<?php
    session_start();
    require_once('connect.php'); 

    // Security Check: Guest Must Be Logged In
    if (!isset($_SESSION['guest_id']) || $_SESSION['role'] !== 'guest') {
        header("Location: login.php");
        exit;
    }
    
    // Get Data
    $guest_id = $_SESSION['guest_id'];
    $res_id = filter_input(INPUT_GET, 'res_id', FILTER_SANITIZE_NUMBER_INT);

    if (empty($res_id)) {
        header("Location: dashboard.php?status=cancel_failed");
        exit;
    }

    // Call the Stored Procedure -- pass in the guest's ID and the reservation ID.
    // this handles all security checks and transaction.
    $stmt = $conn->prepare("CALL sp_CancelGuestBooking(?, ?, @p_message_type, @p_message)");
    $stmt->bind_param("ii", $guest_id, $res_id);
    
    try {
        $stmt->execute();
        $stmt->close();
        
        // Get the OUT parameters from the procedure
        $result = $conn->query("SELECT @p_message_type as type, @p_message as message")->fetch_assoc();
        
        if ($result['type'] === 'success') {
            // Success!
            header("Location: dashboard.php?status=cancelled_ok");
            exit;
        } else {
            // Procedure returned a controlled error (e.g., "within 24 hours")
            header("Location: dashboard.php?status=cancel_failed&error=" . urlencode($result['message']));
            exit;
        }
        
    } catch (mysqli_sql_exception $e) {
        // An unexpected database error occurred
        $conn->rollback();
        header("Location: dashboard.php?status=cancel_failed&error=" . urlencode($e->getMessage()));
        exit;
    }

?>