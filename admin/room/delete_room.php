<?php
    require_once(__DIR__ . '/../auth_check.php');

    // 1. Get and Validate the Room Number
    $room_no = filter_input(INPUT_GET, 'room_no', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($room_no)) {
        header("Location: rooms.php?error=" . urlencode("Invalid room number."));
        exit;
    }

    // Start a transaction to ensure data integrity
    $conn->begin_transaction();

    try {
        // 2. Security Check: See if the room has any active or future bookings
        $check_sql = "SELECT COUNT(*) as total 
                      FROM reservations 
                      WHERE room_no = ? AND status IN ('Confirmed', 'Pending', 'Checked-In')";
                      
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("s", $room_no);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if ($result_check['total'] > 0) {
            // Room is in use, dont delete
            throw new Exception("Cannot delete room. It has " . $result_check['total'] . " active or future booking(s) associated with it.");
        }
        
        // 3. (Optional, but good): Delete any old, 'Checked-Out' or 'Cancelled' reservation associations
        // This is safe because the foreign key is ON DELETE RESTRICT by default,
        // so the DELETE in step 4 would fail if these aren't cleared.
        // A better long-term solution is 'ON DELETE SET NULL'.
        // For now, we'll just update them to NULL.
        $stmt_clear = $conn->prepare("UPDATE reservations SET room_no = NULL WHERE room_no = ?");
        $stmt_clear->bind_param("s", $room_no);
        $stmt_clear->execute();
        $stmt_clear->close();

        // 4. No conflicts found. Proceed with deleting the room.
        $delete_sql = "DELETE FROM Rooms WHERE room_no = ?";
        $stmt_delete = $conn->prepare($delete_sql);
        $stmt_delete->bind_param("s", $room_no);
        
        if (!$stmt_delete->execute()) {
             throw new Exception("Failed to delete the room.");
        }
        
        $stmt_delete->close();

        // 5. All good, commit the changes
        $conn->commit();
        header("Location: rooms.php?status=success_delete");
        exit;

    } catch (Exception $e) {
        // If anything failed, roll back
        $conn->rollback();
        
        header("Location: rooms.php?error=" . urlencode($e->getMessage()));
        exit;
    }
?>