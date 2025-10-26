<?php
    require_once(__DIR__ . '/../auth_check.php');
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
            <h1>Manage Room Type</h1>
            <div class="content-header-row">
                <div class="page-toggle">
                    <a href = "rooms.php" id="toggleRoomTypes" class="toggle-button" data-target="physical_rooms.php">Room</a>
                    <a href = "room_types.php" id="toggleRoomAssignment" class="toggle-button active" data-target="room_types.php">Room Type</a>
                </div>
                
            
                <div class="header-actions">
                    <a href="add_roomtype.php" class="btn-primary">Add New Room Type</a>
                </div>
            </div>
           

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <!-- <th>Image</th> -->
                        <th>Name</th>
                        <th>Description</th>
                        <th>Base Price</th>
                        <th>Capacity</th>
                        <th>Amenities</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q = "SELECT type_id, type_name, description, base_price, capacity, amenities FROM RoomTypes ORDER BY type_id";
                    $result = $conn->query($q);
                    if (!$result) {
                        echo "<tr><td colspan='8'>Select failed. Error: " . $conn->error . "</td></tr>";
                    } else {
                        while ($row = $result->fetch_array()) { ?>
                        <tr>
                            <td><?php echo $row['type_id']; ?></td>
                            <!-- <td>
                                <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                                    alt="<?php echo htmlspecialchars($row['type_name']); ?>" 
                                    class="table-room-image"
                                    onerror="this.src='https://placehold.co/100x60/0a2342/f0f4f8?text=No+Image';">
                            </td> -->
                            <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>$<?php echo number_format($row['base_price'], 2); ?></td>
                            <td><?php echo $row['capacity']; ?></td>
                            <td><?php echo htmlspecialchars($row['amenities']); ?></td>
                            <td>
                                <!-- Fixed Action Icons -->
                                <div class="action-icons">

                                    <a href='edit_roomtype.php?id=<?php echo $row['type_id']; ?>'>
                                        <img src="/img/Modify.png" alt="Edit" width="24" height="24" title="Edit">
                                    </a>
                                    <a href='delinfo.php?id=<?php echo $row['type_id']; ?>'>
                                        <img src="/img/Delete.png" alt="Delete" width="24" height="24" title="Delete">
                                    </a>
                                </div>
                            </td>
                        </tr>                               
                        <?php }
                    } ?>
                    
                    <?php 
                    // Fixed Total row
                    $q = "SELECT count(*) as total FROM RoomTypes";
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


