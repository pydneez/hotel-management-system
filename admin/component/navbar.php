<?php
    $welcome_name = htmlspecialchars($employee['fname'] ?? 'Staff');
?>

<header class="navbar">
    <div class="nav-left">
        <img src="/img/hotel_logo.png" alt="Hotel Logo" class="logo">
        <h2>RoyalStay Hotel</h2>
    </div>

    <div class="nav-right">
        <span>Welcome, <?php echo $welcome_name; ?>!</span>
        <a href="../../logout.php" class="logout-btn">Logout</a>
    </div>
</header>
