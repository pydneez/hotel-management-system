<?php
require_once(__DIR__ . '/../auth_check.php');
require_once(__DIR__ . '/../../connect.php'); // Make sure this path is correct!

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. Get Guest Data ---
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // --- 2. Get Reservation Data ---
    $type_id = intval($_POST['type_id']);
    $num_adults = intval($_POST['num_adults']);
    $num_children = intval($_POST['num_children']);

    $checkin_date_str = $_POST['checkin_date'];  // e.g., "2025-11-20"
    $checkout_date_str = $_POST['checkout_date']; // e.g., "2025-11-22"
    
    // Add the required 17:00:00 time to match your database TIMESTAMP format
    $checkin_timestamp = $checkin_date_str . ' 17:00:00';
    $checkout_timestamp = $checkout_date_str . ' 17:00:00';

    // --- 3. Validation and Calculation ---
    
    // Basic validation
    if (empty($fname) || empty($lname) || empty($email) || empty($phone) || $type_id <= 0 || empty($checkin_date_str) || empty($checkout_date_str) || $num_adults <= 0) {
        header('Location: add_reservation.php?error=' . urlencode('Missing required fields.'));
        exit;
    }
    
    // Date validation
    $checkin_dt = new DateTime($checkin_timestamp);
    $checkout_dt = new DateTime($checkout_timestamp);
    if ($checkout_dt <= $checkin_dt) {
        header('Location: add_reservation.php?error=' . urlencode('Check-out date must be after check-in date.'));
        exit;
    }
    
    // Calculate total nights
    $interval = $checkin_dt->diff($checkout_dt);
    $total_nights = $interval->days;

    // Start database transaction
    $conn->begin_transaction();

    try {
        // --- 4. Create Guest Record ---
        $default_password = 'walkin_default_pass';
        $password_hash = password_hash($default_password, PASSWORD_DEFAULT);

        $stmt_guest = $conn->prepare("
            INSERT INTO guests (fname, lname, email, password_hash, phone, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt_guest->bind_param("sssss", $fname, $lname, $email, $password_hash, $phone);
        $stmt_guest->execute();
        
        $guest_id = $conn->insert_id;
        
        if ($guest_id <= 0) {
            // This will trigger if the email is a duplicate
            if ($conn->errno == 1062) {
                 throw new Exception("A guest with this email already exists.");
            }
            throw new Exception("Failed to create guest record.");
        }
        $stmt_guest->close();

        // --- 5. Create Reservation Record ---
        $booking_date = date('Y-m-d H:i:s');
        $status = 'Confirmed';
        
        $stmt_res = $conn->prepare("
            INSERT INTO reservations 
                (guest_id, type_id, checkin_date, checkout_date, num_adults, num_children, 
                 booking_date, total_nights, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt_res->bind_param(
            "iissiisis",
            $guest_id,
            $type_id,
            $checkin_timestamp,
            $checkout_timestamp,
            $num_adults,
            $num_children,
            $booking_date,
            $total_nights,
            $status
        );
        
        $stmt_res->execute();
        $stmt_res->close();

        // If all queries succeeded, commit the transaction
        $conn->commit();

        // --- 6. Redirect to reservations list ---
        header('Location: reservations.php?msg=' . urlencode('New reservation added successfully.'));
        exit;

    } catch (Exception $e) {
        // If anything failed, roll back
        $conn->rollback();
        
        // Redirect with the specific error message
        header('Location: add_reservation.php?error=' . urlencode($e->getMessage()));
        exit;
    }

} else {
    // If not a POST request, just redirect back
    header('Location: add_reservation.php');
    exit;
}
?>