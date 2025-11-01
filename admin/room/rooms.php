<?php
    require_once(__DIR__ . '/../auth_check.php');
    
    $success_message = "";
    $error_message = ""; 

    // Now, populate them if the URL parameter exists
    if (isset($_GET['status']) && $_GET['status'] === 'success1') {
        $success_message = "New room type has been added successfully";
    } 
    if (isset($_GET['status']) && $_GET['status'] === 'success2') {
        $success_message = "Room type has been edited successfully";
    }

    // --- PAGINATION LOGIC ---
    $limit = 10;

    // current page number from URL, default to 1
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) {
        $page = 1;
    }

    // 3. Get the total number of rooms
    $count_result = $conn->query("SELECT COUNT(*) as total FROM Rooms");
    $total_rows = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit); // Calculate total pages

    // 4. Calculate the offset (how many rows to skip)
    $offset = ($page - 1) * $limit;

    // --- END PAGINATION LOGIC ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management</title>
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
        <h1>Manage Room</h1>
        <div class="content-header-row">
            <div class="page-toggle">
                <a href="/admin/room/rooms.php" id="toggleRoomTypes" class="toggle-button active">Room</a>
                <a href="/admin/room/room_types.php" id="toggleRoomAssignment" class="toggle-button">Room Type</a>
            </div>
            
            <div class="header-actions">
                <a href="/admin/room/add_room.php" class="btn-primary">Add New Physical Room</a>
            </div>  
        </div>

        <!-- Success/Error Message Area -->
        <div id="ajax-message-area"> 
            <?php if (!empty($error_message)): ?>
                <div class="form-message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="form-message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Room No.</th>
                    <th>Room Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $q = "SELECT Rooms.*, RoomTypes.type_name 
                      FROM Rooms 
                      JOIN RoomTypes ON Rooms.type_id = RoomTypes.type_id 
                      ORDER BY room_no
                      LIMIT ? OFFSET ?";
                
                $stmt = $conn->prepare($q);
                // "ii" -> binding two integers
                $stmt->bind_param("ii", $limit, $offset);
                $stmt->execute();
                $result = $stmt->get_result();

                if (!$result) {
                    echo "<tr><td colspan='4'>Select failed. Error: " . $conn->error . "</td></tr>";
                } elseif ($result->num_rows == 0) {
                     echo "<tr><td colspan='4' style='text-align:center;'>No rooms found.</td></tr>";
                } else {
                    while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['room_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                        <td>
                            <?php 
                            $status_text = htmlspecialchars($row['status']);
                            $status_class = '';
                            
                            // Use strtolower for a case-insensitive match
                            switch (strtolower($status_text)) {
                                case 'available':
                                    $status_class = 'status-available';
                                    break;
                                case 'cleaning':
                                    $status_class = 'status-cleaning';
                                    break;
                                case 'occupied':
                                    $status_class = 'status-occupied';
                                    break;
                                case 'maintenance':
                                    $status_class = 'status-maintenance';
                                    break;
                                default:
                                    $status_class = 'status-default'; // Fallback style
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-icons">
                                <a href='/admin/room/edit_room.php?room_no=<?php echo $row['room_no']; ?>'>
                                    <img src="/img/Modify.png" alt="Edit" width="24" height="24" title="Edit">
                                </a>
                                <a href='/admin/room/delete_room.php?room_no=<?php echo $row['room_no']; ?>' onclick="return confirm('Are you sure you want to delete this room?');">
                                    <img src="/img/Delete.png" alt="Delete" width="24" height="24" title="Delete">
                                </a>
                            </div>
                        </td>
                    </tr>                               
                    <?php }
                }
                $stmt->close();
                ?>
                
                <?php 
                    $start_record = $offset + 1;
                    $end_record = $offset + $result->num_rows;
                    
                    echo "<tr><td colspan='4' class='table-footer'>
                            Showing $start_record - $end_record of $total_rows total records
                        </td></tr>";
                ?>
            </tbody>

        </table>

        <!-- === NEW PAGINATION CONTROLS === -->
        <div class="pagination-controls">
            <?php if ($total_pages > 1): // Only show controls if there is more than one page ?>

                <!-- Previous Button -->
                <?php if ($page > 1): ?>
                    <a href="/admin/room/rooms.php?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                <?php endif; ?>

                <!-- Numbered Links -->
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="/admin/room/rooms.php?page=<?php echo $i; ?>" 
                       class="btn <?php echo ($i == $page) ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- Next Button -->
                <?php if ($page < $total_pages): ?>
                    <a href="/admin/room/rooms.php?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                <?php endif; ?>

            <?php endif; ?>
        </div>


    </main>
</div>

</body>
</html>

