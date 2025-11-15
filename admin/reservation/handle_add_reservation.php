<?php
require_once(__DIR__ . '/../auth_check.php');

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

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
        header('Location: add_reservation.php?error=' . urlencode('Missing required fields.'));
        exit;
    }
    
    // Date validation
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
        header('Location: add_reservation.php?error=' . urlencode($e->getMessage()));
        exit;
    }

} else {
    // If not a POST request, just redirect back
    header('Location: add_reservation.php');
    exit;
}
?>