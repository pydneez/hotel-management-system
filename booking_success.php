<?php
    session_start();
    require_once('connect.php'); 

    // Security Check: Must be a logged-in guest
    if (!isset($_SESSION['guest_id']) || $_SESSION['role'] !== 'guest') {
        header("Location: login.php");
        exit;
    }
    
    // Get the reservation ID from the URL
    $res_id = filter_input(INPUT_GET, 'res_id', FILTER_SANITIZE_NUMBER_INT);
    if (!$res_id) {
        header("Location: index.php?error=No reservation specified.");
        exit;
    }
    
    // Fetch the reservation details to display
    $query = "
        SELECT 
            r.*, 
            g.fname,
            rt.type_name,
            (SELECT rti.image_url 
             FROM RoomTypeImages rti 
             WHERE rti.type_id = r.type_id 
             LIMIT 1) AS main_image_url
        FROM reservations r
        JOIN Guests g ON r.guest_id = g.guest_id
        JOIN RoomTypes rt ON r.type_id = rt.type_id
        WHERE r.res_id = ? AND r.guest_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $res_id, $_SESSION['guest_id']);
    $stmt->execute();
    $reservation = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();

    // If reservation not found or doesn't belong to this guest, redirect
    if (!$reservation) {
        header("Location: index.php?error=Reservation not found.");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed!</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <?php include "navbar.php"; ?>
    
    <main class="main-content" style="background-color: var(--content-bg);">
        <div class="container">
            
            <div class="success-card">
                <h1>Booking Confirmed!</h1>
                <p style="font-size: 1.2rem;">
                    Thank you, <?php echo htmlspecialchars($reservation['fname']); ?>! Your room is reserved.
                </p>
                <p>A confirmation email has been sent to <?php echo htmlspecialchars($_SESSION['email']); ?>.</p>
                
                <ul class="success-summary">
                    <li><span>Reservation ID</span> <strong>#<?php echo htmlspecialchars($reservation['res_id']); ?></strong></li>
                    <li><span>Room Type</span> <strong><?php echo htmlspecialchars($reservation['type_name']); ?></strong></li>
                    <li><span>Check-in</span> <strong><?php echo htmlspecialchars($reservation['checkin_date']); ?></strong></li>
                    <li><span>Check-out</span> <strong><?php echo htmlspecialchars($reservation['checkout_date']); ?></strong></li>
                    <li><span>Total Nights</span> <strong><?php echo htmlspecialchars($reservation['total_nights']); ?></strong></li>
                    <li><span>Total Cost</span> <strong>$<?php echo htmlspecialchars(number_format($reservation['total_cost'], 2)); ?></strong></li>
                </ul>
                
                <a href="dashboard.php" class="btn-primary">View My Bookings</a>
            </div>

        </div>
    </main>
    
    <?php include "footer.php"; ?>
</body>
</html>