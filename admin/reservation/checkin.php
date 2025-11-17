<?php
    require_once(__DIR__ . '/../auth_check.php');

    // --- Check for success/error messages from actions ---
    $error_message = "";
    $success_message = "";
    if (isset($_GET['status'])) {
        if ($_GET['status'] === 'checkin_success') {
            $success_message = "Guest checked in successfully.";
        }
    }
    if (isset($_GET['error'])) {
        $error_message = htmlspecialchars(urldecode($_GET['error']));
    }
    // --- End message check ---

    // --- 1. PAGINATION LOGIC ---
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

    // --- 2. Main Query ---
    // Fetches all guests who are 'Confirmed' or 'Pending' and are due for check-in
    $q = "SELECT 
            res_id, guest_name, type_name,
            checkin_date, checkout_date, status, type_id
        FROM view_ReservationDetails
        WHERE status = 'Confirmed' AND checkin_date <= CURDATE()
        ORDER BY checkin_date ASC, guest_name ASC
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
    <title>Dashboard | Check-In</title>
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
                    <a href="/admin/reservation/checkin.php" id="toggleRoomTypes" class="toggle-button active">Check-In</a>
                    <a href="/admin/reservation/checkout.php" id="toggleRoomAssignment" class="toggle-button">Check-Out</a>
                </div>
                <div class="header-actions">
                    <a href="/admin/reservation/add_reservation.php" class="btn-primary">Add New Reservation</a>
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
                        echo "<tr><td colspan='6'>Select failed. Error: " . $conn->error . "</td></tr>";
                    } elseif ($result->num_rows == 0) {
                        echo "<tr><td colspan='6' style='text-align:center;'>No guests are pending check-in.</td></tr>";
                    } else {
                        while ($row = $result->fetch_assoc()) { 
                            $status_text = htmlspecialchars($row['status']);
                            $status_class = (strtolower($status_text) == 'confirmed' || strtolower($status_text) == 'pending') ? 'status-available' : 'status-default';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['guest_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['type_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['checkin_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['checkout_date']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-checkin" onclick="openCheckinModal(
                                        <?= $row['res_id'] ?>,
                                        <?= $row['type_id'] ?>,
                                        '<?= htmlspecialchars(addslashes($row['guest_name'])) ?>',
                                        '<?= htmlspecialchars(addslashes($row['type_name'])) ?>'
                                    )">
                                        Check In
                                    </button>
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
                            
                            echo "<tr><td colspan='6' class='table-footer'>
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
                        <a href="checkin.php?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="checkin.php?page=<?php echo $i; ?>" 
                        class="btn <?php echo ($i == $page) ? 'btn-primary' : 'btn-secondary'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="checkin.php?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

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
$conn->close();
?>