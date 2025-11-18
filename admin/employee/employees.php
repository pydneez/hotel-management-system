<?php
    require_once(__DIR__ . '/../auth_check.php');

    $limit = 10; // 10 bookings per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    // Get total number of guests waiting for check-in
    $count_sql = "SELECT COUNT(res_id) as total 
                  FROM reservations
                  WHERE status = 'Confirmed' AND checkin_date <= CURDATE()";
                  
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute();
    $total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);
    $count_stmt->close();

    // Calculate the offset
    $offset = ($page - 1) * $limit;
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Employee Management</title>
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
                <h1>Manage Employee</h1>
                <div class="content-header-row"> 
                    <div class="">
                    </div>        

                    <div class="header-actions">
                        <a href="add_employee.php" class="btn-primary">Add New Employee</a>
                    </div>
                </div>
            

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = "SELECT emp_id, fname, lname, email, role FROM Employees ORDER BY emp_id";
                        $result = $conn->query($q);
                        if (!$result) {
                            echo "<tr><td colspan='6'>Select failed. Error: " . $conn->error . "</td></tr>";
                        } else {
                            while ($row = $result->fetch_array()) { ?>
                            <tr>
                                <td><?php echo $row['emp_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['fname']); ?></td>
                                <td><?php echo htmlspecialchars($row['lname']); ?></td>
                                <td><?php echo $row['email']; ?></td>
                                <td><?php echo htmlspecialchars($row['role']); ?></td>
                                <td>

                                    <div class="action-icons">
                                        <a href='edit_employeee.php?id=<?php echo $row['emp_id']; ?>'>
                                            <img src="/img/Modify.png" alt="Edit" width="24" height="24" title="Edit">
                                        </a>
                                        <a href='delete_employee.php?id=<?php echo $row['emp_id']; ?>' onclick="return confirm('Are you sure you want to delete this employee?');">
                                            <img src="/img/Delete.png" alt="Delete" width="24" height="24" title="Delete">
                                        </a>
                                    </div>

                                </td>
                            </tr>                               
                            <?php }
                        } ?>
                        
                        <?php 
                            $q = "SELECT count(*) as total FROM Employees";
                            $count = $conn->query($q);
                            if ($count) {
                                $countRow = $count->fetch_assoc();
                                echo "<tr><td colspan='6' class='table-footer'>Total " . $countRow['total'] . " records</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
                            
                <!-- Pagination Controls -->
                <div class="pagination-controls">
                    <?php if ($total_pages > 1): ?>
                        <?php if ($page > 1): ?>
                            <a href="employees.php?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="employees.php?page=<?php echo $i; ?>" 
                            class="btn <?php echo ($i == $page) ? 'btn-primary' : 'btn-secondary'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="employees.php?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>


            </main>
        </div>

    </body>
</html>
