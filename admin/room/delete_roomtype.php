<?php
    require_once(__DIR__ . '/../auth_check.php');

    $type_id_to_delete = filter_input(INPUT_GET, 'type_id', FILTER_SANITIZE_NUMBER_INT);

    if (empty($type_id_to_delete)) {
        header("Location: room_types.php?error=" . urlencode("Invalid room type ID."));
        exit;
    }

    // Start a transaction
    $conn->begin_transaction();

    try {
        // CRITICAL CHECK 1: Are any physical rooms still using this type?
        $stmt_check_rooms = $conn->prepare("SELECT COUNT(*) as total FROM Rooms WHERE type_id = ?");
        $stmt_check_rooms->bind_param("i", $type_id_to_delete);
        $stmt_check_rooms->execute();
        $result_rooms = $stmt_check_rooms->get_result()->fetch_assoc();
        $stmt_check_rooms->close();

        if ($result_rooms['total'] > 0) {
            throw new Exception("Cannot delete. " . $result_rooms['total'] . " physical rooms are still assigned to this type. Please re-assign them first.");
        }

        // Are any reservations (past or future) linked to this?
        $stmt_check_res = $conn->prepare("SELECT COUNT(*) as total FROM reservations WHERE type_id = ?");
        $stmt_check_res->bind_param("i", $type_id_to_delete);
        $stmt_check_res->execute();
        $result_res = $stmt_check_res->get_result()->fetch_assoc();
        $stmt_check_res->close();

        if ($result_res['total'] > 0) {
            throw new Exception("Cannot delete. " . $result_res['total'] . " reservations (past and future) are linked to this type. Deleting it would corrupt historical booking data.");
        }

        // All checks passed. It is safe to delete.
        // We can delete from RoomTypes. The 'ON DELETE CASCADE'
        // on the 'RoomTypeImages' table will automatically delete all associated images.
        
        $stmt_delete = $conn->prepare("DELETE FROM RoomTypes WHERE type_id = ?");
        $stmt_delete->bind_param("i", $type_id_to_delete);
        
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                // Success
                $conn->commit();
                header("Location: room_types.php?status=delete_success");
            } else {
                throw new Exception("Room type not found or already deleted.");
            }
        } else {
            throw new Exception("Failed to delete room type.");
        }
        $stmt_delete->close();
        $conn->close();
        exit;

    } catch (Exception $e) {
        // If anything failed, roll back
        $conn->rollback();
        $conn->close();
        // Redirect with the specific error message
        header("Location: room_types.php?error=" . urlencode($e->getMessage()));
        exit;
    }
?>