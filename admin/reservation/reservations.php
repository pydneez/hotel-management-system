<?php
<<<<<<< HEAD
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Use __DIR__ to go up one level (to /admin/) and find auth_check.php
require_once(__DIR__ . '/../auth_check.php');
// Assume you have a db_connect.php file in your /admin/ folder
require_once(__DIR__ . '/../../connect.php');

// --- Handle Search ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql_where = " WHERE r.checkout_date >= CURDATE()"; // User requirement
$params = [];
$types = "";

if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    // Check if search query is numeric to also search by res_id
    if (is_numeric($search_query)) {
        $sql_where .= " AND (CONCAT(g.fname, ' ', g.lname) LIKE ? OR r.res_id = ?)";
        $params[] = $search_param;
        $params[] = $search_query;
        $types .= "si";
    } else {
        $sql_where .= " AND CONCAT(g.fname, ' ', g.lname) LIKE ?";
        $params[] = $search_param;
        $types .= "s";
    }
}

// --- Main Query ---
// This query now joins roomtypes directly from reservations.type_id
$sql = "
    SELECT 
        r.res_id, r.checkin_date, r.checkout_date, r.status, r.room_no, 
        r.type_id, 
        g.fname, g.lname, 
        rt.type_name
    FROM 
        reservations r
    JOIN 
        guests g ON r.guest_id = g.guest_id
    JOIN 
        roomtypes rt ON r.type_id = rt.type_id
    $sql_where
    ORDER BY 
        r.checkin_date ASC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
=======
    require_once(__DIR__ . '/../auth_check.php');

    // --- Check for success/error messages from actions ---
    $error_message = "";
    $success_message = "";
    if (isset($_GET['status'])) {
        if ($_GET['status'] === 'checkin_success') {
            $success_message = "Guest checked in successfully.";
        } elseif ($_GET['status'] === 'checkout_success') {
            $success_message = "Guest checked out successfully.";
        }
    }
    if (isset($_GET['error'])) {
        $error_message = htmlspecialchars(urldecode($_GET['error']));
    }
    if (isset($_GET['msg'])) {
        $success_message = htmlspecialchars(urldecode($_GET['msg']));
    }
    // --- End message check ---


    // --- 1. Handle Search ---
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sql_where = " WHERE r.status IN ('Confirmed', 'Checked-In')"; // Default filter
    $params = [];
    $types = "";

    if (!empty($search_query)) {
        $search_param = "%" . $search_query . "%";
        if (is_numeric($search_query)) {
            $sql_where .= " AND (CONCAT(g.fname, ' ', g.lname) LIKE ? OR r.res_id = ? OR r.room_no = ?)";
            $params[] = $search_param;
            $params[] = $search_query;
            $params[] = $search_query;
            $types .= "sis";
        } else {
            $sql_where .= " AND CONCAT(g.fname, ' ', g.lname) LIKE ?";
            $params[] = $search_param;
            $types .= "s";
        }
    }

    // --- 2. PAGINATION LOGIC ---
    $limit = 10; // 10 bookings per page
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;

    // Get total number of matching reservations
    $count_sql = "SELECT COUNT(r.res_id) as total 
                  FROM reservations r
                  JOIN guests g ON r.guest_id = g.guest_id
                  JOIN roomtypes rt ON r.type_id = rt.type_id
                  $sql_where";
                  
    $count_stmt = $conn->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $limit);
    $count_stmt->close();

    // Calculate the offset
    $offset = ($page - 1) * $limit;

    // --- 3. Main Query ---
    $sql = "
        SELECT 
            r.res_id, r.checkin_date, r.checkout_date, r.status, r.room_no, 
            r.type_id, 
            g.fname, g.lname, 
            rt.type_name
        FROM 
            reservations r
        JOIN 
            guests g ON r.guest_id = g.guest_id
        JOIN 
            roomtypes rt ON r.type_id = rt.type_id
        $sql_where
        ORDER BY 
            r.checkin_date ASC
        LIMIT ? OFFSET ?
    ";

    // Add pagination params to the list
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
>>>>>>> a9e9cbd (feat: reservation dashboard, walk-in reservation, check-in, check-out, all updated accordingly)

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Reservations</title>
    <link rel="stylesheet" href="../admin.css">
    <style>
        /* Page-specific styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }
        .search-form input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-form button {
            padding: 8px 16px;
        }

        /* Status badges */
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            color: #fff;
        }
        .status-confirmed { background-color: #3498db; }
        .status-checked-in { background-color: #2ecc71; }
        .status-checked-out { background-color: #95a5a6; }
        .status-pending { background-color: #f39c12; }
        .status-cancelled { background-color: #e74c3c; }

        /* Modal styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgb(0,0,0); 
            background-color: rgba(0,0,0,0.4); 
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .modal-content h2 {
            margin-top: 0;
        }
        .modal-content select, .modal-content button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
        .modal-content p {
            margin: 10px 0;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

<?php include "../component/navbar.php"; ?>

<div class="dashboard-container">
    <?php include "../component/sidebar.php"; ?>

    <main class="content">
<<<<<<< HEAD
        <div class="page-header">
=======
        <div class="content-header-row">
>>>>>>> a9e9cbd (feat: reservation dashboard, walk-in reservation, check-in, check-out, all updated accordingly)
            <h1>Reservations</h1>
            <a href="add_reservation.php" class="btn btn-primary">Add New Reservation</a>
        </div>

        <form method="GET" action="reservations.php" class="search-form">
<<<<<<< HEAD
            <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search Guest Name or Booking ID...">
            <button type="submit" class="btn">Search</button>
=======
            <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search Guest Name, Booking ID, or Room No...">
            <button type="submit" class="btn btn-primary">Search</button>
>>>>>>> a9e9cbd (feat: reservation dashboard, walk-in reservation, check-in, check-out, all updated accordingly)
        </form>

        <br>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Guest Name</th>
                    <th>Room Type</th>
                    <th>Room No.</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php 
<<<<<<< HEAD
                            $status_class = 'status-' . strtolower(str_replace(' ', '-', $row['status']));
=======
                            $status_text = htmlspecialchars($row['status']);
                            $status_class = 'status-default'; // Fallback
                            switch (strtolower($status_text)) {
                                case 'confirmed':
                                    $status_class = 'status-confirmed'; 
                                    break;
                                case 'pending':
                                    $status_class = 'status-pending'; 
                                    break;
                                case 'checked-in':
                                    $status_class = 'status-checked-in'; 
                                    break;
                                case 'checked-out':
                                    $status_class = 'status-checked-out'; 
                                    break;
                                case 'cancelled':
                                    $status_class = 'status-cancelled'; 
                                    break;
                            }
>>>>>>> a9e9cbd (feat: reservation dashboard, walk-in reservation, check-in, check-out, all updated accordingly)
                        ?>
                        <tr>
                            <td><?= $row['res_id'] ?></td>
                            <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?></td>
                            <td><?= htmlspecialchars($row['type_name']) ?></td>
                            <td><?= $row['room_no'] ? htmlspecialchars($row['room_no']) : 'N/A' ?></td>
                            <td><?= date('Y-m-d', strtotime($row['checkin_date'])) ?></td>
                            <td><?= date('Y-m-d', strtotime($row['checkout_date'])) ?></td>
<<<<<<< HEAD
                            <td><span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                            <td>
                                <?php if ($row['status'] == 'Confirmed'): ?>
                                    <button class="btn btn-checkin" onclick="openCheckinModal(
                                        <?= $row['res_id'] ?>,
                                        <?= $row['type_id'] ?>,
                                        '<?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?>',
                                        '<?= htmlspecialchars($row['type_name']) ?>'
=======
                            
                            <td><span class="status-badge <?= $status_class ?>"><?= $status_text ?></span></td>
                            <td>
                                <div class="action-buttons">
                                <?php if ($row['status'] == 'Confirmed' || $row['status'] == 'Pending'): ?>
                                    <button class="btn btn-checkin" onclick="openCheckinModal(
                                        <?= $row['res_id'] ?>,
                                        <?= $row['type_id'] ?>,
                                        '<?= htmlspecialchars(addslashes($row['fname'] . ' ' . $row['lname'])) ?>',
                                        '<?= htmlspecialchars(addslashes($row['type_name'])) ?>'
>>>>>>> a9e9cbd (feat: reservation dashboard, walk-in reservation, check-in, check-out, all updated accordingly)
                                    )">
                                        Check In
                                    </button>
                                <?php elseif ($row['status'] == 'Checked-In'): ?>
                                    <a href="reservation_action.php?action=checkout&res_id=<?= $row['res_id'] ?>&room_no=<?= htmlspecialchars($row['room_no']) ?>" 
                                       class="btn btn-checkout" 
                                       onclick="return confirm('Are you sure you want to check out this guest (Booking ID: <?= $row['res_id'] ?>)? This will set room <?= htmlspecialchars($row['room_no']) ?> to Cleaning.')">
                                        Check Out
                                    </a>
                                <?php endif; ?>
<<<<<<< HEAD
=======
                                </div>
>>>>>>> a9e9cbd (feat: reservation dashboard, walk-in reservation, check-in, check-out, all updated accordingly)
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
<<<<<<< HEAD
                        <td colspan="8">No reservations found matching your criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

=======
                        <td colspan="8" style="text-align: center;">No reservations found matching your criteria.</td>
                    </tr>
                <?php endif; ?>
                
                <?php 
                    // Show total count row only if there are results
                    if ($total_rows > 0) {
                        $start_record = $offset + 1;
                        $end_record = $offset + $result->num_rows;
                        
                        echo "<tr><td colspan='8' class='table-footer'>
                                Showing $start_record - $end_record of $total_rows total records
                            </td></tr>";
                    }
                ?>
            </tbody>
        </table>

        <div class="pagination-controls">
            <?php if ($total_pages > 1): ?>
                
                <?php 
                    $search_param_url = !empty($search_query) ? '&search=' . urlencode($search_query) : '';
                ?>

                <!-- Previous Button -->
                <?php if ($page > 1): ?>
                    <a href="reservations.php?page=<?php echo $page - 1; ?><?php echo $search_param_url; ?>" class="btn btn-secondary">Previous</a>
                <?php endif; ?>

                <!-- Numbered Links -->
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="reservations.php?page=<?php echo $i; ?><?php echo $search_param_url; ?>" 
                       class="btn <?php echo ($i == $page) ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <!-- Next Button -->
                <?php if ($page < $total_pages): ?>
                    <a href="reservations.php?page=<?php echo $page + 1; ?><?php echo $search_param_url; ?>" class="btn btn-secondary">Next</a>
                <?php endif; ?>

            <?php endif; ?>
        </div>

>>>>>>> a9e9cbd (feat: reservation dashboard, walk-in reservation, check-in, check-out, all updated accordingly)
    </main>
</div>

<div id="checkinModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeCheckinModal()">&times;</span>
        <h2>Assign Room for Check-in</h2>
        <form action="reservation_action.php?action=checkin_assign" method="POST">
            <input type="hidden" id="modal_res_id" name="res_id">
            
            <p><strong>Booking ID:</strong> <span id="modal_res_id_display"></span></p>
            <p><strong>Guest:</strong> <span id="modal_guest_name"></span></p>
            <p><strong>Room Type:</strong> <span id="modal_room_type"></span></p>

            <label for="modal_room_select"><strong>Assign Available Room:</strong></label>
            <select name="room_no" id="modal_room_select" required>
                <option value="">Loading rooms...</option>
            </select>
            
            <button type="submit" class="btn btn-primary">Assign and Check In</button>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('checkinModal');
    const modalResId = document.getElementById('modal_res_id');
    const modalResIdDisplay = document.getElementById('modal_res_id_display');
    const modalGuestName = document.getElementById('modal_guest_name');
    const modalRoomType = document.getElementById('modal_room_type');
    const modalRoomSelect = document.getElementById('modal_room_select');

    async function openCheckinModal(res_id, type_id, guestName, typeName) {
        // Populate modal with known info
        modalResId.value = res_id;
        modalResIdDisplay.textContent = res_id;
        modalGuestName.textContent = guestName;
        modalRoomType.textContent = typeName;
        modalRoomSelect.innerHTML = '<option value="">Loading available rooms...</option>';
        modal.style.display = 'block';

        try {
            // Fetch available rooms for this type_id
            const response = await fetch(`get_available_rooms.php?type_id=${type_id}`);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const rooms = await response.json();

            // Populate select dropdown
            modalRoomSelect.innerHTML = ''; // Clear loading message
            if (rooms.length > 0) {
                rooms.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.room_no;
                    option.textContent = room.room_no;
                    modalRoomSelect.appendChild(option);
                });
            } else {
                modalRoomSelect.innerHTML = '<option value="">No available rooms of this type.</option>';
                modalRoomSelect.disabled = true;
            }
        } catch (error) {
            console.error('Failed to fetch rooms:', error);
            modalRoomSelect.innerHTML = '<option value="">Error fetching rooms.</option>';
            modalRoomSelect.disabled = true;
        }
    }

    function closeCheckinModal() {
        modal.style.display = 'none';
        modalRoomSelect.disabled = false; // Re-enable in case it was disabled
    }

    // Close modal if user clicks outside of it
    window.onclick = function(event) {
        if (event.target == modal) {
            closeCheckinModal();
        }
    }
</script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>