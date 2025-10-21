<aside class="sidebar">
        <section class="profile-card">
            <h2>Welcome!</h2>
            <p><strong>Role:</strong> <?php echo htmlspecialchars($employee['role']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        </section>
        <ul>
            <li><a href="dashboard.php">🏨 Dashboard</a></li>
            <li><a href="checkin.php">🛎️ Check-in</a></li>
            <li><a href="checkout.php">🚪 Check-out</a></li>
            <li><a href="reservations.php">📅 Reservations</a></li>
            <li><a href="rooms.php">🧹 Room Management</a></li>
            <li><a href="employees.php">👥 Employees</a></li>
        </ul>
 </aside>