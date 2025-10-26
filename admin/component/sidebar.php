<?php
    // Get the name of the current script (e.g., "dashboard.php")
    $current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="sidebar">
    <h3>Staff Menu</h3>
    <ul>
        <li>
            <a href="/admin/dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                🏨 Dashboard
            </a>
        </li>
        <li>
            <a href="/admin/reservation/checkin.php" class="<?php echo ($current_page == 'checkin.php') ? 'active' : ''; ?>">
                🛎️ Check-in
            </a>
        </li>
        <li>
            <a href="/admin/reservation/checkout.php" class="<?php echo ($current_page == 'checkout.php') ? 'active' : ''; ?>">
                🚪 Check-out
            </a>
        </li>
        <li>
            <a href="/admin/reservation/reservations.php" class="<?php echo ($current_page == 'reservations.php') ? 'active' : ''; ?>">
                📅 Reservations
            </a>
        </li>
        <li>
            <a href="/admin/room/rooms.php" class="<?php echo ($current_page == 'rooms.php' || $current_page == 'room_types.php'  ) ? 'active' : ''; ?>">
                🧹 Room
            </a>
        </li>
        <li>
            <a href="/admin/employee/employees.php" class="<?php echo ($current_page == 'employees.php') ? 'active' : ''; ?>">
                👥 Employees
            </a>
        </li>
    </ul>
</aside>

