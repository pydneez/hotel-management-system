<?php
require_once('auth_check.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<?php include "component/navbar.php"; ?>

<div class="dashboard-container">
    <?php include "component/sidebar.php"; ?>

    <main class="content">
        <section class="dashboard-widgets">
            <div class="card">🏨 Active Bookings<br><span>32</span></div>
            <div class="card">🚪 Available Rooms<br><span>15</span></div>
            <div class="card">🧾 Pending Payments<br><span>5</span></div>
            <div class="card">👥 Staff On Duty<br><span>8</span></div>
        </section>
    </main>
</div>

</body>
</html>
