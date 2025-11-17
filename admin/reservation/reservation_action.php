<?php
require_once(__DIR__ . '/../auth_check.php');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Start transaction for operations that modify multiple tables
$conn->begin_transaction();

try {
    // --- ACTION: Check-in & Assign Room (from Modal POST) ---
    if ($_SERVER["REQUEST_METHOD"] == "POST" && $action == 'checkin_assign') {
        $res_id = isset($_POST['res_id']) ? intval($_POST['res_id']) : 0;
        $room_no = isset($_POST['room_no']) ? $_POST['room_no'] : '';

        if ($res_id <= 0 || empty($room_no)) {
            throw new Exception("Invalid data provided for check-in.");
        }

        // 1. Update reservation status and assign room number
        // We only update rooms that are 'Confirmed' to prevent re-checking-in
        $stmt1 = $conn->prepare("
            UPDATE reservations 
            SET status = 'Checked-In', room_no = ? 
            WHERE res_id = ? AND status = 'Confirmed'
        ");
        $stmt1->bind_param("si", $room_no, $res_id);
        $stmt1->execute();

        if ($stmt1->affected_rows == 0) {
            throw new Exception("Reservation not found or already checked in.");
        }
        
        // As per your sample data (res_id 2, room 201), 
        // a 'Checked-In' room's status in the 'rooms' table remains 'Available'.
        // So, we do NOT update the 'rooms' table on check-in.
        
        $stmt1->close();
        $conn->commit();
        header('Location: reservations.php?msg=checkin_success');
        exit;
    }

    // --- ACTION: Check-out (from GET link) ---
    elseif ($action == 'checkout') {
        $res_id = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;
        $room_no = isset($_GET['room_no']) ? $_GET['room_no'] : '';

        if ($res_id <= 0 || empty($room_no)) {
            throw new Exception("Invalid data provided for check-out.");
        }

        // 1. Update reservation status to 'Checked-Out'
        $stmt1 = $conn->prepare("
            UPDATE reservations 
            SET status = 'Checked-Out' 
            WHERE res_id = ? AND status = 'Checked-In'
        ");
        $stmt1->bind_param("i", $res_id);
        $stmt1->execute();

        if ($stmt1->affected_rows == 0) {
            throw new Exception("Reservation not found or not checked in.");
        }
        $stmt1->close();

        // 2. Update room status to 'Cleaning' as requested
        $stmt2 = $conn->prepare("UPDATE rooms SET status = 'Cleaning' WHERE room_no = ?");
        $stmt2->bind_param("s", $room_no);
        $stmt2->execute();
        
        if ($stmt2->affected_rows == 0) {
            // This is not critical enough to roll back, but good to know
            // Maybe log this error
        }
        $stmt2->close();
        
        $conn->commit();
        header('Location: reservations.php?msg=checkout_success');
        exit;
    }
    
    else {
        throw new Exception("No valid action specified.");
    }

} catch (Exception $e) {
    // If anything went wrong, roll back
    $conn->rollback();
    // Redirect with an error
    header('Location: reservations.php?error=' . urlencode($e->getMessage()));
    exit;
}
?>