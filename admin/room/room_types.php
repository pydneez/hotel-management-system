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
        <h1>Room Type management</h1>
        
    </main>
</div>

</body>
</html>
