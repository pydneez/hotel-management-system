<?php
    // Use __DIR__ to go up one level (to /admin/) and find auth_check.php
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
        <h1>Room Management</h1>
        <p>This is where you will add, edit, and view all hotel rooms.</p>

        <section class="dashboard-widgets">
            <div class="card">
                <h3>Total Rooms</h3>
                <span>150</span>
            </div>
            <div class="card">
                <h3>Occupied</h3>
                <span>110</span>
            </div>
            <div class="card">
                <h3>Needs Cleaning</h3>
                <span>12</span>
            </div>
            <div class="card">
                <h3>Out of Order</h3>
                <span>2</span>
            </div>
        </section>

        <div class="card" style="margin-top: 2rem; text-align: left;">
            <h2>Room List</h2>
            <p>A table of all rooms would go here...</p>
        </div>
    </main>
</div>

</body>
</html>
