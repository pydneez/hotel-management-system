<?php
    session_start();
    require_once('connect.php'); 

    // --- 1. Security Check: Guest Must Be Logged In ---
    if (!isset($_SESSION['guest_id']) || $_SESSION['role'] !== 'guest') {
        // Not a logged-in guest.
        header("Location: login.php?message=Please+log+in+to+view+your+dashboard.");
        exit;
    }
    
    $guest_id = $_SESSION['guest_id'];
    $error_message = "";
    $success_message = "";

    // --- 2. Check for Messages from other pages (like cancellation) ---
    if (isset($_GET['status'])) {
        if ($_GET['status'] === 'cancelled_ok') {
            $success_message = "Your booking has been successfully cancelled.";
        }
        if ($_GET['status'] === 'cancel_failed') {
            $error_message = "Could not cancel booking. It may be too late or an error occurred.";
        }
    }

    // --- 3. Fetch all bookings for this guest ---
    $bookings = [];
    $query = "
        SELECT 
            r.res_id, r.checkin_date, r.checkout_date, r.total_nights, 
            r.total_cost, r.status, r.num_adults, r.num_children,
            rt.type_name,
            rm.room_no
        FROM reservations r
        JOIN RoomTypes rt ON r.type_id = rt.type_id
        LEFT JOIN Rooms rm ON r.room_no = rm.room_no -- LEFT JOIN in case room_no is NULL
        WHERE r.guest_id = ?
        ORDER BY r.checkin_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $guest_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
    } else {
        $error_message = "An error occurred while fetching your bookings.";
    }
    $stmt->close();
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | RoyalStay Hotel</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>

    <?php include "navbar.php"; ?>
    
    <main class="main-content room-section"> 
        <div class="container">
            <h1 class="section-heading">My Bookings</h1>
            <p style="margin-top: -1.5rem; margin-bottom: 2rem; font-size: 1.1rem; color: #555;">View and manage your reservations</p>
            
            <div id="ajax-message-area" style="margin-bottom: 2rem;"> 
                <?php if (!empty($error_message)): ?>
                    <div class="form-message error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="form-message success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
            </div>
            
            <div class="booking-list">
                <?php if (empty($bookings)): ?>
                    <div class="card no-results-message">
                        <h3>You have no bookings.</h3>
                        <p>Why not find your perfect room?</p>
                        <a href="search_results.php" class="btn-primary" style="margin-top: 1rem;">Find a Room</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): 
                        $booking_ref = "BK" . str_replace('-', '', $booking['checkin_date']) . $booking['res_id'];
                        $total_guests = $booking['num_adults'] + $booking['num_children'];
                    ?>
                        <div class="booking-item-card" style = "flex-direction: column;">
                            <div >
                                <div style = "display:flex; justify-content: space-between;">
                                    <div class="booking-item-header">
                                        <div>
                                            <div style = "display: flex; align-items: center; justify-content: space-between;">
                                                <div>
                                                    <h3><?php echo htmlspecialchars($booking['type_name']); ?></h3>
                                                </div>
                                                <div>
                                                    <?php if($booking['room_no']): ?>
                                                        <span class="room-badge">Room <?php echo htmlspecialchars($booking['room_no']); ?></span>
                                                        <?php else: ?>
                                                        <?php
                                                            $status_text = htmlspecialchars($booking['status']);
                                                            $status_class = 'status-default'; // Fallback
                                                            
                                                            // Assign class based on status
                                                            switch (strtolower($status_text)) {
                                                                case 'confirmed':
                                                                case 'pending':
                                                                    $status_class = 'status-confirmed'; // Green
                                                                    break;
                                                                case 'checked-in':
                                                                    $status_class = 'status-checked-in'; // Red
                                                                    break;
                                                                case 'cancelled':
                                                                    $status_class = 'status-cancelled'; // Red
                                                                    break;
                                                                case 'checked-out':
                                                                    $status_class = 'status-checked-out'; // Grey
                                                                    break;
                                                            }
                                                        ?>
                                                        <span class="status-badge <?php echo $status_class; ?>">
                                                            <?php echo $status_text; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <p class="booking-reference">Booking Reference: <?php echo htmlspecialchars($booking_ref); ?></p>
                                        </div>
                                    </div>
                                    <div class="booking-item-body">
                                        <ul class="booking-details-list">
                                            <li>
                                                <span>Check-in:</span>
                                                <strong><?php echo htmlspecialchars($booking['checkin_date']); ?></strong>
                                            </li>
                                            <li>
                                                <span>Check-out:</span>
                                                <strong><?php echo htmlspecialchars($booking['checkout_date']); ?></strong>
                                            </li>
                                            <li>
                                                <span>Guests:</span>
                                                <strong><?php echo $total_guests; ?></strong>
                                            </li>
                                            <li>
                                                <span>Total:</span>
                                                <strong class="booking-price">$<?php echo htmlspecialchars(number_format($booking['total_cost'], 2)); ?></strong>
                                            </li>
                                        </ul>
                                </div>
                                </div>
                            </div>
                            
                            <div >
                                <div class="booking-item-footer">
                                    <?php
                                        $can_cancel = false;
                                        if ($booking['status'] === 'Confirmed' || $booking['status'] === 'Pending') {
                                            try {
                                                $checkin_date = new DateTime($booking['checkin_date']);
                                                $now = new DateTime();
                                                // Check if check-in is in the future
                                                if ($checkin_date > $now) {
                                                    $interval = $now->diff($checkin_date);
                                                    $hours_until_checkin = ($interval->days * 24) + $interval->h;
                                                    // Check if it's more than 24 hours away
                                                    if ($hours_until_checkin > 24) {
                                                        $can_cancel = true;
                                                    }
                                                }
                                            } catch(Exception $e) {
                                            }
                                        }
                                    ?>
                                    
                                    
                                    <?php if ($can_cancel): ?>
                                        <a href="cancel_booking.php?res_id=<?php echo $booking['res_id']; ?>" 
                                            class="btn-cancel-booking" 
                                            onclick="return confirm('Are you sure you want to cancel this booking?');">
                                            Cancel Booking
                                        </a>
                                    <?php elseif ($booking['status'] === 'Cancelled'): ?>
                                        <p class="booking-footer-text">This booking has been cancelled.</p>
                                    <?php elseif ($booking['status'] === 'Checked-Out'): ?>
                                        <p class="booking-footer-text">This booking is complete. Thank you for staying with us!</p>
                                    <?php elseif ($booking['status'] === 'Checked-In'): ?>
                                        <p class="booking-footer-text">Enjoy your stay!</p>
                                    <?php else: ?>
                                        <p class="booking-footer-text">This booking cannot be cancelled (active or within 24 hours of check-in).</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                        </div> 
                    <?php endforeach; ?>
                <?php endif; ?>  
            </div>

        </div>
    </main>
    
    <?php include "footer.php"; ?>

</body>
</html>