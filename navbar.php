<?php
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

        <nav class="navbar-links-desktop">
            <a href="index.php">Home</a>
            <a href="search_results.php">Rooms</a>
            
            <?php 
            if (isset($_SESSION['email']) && isset($_SESSION['role'])):
                if ($_SESSION['role'] === 'guest'):
            ?>
                <a href="dashboard.php">My Dashboard</a>
                <a href="logout.php" class="btn-primary">Logout</a>
            <?php else: // Staff ?>
                <a href="/admin/dashboard.php">Admin Dashboard</a>
                <a href="logout.php" class="btn-primary">Logout</a>
            <?php endif;
            // User is NOT logged in
            else: 
            ?>
                <a href="login.php">Login</a>
                <a href="registration.php" class="btn-primary">Sign Up</a>
            <?php endif; ?>
        </nav>

        <!-- 2. Mobile Menu "Hamburger" Button (Hidden on desktop) -->
        <button id="mobile-menu-toggle" class="mobile-menu-toggle">
            <span></span>
            <span></span>
            <span></span>
        </button>

    </div>
</header>

    <!-- 3. Mobile Menu Overlay (Hidden by default) -->
    <div id="mobile-menu-overlay" class="mobile-menu-overlay">
        <nav class="mobile-menu-links">
            <a href="index.php">Home</a>
            <a href="search_results.php">Rooms</a>
            
            <?php 
            // Check if a user is logged in
            if (isset($_SESSION['email']) && isset($_SESSION['role'])):
                if ($_SESSION['role'] === 'guest'):
            ?>
                <a href="dashboard.php">My Dashboard</a>
                <a href="logout.php">Logout</a>
            <?php else: // Staff ?>
                <a href="/admin/dashboard.php">Admin Dashboard</a>
                <a href="logout.php">Logout</a>
            <?php endif;
            // User is NOT logged in
            else: 
            ?>
                <a href="login.php">Login</a>
                <a href="registration.php">Sign Up</a>
            <?php endif; ?>
        </nav>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu-overlay');

        if (toggleButton && mobileMenu) {
            toggleButton.addEventListener('click', function() {
                // Toggle 'active' class on button for "X" animation
                this.classList.toggle('active');
                // Toggle 'open' class on menu to show/hide it
                mobileMenu.classList.toggle('open');
            });
        }
    });
</script>