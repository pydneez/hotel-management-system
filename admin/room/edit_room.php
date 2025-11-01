<?php
    require_once(__DIR__ . '/../auth_check.php');
    
    $error_message = "";
    $success_message = "";
    $room = null; // To store the specific room's data
    $all_room_types = []; // To store all available room types for the dropdown
    $room_no = null;
    $statuses = []; // For the status dropdown

    // --- Get the Room No (from GET or POST) ---
    if (isset($_GET['room_no'])) {
        $room_no = filter_input(INPUT_GET, 'room_no', FILTER_SANITIZE_SPECIAL_CHARS);
    } elseif (isset($_POST['room_no'])) {
        $room_no = filter_input(INPUT_POST, 'room_no', FILTER_SANITIZE_SPECIAL_CHARS);
    } else {
        header("Location: rooms.php?error=noID"); // No ID, redirect
        exit;
    }

    // --- Step 1: Handle Form Submission (UPDATE) ---
    if (isset($_POST['submit'])) {
        // Sanitize all inputs
        $type_id = filter_input(INPUT_POST, 'type_id', FILTER_SANITIZE_NUMBER_INT);
        $status = trim(filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS));
        // room_no is already sanitized from above

        if (empty($type_id) || empty($status) || empty($room_no)) {
            $error_message = "All fields are required.";
        } else {
            // All good, prepare the UPDATE statement
            $query = "UPDATE Rooms 
                      SET type_id = ?, status = ? 
                      WHERE room_no = ?";
            
            $stmt = $conn->prepare($query);
            // i = integer, s = string, s = string
            $stmt->bind_param("iss", $type_id, $status, $room_no);

            if ($stmt->execute()) {
                // Redirect back to the list page with a success message
                header("Location: rooms.php?status=success_edit");
                exit();
            } else {
                $error_message = "Update failed. Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // --- Step 2: Fetch Data for Form (SELECT) ---
    
    // 2a. Fetch the specific room to edit
    $query_room = "SELECT * FROM Rooms WHERE room_no = ?";
    $stmt_room = $conn->prepare($query_room);
    $stmt_room->bind_param("s", $room_no);
    $stmt_room->execute();
    $room = $stmt_room->get_result()->fetch_assoc();
    $stmt_room->close();

    if (!$room) {
        $error_message = "Room not found.";
    }

    // 2b. Fetch ALL room types to populate the dropdown
    $query_types = "SELECT type_id, type_name FROM RoomTypes ORDER BY type_id";
    $types_result = $conn->query($query_types);
    if ($types_result) {
        while ($row = $types_result->fetch_assoc()) {
            $all_room_types[] = $row;
        }
    } else {
        $error_message .= " Could not fetch room types.";
    }
    
    // --- NEW: 2c. Fetch all possible Statuses from the database (ENUM) ---
    $query_enum = "SHOW COLUMNS FROM Rooms LIKE 'status'";
    $result_enum = $conn->query($query_enum);
    if ($result_enum && $result_enum->num_rows > 0) {
        $row_enum = $result_enum->fetch_assoc();
        $type = $row_enum['Type'];
        // This regex extracts the values from an ENUM definition like ENUM('Val1','Val2')
        preg_match_all("/'([^']+)'/", $type, $matches);
        if (isset($matches[1])) {
            $statuses = $matches[1];
        }
    }
    
    // Fallback if the query fails or it's not an ENUM
    if (empty($statuses)) {
        $statuses = ['Available', 'Occupied', 'Cleaning', 'Maintenance'];
        if(empty($error_message)) { // Only add error if no other error exists
             $error_message = "Warning: Could not dynamically load room statuses. Using fallback list.";
        }
    }

    $conn->close(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Room <?php echo htmlspecialchars($room_no); ?></title>
    <link rel="stylesheet" href="../admin.css">
</head>
<body>

<?php 
    include "../component/navbar.php"; 
?>

<div class="dashboard-container">
    <?php 
        include "../component/sidebar.php"; 
    ?>

    <main class="content">
        <div class="content-header-row">
            <h1>Edit Room <?php echo htmlspecialchars($room_no); ?></h1>
            <div class="header-actions">
                <a href="rooms.php" class="btn btn-secondary">← Back to Rooms</a>
            </div>
        </div>
            
        <div id="ajax-message-area"> 
            <?php if (!empty($error_message)): ?>
                <div class="form-message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="form-message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
        </div>

        <?php if ($room && !empty($all_room_types)): // Only show form if data was fetched ?>
        <form action="edit_room.php" method="post">
            
            <div class="background-card" style="max-width: 1000px;">

                <input type="hidden" name="room_no" value="<?php echo htmlspecialchars($room['room_no']); ?>">

                <label for="room_no_display">Room No.</label>
                <input type="text" id="room_no_display" 
                       value="<?php echo htmlspecialchars($room['room_no']); ?>" 
                       title="Room number cannot be changed." disabled>

                <label for="type_id">Room Type<span>*</span></label>
                <select id="type_id" name="type_id" required>
                    <?php foreach ($all_room_types as $type): ?>
                        <option value="<?php echo $type['type_id']; ?>" 
                            <?php echo ($type['type_id'] == $room['type_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['type_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="status">Status<span>*</span></label>
                <select id="status-select" name="status" required>
                    <?php 
                        $current_status = $room['status'];
                    ?>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo htmlspecialchars($status); ?>"
                            <?php echo (strtolower($status) == strtolower($current_status)) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="form-submit-row">
                    <input type="submit" name="submit" value="Save Changes" class="btn btn-primary">
                </div>
            </div> 

        </form>
        <?php elseif (!$room): ?>
            <p>Room not found. Please go back and select a valid room.</p> 
        <?php else: ?>
            <p>Could not load room types. Please check database connection.</p>
        <?php endif; ?>

    </main>
</div>



</body>
</html>
```


