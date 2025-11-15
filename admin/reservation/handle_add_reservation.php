<?php
require_once(__DIR__ . '/../auth_check.php');
<<<<<<< HEAD
require_once(__DIR__ . '/../../connect.php'); // Make sure this path is correct!
=======
>>>>>>> a9e9cbd (feat: reservation dashboard, walk-in reservation, check-in, check-out, all updated accordingly)

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

<<<<<<< HEAD
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
=======
    // --- 1. Get and Sanitize All Data ---
    $fname = trim(filter_input(INPUT_POST, 'fname', FILTER_SANITIZE_SPECIAL_CHARS));
    $lname = trim(filter_input(INPUT_POST, 'lname', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));
    
    $type_id = filter_input(INPUT_POST, 'type_id', FILTER_SANITIZE_NUMBER_INT);
    $num_adults = filter_input(INPUT_POST, 'num_adults', FILTER_SANITIZE_NUMBER_INT);
    $num_children = filter_input(INPUT_POST, 'num_children', FILTER_SANITIZE_NUMBER_INT);
    $checkin_date = filter_input(INPUT_POST, 'checkin_date', FILTER_SANITIZE_SPECIAL_CHARS);
    $checkout_date = filter_input(INPUT_POST, 'checkout_date', FILTER_SANITIZE_SPECIAL_CHARS);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_SPECIAL_CHARS);
    
    // --- 2. Basic Validation ---
    if (empty($fname) || empty($lname) || empty($email) || empty($phone) || empty($type_id) || empty($checkin_date) || empty($checkout_date) || empty($num_adults) || empty($payment_method)) {
>>>>>>> a9e9cbd (feat: reservation dashboard, walk-in reservation, check-in, check-out, all updated accordingly)
        header('Location: add_reservation.php?error=' . urlencode('Missing required fields.'));
        exit;
    }
    
    // Date validation
<<<<<<< HEAD
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
=======
    if ($checkout_date <= $checkin_date) {
        header('Location: add_reservation.php?error=' . urlencode('Check-out date must be after check-in date.'));
        exit;
    }

    // --- 3. Call The Stored Procedure ---
    $stmt = $conn->prepare("
        CALL sp_CreateWalkInReservation(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @p_res_id, @p_message)
    ");
    
    // Bind all 10 input parameters
    $stmt->bind_param("ssssissiis",
        $fname, $lname, $email, $phone,
        $type_id, $checkin_date, $checkout_date, $num_adults, $num_children, $payment_method
    );

    try {
        $stmt->execute();
        $stmt->close();
        
        // Get the OUT parameters
        $result = $conn->query("SELECT @p_res_id as res_id, @p_message as message")->fetch_assoc();

        if ($result['res_id']) {
            // Success!
            header('Location: reservations.php?status=add_success&msg=' . urlencode($result['message']));
            exit;
        } else {
            // Procedure returned a controlled error (e.g., "no rooms")
            header('Location: add_reservation.php?error=' . urlencode($result['message']));
            exit;
        }

    } catch (Exception $e) {
        // Handle unexpected SQL error
>>>>>>> a9e9cbd (feat: reservation dashboard, walk-in reservation, check-in, check-out, all updated accordingly)
        header('Location: add_reservation.php?error=' . urlencode($e->getMessage()));
        exit;
    }

} else {
    // If not a POST request, just redirect back
    header('Location: add_reservation.php');
    exit;
}
?>