<?php
    session_start();
    require_once('connect.php'); 
    
    $error_message = "";
    $success_message = "";

    // --- 1. Security Check: Guest Must Be Logged In ---
    if (!isset($_SESSION['guest_id']) || $_SESSION['role'] !== 'guest') {
        // Not a logged-in guest. Save their intended booking and send to login.
        $redirect_url = "booking_confirm.php?" . http_build_query($_GET);
        $message = "Please log in or create an account to complete your booking.";
        header("Location: login.php?redirect=" . urlencode($redirect_url) . "&message=" . urlencode($message));
        exit;
    }
    
    // --- 2. Get and Sanitize All Data ---
    $guest_id = $_SESSION['guest_id'];
    $type_id = filter_input(INPUT_GET, 'type_id', FILTER_SANITIZE_NUMBER_INT);
    $check_in = filter_input(INPUT_GET, 'check_in', FILTER_SANITIZE_SPECIAL_CHARS);
    $check_out = filter_input(INPUT_GET, 'check_out', FILTER_SANITIZE_SPECIAL_CHARS);
    $adults = filter_input(INPUT_GET, 'adults', FILTER_SANITIZE_NUMBER_INT);
    $children = filter_input(INPUT_GET, 'children', FILTER_SANITIZE_NUMBER_INT);
    $num_nights = filter_input(INPUT_GET, 'nights', FILTER_SANITIZE_NUMBER_INT);
    $total_cost = filter_input(INPUT_GET, 'cost', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    
    // Recalculate total_guests
    $total_guests = (int)$adults + (int)$children;

    // Simple validation
    if (empty($type_id) || empty($check_in) || empty($check_out) || empty($num_nights) || empty($total_cost)) {
        header("Location: index.php?error=Invalid booking data. Please try your search again.");
        exit;
    }
    
    // --- 3. Fetch Room Details for Display ---
    $room_type = null;
    $room_images = [];
    
    // Fetch room type details
    $stmt_room = $conn->prepare("SELECT * FROM RoomTypes WHERE type_id = ?");
    $stmt_room->bind_param("i", $type_id);
    $stmt_room->execute();
    $room_type = $stmt_room->get_result()->fetch_assoc();
    $stmt_room->close();
    
    // Fetch all images for this room type
    $stmt_images = $conn->prepare("SELECT image_url FROM RoomTypeImages WHERE type_id = ?");
    $stmt_images->bind_param("i", $type_id);
    $stmt_images->execute();
    $images_result = $stmt_images->get_result();
    while ($row = $images_result->fetch_assoc()) {
        $room_images[] = $row['image_url'];
    }
    $stmt_images->close();

    if (!$room_type) {
         header("Location: index.php?error=Room type not found.");
         exit;
    }
    
    // --- 4. Handle Final Booking Submission ---
    if (isset($_POST['submit'])) {
        if (!isset($_POST['terms'])) {
            $error_message = "You must agree to the terms and conditions to proceed.";
        } else {
            $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_SPECIAL_CHARS);
            
            // Call the new Stored Procedure
            $stmt_book = $conn->prepare("CALL sp_CreateGuestReservation(?, ?, ?, ?, ?, ?, ?, @p_res_id, @p_message)");
            $stmt_book->bind_param("iissiis", $guest_id, $type_id, $check_in, $check_out, $adults, $children, $payment_method);
            
            try {
                $stmt_book->execute();
                // Get OUT parameters
                $result = $conn->query("SELECT @p_res_id as res_id, @p_message as message")->fetch_assoc();
                
                if ($result['res_id']) {
                    header("Location: booking_success.php?res_id=" . $result['res_id']);
                    exit;
                } else {
                    // Stored procedure returned an error (e.g., room is now full)
                    $error_message = $result['message'];
                }
            } catch (mysqli_sql_exception $e) {
                $error_message = "An error occurred: " . $e->getMessage();
            }
            $stmt_book->close();
        }
    }
    
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Your Booking</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

    <?php include "navbar.php"; ?>
    
    <main class="main-content" style="background-color: var(--content-bg);">
        <div class="container" style="padding: 3rem 0;">
            <h1 style="margin-bottom: 2rem;">Confirm Your Booking</h1>
            
            <?php if (!empty($error_message)): ?>
                <div class="form-message error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="booking_confirm.php?<?php echo http_build_query($_GET); ?>">
                <div class="booking-grid">
                
                    <!-- Left Column: Room Details -->
                    <div class="booking-summary-card">
                        <h2><?php echo htmlspecialchars($room_type['type_name']); ?></h2>
                        
                        <div class="room-gallery">
                            <img src="<?php echo htmlspecialchars(!empty($room_images) ? $room_images[0] : 'https://placehold.co/800x400/0a2342/f0f4f8?text=Room'); ?>" 
                                 alt="Main room image" 
                                 class="room-gallery-main-image" 
                                 id="main-image">
                            
                            <?php if (count($room_images) > 1): ?>
                            <div class="room-gallery-thumbnails">
                                <?php foreach ($room_images as $index => $image_url): ?>
                                <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                     alt="Room thumbnail <?php echo $index + 1; ?>" 
                                     class="room-gallery-thumb <?php echo ($index == 0) ? 'active' : ''; ?>"
                                     onclick="document.getElementById('main-image').src = this.src; document.querySelectorAll('.room-gallery-thumb').forEach(t => t.classList.remove('active')); this.classList.add('active');">
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <h3 style="margin-top: 1.5rem;">Your Stay Details</h3>
                        <ul class="booking-summary-list">
                            <li><span>Check-in</span> <strong><?php echo htmlspecialchars($check_in); ?></strong></li>
                            <li><span>Check-out</span> <strong><?php echo htmlspecialchars($check_out); ?></strong></li>
                            <li><span>Total Nights</span> <strong><?php echo htmlspecialchars($num_nights); ?></strong></li>
                            <li><span>Adults</span> <strong><?php echo htmlspecialchars($adults); ?></strong></li>
                            <li><span>Children</span> <strong><?php echo htmlspecialchars($children); ?></strong></li>
                        </ul>
                        
                        <div class="booking-total">
                            Total Cost: $<?php echo htmlspecialchars(number_format($total_cost, 2)); ?>
                        </div>
                    </div>
                    
                    <!-- Right Column: Finalize -->
                    <div class="booking-summary-card">
                        <h3>Finalize Your Booking</h3>
                        
                        <div class="payment-option-group">
                            <label>How would you like to pay?</label>
                            <small>Payment is due upon arrival at the hotel.</small>
                            
                            <label class="payment-option" style="margin-top: 1rem;">
                                <input type="radio" name="payment_method" value="Credit Card" checked>
                                Credit / Debit Card
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="Cash">
                                Pay with Cash
                            </label>
                        </div>
                        
                        <div class="terms-box">
                            <label>
                                <input type="checkbox" name="terms" required>
                                I agree to the <a href="#" onclick="alert('Terms: No-show fee is $50. Cancellation must be 24 hours in advance.')">Terms and Conditions</a>.
                            </label>
                        </div>
                        
                        <button type="submit" name="submit" class="btn btn-primary btn-book-now">
                            Confirm Booking
                        </button>
                    </div>
                
                </div>
            </form>

        </div>
    </main>
    
    <?php include "footer.php"; ?>

</body>
</html>