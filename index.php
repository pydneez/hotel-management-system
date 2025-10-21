<?php
    // Optional: Include your database connection if you plan to dynamically load data here
    // require_once('connect.php'); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to [Hotel Name] | Book Your Stay</title>
    <link rel="stylesheet" href="default.css">
    <style>
        </style>
</head>
<body>

    <?php include "navbar.php"?>
    <div class="hero-section">
        <h1>Experience Unforgettable Stays at [Hotel Name]</h1>
        <p>Book your perfect room now.</p>
        
        <div class="availability-checker">
            <h2>Check Availability</h2>
            <form action="search_results.php" method="GET">
                <div>
                    <label for="check_in">Check-in Date</label>
                    <input type="date" id="check_in" name="check_in" required>
                </div>
                <div>
                    <label for="check_out">Check-out Date</label>
                    <input type="date" id="check_out" name="check_out" required>
                </div>
                <div>
                    <label for="adults">Adults</label>
                    <input type="number" id="adults" name="adults" min="1" value="1" required>
                </div>
                <div>
                    <label for="children">Children</label>
                    <input type="number" id="children" name="children" min="0" value="0">
                </div>
                <div>
                    <label>&nbsp;</label>
                    <input type="submit" value="Search Rooms">
                </div>
            </form>
        </div>
    </div>

    <?php include "show_room.php" ?>

    <footer style="text-align: center; padding: 20px; background-color: #333; color: white;">
        <p>&copy; <?php echo date("Y"); ?> [Hotel Name]. All rights reserved.</p>
    </footer>

</body>
</html>s