<?php
    session_start();
    require_once('connect.php'); 
    
    $error_message = "";
    $available_rooms = [];
    $num_nights = 0;
    $total_guests = 0;

    // --- 1. Get and Validate User Input ---
    $check_in = filter_input(INPUT_GET, 'check_in', FILTER_SANITIZE_SPECIAL_CHARS);
    $check_out = filter_input(INPUT_GET, 'check_out', FILTER_SANITIZE_SPECIAL_CHARS);
    $adults = filter_input(INPUT_GET, 'adults', FILTER_SANITIZE_NUMBER_INT);
    $children = filter_input(INPUT_GET, 'children', FILTER_SANITIZE_NUMBER_INT);

    // Set defaults if inputs are present (for form pre-filling)
    $adults = $adults ? $adults : 1;
    $children = $children ? $children : 0;


    if (empty($check_in) || empty($check_out) || empty($adults)) {
        $error_message = "Please fill out all required fields (check-in, check-out, and adults).";
    } else {
        try {
            $check_in_date = new DateTime($check_in);
            $check_out_date = new DateTime($check_out);
            $today = new DateTime(date('Y-m-d'));

            if ($check_in_date >= $check_out_date) {
                $error_message = "Your check-out date must be at least one day after your check-in date.";
            } elseif ($check_in_date < $today) {
                 $error_message = "Your check-in date cannot be in the past.";
            } else {
                $num_nights = $check_out_date->diff($check_in_date)->days;
                $total_guests = (int)$adults + (int)$children;
            }
        } catch (Exception $e) {
            $error_message = "Invalid date format. Please try again.";
        }
    }

    // --- 2. Query for Available Rooms (if input is valid) ---
    if (empty($error_message) && $num_nights > 0) {
        
        $query = "CALL sp_SearchAvailableRoomTypes(?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        // s = check_in, s = check_out, i = total_guests, i = num_nights
        $stmt->bind_param("ssii", $check_in, $check_out, $total_guests, $num_nights);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $available_rooms[] = $row;
            }
        } else {
            $error_message = "An error occurred while searching. Please try again.";
        }
        $stmt->close();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Rooms | RoyalStay Hotel</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

    <?php include "navbar.php"; ?>
    
    <main class="room-section" style="min-height: 70vh;">
        <div class="container">
            
            <div class="availability-checker" style="margin-bottom: 3rem;  box-shadow: none; border: 1px solid var(--border-color);">
                <form action="search_results.php" method="GET">
                    <div>
                        <label for="check_in">Check-in Date</label>
                        <input type="date" id="check_in" name="check_in" 
                                min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo htmlspecialchars($check_in); ?>" required>
                    </div>

                    <div>
                        <label for="check_out">Check-out Date</label>
                        <input type="date" id="check_out" name="check_out" 
                               value="<?php echo htmlspecialchars($check_out); ?>" required>
                    </div>
                    <div>
                        <label for="adults">Adults</label>
                        <input type="number" id="adults" name="adults" min="1" 
                               value="<?php echo htmlspecialchars($adults); ?>" required>
                    </div>
                    <div>
                        <label for="children">Children</label>
                        <input type="number" id="children" name="children" min="0" 
                               value="<?php echo htmlspecialchars($children); ?>">
                    </div>
                    <div>
                        <label>&nbsp;</label> 
                        <input type="submit" value="Edit Search" class="btn-primary">
                    </div>
                </form>
            </div>

            <div id="message-display"> 
                <?php if (!empty($error_message)): ?>
                        <p style="color: red; font-weight: bold; text-align: center;"><?php echo $error_message; ?></p>
                <?php endif; ?>
            </div> 

            <h2 style="text-align: left; margin-bottom: 2rem; border-bottom: 2px solid var(--border-color); padding-bottom: 1rem;">
                Available Rooms
            </h2>
            
            <div class="room-grid">
                <?php if (empty($error_message) && !empty($available_rooms)): ?>
                    <?php foreach($available_rooms as $room): ?>
                        <?php
                            $booking_url = "booking_confirm.php?type_id={$room['type_id']}&check_in={$check_in}&check_out={$check_out}&adults={$adults}&children={$children}&nights={$num_nights}&cost={$room['total_cost']}";                            
                            // Check if a GUEST (not staff) is logged in
                            if (isset($_SESSION['email']) && $_SESSION['role'] === 'guest') {
                                $book_now_link = $booking_url;
                            } else {
                                // Not logged in, or is staff. Send to login.
                                $book_now_link = "login.php?redirect=" . urlencode($booking_url) . "&message=Please+log+in+as+a+guest+to+book+a+room.";
                            }
                        ?>
                        <div class="room-card">
                            <img 
                                src="<?php echo htmlspecialchars($room['main_image_url'] ?? '/uploads/rooms/default.jpg'); ?>" 
                                alt="<?php echo htmlspecialchars($room['type_name']); ?>" 
                                class="room-card-image"
                                onerror="this.onerror=null; this.src='https://placehold.co/400x220/0a2342/f0f4f8?text=Image+Not+Found';">

                            <div class="room-card-content">
                                <h3><?php echo htmlspecialchars($room['type_name']); ?></h3>

                                <!-- Show TOTAL price, not per-night -->
                                <div class="room-card-price">
                                    $<?php echo htmlspecialchars(number_format($room['total_cost'], 2)); ?>
                                    <span style="font-weight: 400; font-size: 0.9rem; color: #555;">
                                        (for <?php echo $num_nights; ?> nights)
                                    </span>
                                </div>
                                <p class="room-card-description">
                                    <?php echo htmlspecialchars($room['description']); ?>
                                </p>
                                
                                <?php if (!empty($room['amenities'])): ?>
                                    <ul class="room-card-amenities">
                                        <?php 
                                            $amenities = explode(',', $room['amenities']);
                                            foreach ($amenities as $amenity): 
                                        ?>
                                            <li><?php echo htmlspecialchars(trim($amenity)); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>

                                <a href="<?php echo htmlspecialchars($book_now_link); ?>" class="btn-primary">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (empty($error_message)): ?>
                    <div class="card" style="text-align: center; grid-column: 1 / -1;">
                        <h3>No Rooms Available</h3>
                        <p>We're sorry, but no rooms match your criteria (<?php echo $total_guests; ?> guests) for those dates. Please try a different date range or contact us directly.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <br>
    </section>
    
    <?php include "footer.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkIn = document.getElementById('check_in');
            const checkOut = document.getElementById('check_out');
            
            const today = new Date().toISOString().split('T')[0];
            
            // Set min on check-in *if* it's not already set (it might be in the past)
            if (!checkIn.value || checkIn.value < today) {
                 checkIn.setAttribute('min', today);
            }

            // Function to set checkout min date
            function setMinCheckout() {
                if (checkIn.value) {
                    let nextDay = new Date(checkIn.value);
                    nextDay.setDate(nextDay.getDate() + 1);
                    const minCheckout = nextDay.toISOString().split('T')[0];
                    checkOut.setAttribute('min', minCheckout);
                    // Also set checkout value if it's no longer valid
                    if (checkOut.value < minCheckout) {
                        checkOut.value = minCheckout;
                    }
                }
            }

            // Set it on page load
            setMinCheckout();
            
            // Set it on change
            checkIn.addEventListener('change', setMinCheckout);
        });
    </script>

</body>
</html>