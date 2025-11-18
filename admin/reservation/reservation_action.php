<?php
require_once(__DIR__ . '/../auth_check.php');

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $action == 'checkin') {
    $res_id = filter_input(INPUT_POST, 'res_id', FILTER_SANITIZE_NUMBER_INT);
    $room_no = filter_input(INPUT_POST, 'room_no', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($res_id) || empty($room_no)) {
        header("Location: checkin.php?error=" . urlencode("Missing data for check-in."));
        exit;
    }

    $stmt = $conn->prepare("CALL sp_CheckInGuest(?, ?)");
    $stmt->bind_param("is", $res_id, $room_no);
    
    try {
        $stmt->execute();
        header("Location: checkin.php?status=checkin_success");
        exit;
    } catch (mysqli_sql_exception $e) {
        header("Location: checkin.php?error=" . urlencode($e->getMessage()));
        exit;
    }
    $stmt->close();
}

elseif ($action == 'checkout') {
    $res_id = filter_input(INPUT_GET, 'res_id', FILTER_SANITIZE_NUMBER_INT);
    $room_no = filter_input(INPUT_GET, 'room_no', FILTER_SANITIZE_SPECIAL_CHARS);

    if (empty($res_id) || empty($room_no)) {
        header("Location: checkout.php?error=" . urlencode("Missing data for check-out."));
        exit;
    }

    $stmt = $conn->prepare("CALL sp_CheckOutGuest(?, ?)");
    $stmt->bind_param("is", $res_id, $room_no);

    try {
        $stmt->execute();
        header("Location: checkout.php?status=checkout_success");
        exit;
    } catch (mysqli_sql_exception $e) {
        header("Location: checkout.php?error=" . urlencode($e->getMessage()));
        exit;
    }
    $stmt->close();
}

// --- Fallback ---
else {
    header("Location: reservations.php?error=" . urlencode("Invalid action."));
    exit;
}
?>