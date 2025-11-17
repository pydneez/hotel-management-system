<?php
    require_once(__DIR__ . '/../auth_check.php');

    $error_message = "";
    $success_message = "";
    if (isset($_GET['status'])) {
        if ($_GET['status'] === 'checkout_success') {
            $success_message = "Guest checked out successfully.";
        }
    }
    if (isset($_GET['error'])) {
        $error_message = htmlspecialchars(urldecode($_GET['error']));
    }

    // --- 1. PAGINATION LOGIC ---
    $limit = 10; 
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    // Get total number of *checked-in* guests
    $count_stmt = $conn->prepare("SELECT COUNT(res_id) as total FROM reservations WHERE status = 'Checked-In'");
    $count_stmt->execute();
    $total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);
    $count_stmt->close();

    // Calculate the offset
    $offset = ($page - 1) * $limit;

    // --- 2. Main Query ---
    // Fetches all guests who are currently 'Checked-In' using the View
    $q = "SELECT 
            res_id, guest_name, room_no, type_name, 
            checkin_date, checkout_date, status,
            checkin_time
        FROM view_ReservationDetails
        WHERE status = 'Checked-In'
        ORDER BY checkout_date ASC, guest_name ASC
        LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($q);
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Check-Out</title>
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
        <h1>Check In / Out</h1>
        <div class="content-header-row">
            <div class="page-toggle">
                <a href="/admin/reservation/checkin.php" id="toggleRoomTypes" class="toggle-button">Check-In</a>
                <a href="/admin/reservation/checkout.php" id="toggleRoomAssignment" class="toggle-button active">Check-Out</a>
            </div>
        </div>


        <div id="ajax-message-area" style="margin-top: 1rem;"> 
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
                    <th>Guest Name</th>
                    <th>Room No.</th>
                    <th>Room Type</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!$result) {
                    echo "<tr><td colspan='7'>Select failed. Error: " . $conn->error . "</td></tr>";
                } elseif ($result->num_rows == 0) {
                     echo "<tr><td colspan='7' style='text-align:center;'>No guests are currently checked in.</td></tr>";
                } else {
                    while ($row = $result->fetch_assoc()) { 
                        $checkin_datetime = 'N/A'; // Default
                        if (!empty($row['checkin_time'])) {
                            // Format the timestamp
                            $checkin_datetime = date('Y-m-d H:i A', strtotime($row['checkin_time']));
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['guest_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['room_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                        <td><?php echo $checkin_datetime; // This is now safe ?></td>
                        <td><?php echo htmlspecialchars($row['checkout_date']); ?></td>
                        <td>
                            <span class="status-badge status-checked-in">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="reservation_action.php?action=checkout&res_id=<?php echo $row['res_id']; ?>&room_no=<?php echo htmlspecialchars($row['room_no']); ?>" 
                                   class="btn btn-checkout" 
                                   onclick="return confirm('Are you sure you want to check out <?php echo htmlspecialchars(addslashes($row['guest_name'])); ?> from Room <?php echo htmlspecialchars($row['room_no']); ?>?');">
                                    Check Out
                                </a>
                            </div>
                        </td>
                    </tr>                               
                    <?php }
                }
                $stmt->close();
                ?>
                
                <?php 
                    // Show total count row only if there are results
                    if ($total_rows > 0) {
                        $start_record = $offset + 1;
                        $end_record = $offset + $result->num_rows;
                        
                        echo "<tr><td colspan='7' class='table-footer'>
                                Showing $start_record - $end_record of $total_rows total records
                            </td></tr>";
                    }
                ?>
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="pagination-controls">
            <?php if ($total_pages > 1): ?>
                <?php if ($page > 1): ?>
                    <a href="checkout.php?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="checkout.php?page=<?php echo $i; ?>" 
                       class="btn <?php echo ($i == $page) ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="checkout.php?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- REMOVED: Check-in modal and script, as they are not needed on this page -->

</body>
</html>
<?php
$conn->close();
?>