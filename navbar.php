<?php
// Start session if not already started, to check login status
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="navbar-public">
    <div class="container">
        <a href="index.php" class="navbar-logo">
            <img src="/img/hotel_logo.png" alt="RoyalStay Hotel Logo"
                 onerror="this.style.display='none'"> <!-- Hide if logo fails -->
            RoyalStay Hotel
        </a>
        <nav class="navbar-links">
            <a href="index.php">Home</a>
            <a href="#rooms">Rooms</a>
            <a href="#footer">Contact</a>
            
            <?php 
            // Check if a user is logged in
            if (isset($_SESSION['email']) && isset($_SESSION['role'])):
                
                // Check if the user is a GUEST
                if ($_SESSION['role'] === 'guest'):
            ?>
                <a href="guest_dashboard.php">My Dashboard</a>
                <a href="logout.php" class="btn-primary">Logout</a>
            
            <?php 
                // Check if the user is STAFF (any role *other* than guest)
                else:
            ?>
                <a href="/admin/dashboard.php">Admin Dashboard</a>
                <a href="logout.php" class="btn-primary">Logout</a>
            
            <?php 
                endif;
            
            // User is NOT logged in
            else: 
            ?>
                <a href="login.php">Login</a>
                <a href="registration.php" class="btn-primary">Sign Up</a>
            <?php 
            endif; 
            ?>
        </nav>
    </div>
</header>