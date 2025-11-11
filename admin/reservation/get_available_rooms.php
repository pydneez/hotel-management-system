<?php
require_once(__DIR__ . '/../auth_check.php'); // Secure this endpoint
require_once(__DIR__ . '/../../connect.php');

$type_id = isset($_GET['type_id']) ? intval($_GET['type_id']) : 0;

if ($type_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Find rooms of the correct type that are 'Available'
// This also ensures we don't show rooms that are 'Cleaning' or 'Maintenance'
$sql = "SELECT room_no FROM rooms WHERE type_id = ? AND status = 'Available'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $type_id);
$stmt->execute();
$result = $stmt->get_result();

$rooms = [];
while($row = $result->fetch_assoc()) {
    $rooms[] = $row;
}

$stmt->close();
$conn->close();

// Return the list as JSON
header('Content-Type: application/json');
echo json_encode($rooms);
exit;
?>