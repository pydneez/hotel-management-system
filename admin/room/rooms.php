<?php
    // Use __DIR__ to go up one level (to /admin/) and find auth_check.php
    require_once(__DIR__ . '/../auth_check.php');
    // We'll need the $conn variable, so make sure auth_check requires connect.php
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
                <a href="rooms.php" id="toggleRoomTypes" class="toggle-button active" data-target="physical_rooms.php">Room</a>
                <a href="room_types.php" id="toggleRoomAssignment" class="toggle-button" data-target="room_types.php">Room Type</a>
            </div>
            
            <div class="header-actions">
                <a href="add_room.php" class="btn-primary">Add New Physical Room</a>
            </div>
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
                $q = "SELECT * FROM Rooms join RoomTypes on Rooms.type_id = RoomTypes.type_id ORDER BY room_no";
                $result = $conn->query($q);
                if (!$result) {
                    echo "<tr><td colspan='8'>Select failed. Error: " . $conn->error . "</td></tr>";
                } else {
                    while ($row = $result->fetch_array()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['room_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>
                            <div class="action-icons">
                                <a href='edit_room.php?id=<?php echo $row['room_no']; ?>'>
                                    <img src="/img/Modify.png" alt="Edit" width="24" height="24" title="Edit">
                                </a>
                                <a href='delinfo.php?id=<?php echo $row['room_no']; ?>'>
                                    <img src="/img/Delete.png" alt="Delete" width="24" height="24" title="Delete">
                                </a>
                            </div>
                        </td>
                    </tr>                               
                    <?php }
                } ?>
                
                <?php 
                    $q = "SELECT count(*) as total FROM Rooms";
                    $count = $conn->query($q);
                    if ($count) {
                        $countRow = $count->fetch_assoc();
                        echo "<tr><td colspan='8' class='table-footer'>Total " . $countRow['total'] . " records</td></tr>";
                    }
                ?>
            </tbody>

        </table>


    </main>
</div>

</body>
</html>

