<?php
    require_once(__DIR__ . '/../auth_check.php');
    
    $error_message = "";
    $success_message = "";
    $all_room_types = []; 
    $statuses = []; 
    $form_data = []; 

    // --- Step 1: Handle Form Submission (INSERT) ---
    if (isset($_POST['submit'])) {
        $form_data = $_POST;

        // Sanitize all inputs
        $room_no = trim(filter_input(INPUT_POST, 'room_no', FILTER_SANITIZE_SPECIAL_CHARS));
        $type_id = filter_input(INPUT_POST, 'type_id', FILTER_SANITIZE_NUMBER_INT);
        $status = trim(filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS));

        // Validation
        if (empty($room_no) || empty($type_id) || empty($status)) {
            $error_message = "All fields are required.";
        } else {
            // --- Check for duplicate room_no ---
            $stmt_check = $conn->prepare("SELECT room_no FROM Rooms WHERE room_no = ?");
            $stmt_check->bind_param("s", $room_no);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $error_message = "A room with this number ('" . htmlspecialchars($room_no) . "') already exists.";
            } else {
                $query = "INSERT INTO Rooms (room_no, type_id, status) VALUES (?, ?, ?)";
                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sis", $room_no, $type_id, $status);

                if ($stmt->execute()) {
                    header("Location: rooms.php?status=success_add");
                    exit();
                } else {
                    $error_message = "Insert failed. Error: " . $stmt->error;
                }
                $stmt->close();
            }
            $stmt_check->close();
        }
    }

    // --- Step 2: Fetch Data for Form Dropdowns (SELECT) ---
    
    // 2a. Fetch ALL room types to populate the dropdown
    $query_types = "SELECT type_id, type_name FROM RoomTypes ORDER BY type_id";
    $types_result = $conn->query($query_types);
    if ($types_result) {
        while ($row = $types_result->fetch_assoc()) {
            $all_room_types[] = $row;
        }
    } else {
        $error_message .= " Could not fetch room types.";
    }
    
    // 2b. Fetch all possible Statuses from the database (ENUM)
    $query_enum = "SHOW COLUMNS FROM Rooms LIKE 'status'";
    $result_enum = $conn->query($query_enum);
    if ($result_enum && $result_enum->num_rows > 0) {
        $row_enum = $result_enum->fetch_assoc();
        $type = $row_enum['Type'];
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
    <title>Add New Room</title>
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
            <h1>Add New Room</h1>
            <div class="header-actions">
                <a href="rooms.php" class="btn btn-secondary">← Back to Rooms</a>
            </div>
        </div>
            
        <div> 
            <?php if (!empty($error_message)): ?>
                <div class="form-message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="form-message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($all_room_types)): // Only show form if room types were fetched ?>
        <form action="add_room.php" method="post">
            
            <div class="background-card" style="max-width: 100%;">

                <label for="room_no">Room No.<span>*</span></label>
                <input type="text" id="room_no" name="room_no" 
                       value="<?php echo htmlspecialchars($form_data['room_no'] ?? ''); ?>" 
                       placeholder="e.g., 101, 209, 305" required>

                <label for="type_id">Room Type<span>*</span></label>
                <select id="type_id" name="type_id" required>
                    <option value="" disabled <?php echo (!isset($form_data['type_id'])) ? 'selected' : ''; ?>>-- Select a room type --</option>
                    <?php 
                        $selected_type = $form_data['type_id'] ?? '';
                    ?>
                    <?php foreach ($all_room_types as $type): ?>
                        <option value="<?php echo $type['type_id']; ?>" 
                            <?php echo ($type['type_id'] == $selected_type) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['type_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="status">Status<span>*</span></label>
                <select id="status-select" name="status" required>
                    <?php 
                        $current_status = $form_data['status'] ?? 'Available';
                    ?>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo htmlspecialchars($status); ?>"
                            <?php echo (strtolower($status) == strtolower($current_status)) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="form-submit-row">
                    <input type="submit" name="submit" value="Add Room" class="btn btn-primary">
                </div>
            </div> 

        </form>
        <?php else: ?>
            <p>Could not load room types. Please check database connection or add a Room Type first.</p>
        <?php endif; ?>

    </main>
</div>


</body>
</html>
