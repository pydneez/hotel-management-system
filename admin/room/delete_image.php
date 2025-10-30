<?php
// This script handles the AJAX request to delete an image.
require_once(__DIR__ . '/../auth_check.php');
header('Content-Type: application/json');

// Check if image_id is provided via POST
if (isset($_POST['image_id'])) {
    $image_id = filter_input(INPUT_POST, 'image_id', FILTER_SANITIZE_NUMBER_INT);
    
    // 1. Get the image_url from the DB *before* deleting the record
    $stmt_find = $conn->prepare("SELECT image_url FROM RoomTypeImages WHERE image_id = ?");
    $stmt_find->bind_param("i", $image_id);
    $stmt_find->execute();
    $result = $stmt_find->get_result();
    $image = $result->fetch_assoc();
    $stmt_find->close();
    
    if ($image) {
        // 2. Delete the record from the database
        $stmt_delete = $conn->prepare("DELETE FROM RoomTypeImages WHERE image_id = ?");
        $stmt_delete->bind_param("i", $image_id);
        
        if ($stmt_delete->execute()) {
            $stmt_delete->close();
            
            // 3. Delete the actual file from the server
            $file_path = $_SERVER['DOCUMENT_ROOT'] . $image['image_url'];
            if (file_exists($file_path)) {
                unlink($file_path); // Deletes the file
            }
            
            echo json_encode(['success' => true, 'message' => 'Image deleted.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database delete failed.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Image not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No image ID provided.']);
}
$conn->close();
exit;
?>
