<?php
    require_once('connect.php'); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to RoyalStay Hotel | Book Your Stay</title>
    <link rel="stylesheet" href="index.css"> 
</head>
<body>

    <?php include "navbar.php"; ?>
    
    <main>
        <div class="hero-section">
            <div class="container">
                <h1>Experience Unforgettable Stays</h1>
                <p>Book your perfect room at RoyalStay Hotel today.</p>
                
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
                            <input type="submit" value="Search Rooms" class="btn-primary">
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php 
            include "show_room.php"; 
        ?>
    </main>
    
    <?php include "footer.php"; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkIn = document.getElementById('check_in');
            const checkOut = document.getElementById('check_out');
            
            const today = new Date().toISOString().split('T')[0];
            checkIn.setAttribute('min', today);

            checkIn.addEventListener('change', function() {
                if (checkIn.value) {
                    let nextDay = new Date(checkIn.value);
                    nextDay.setDate(nextDay.getDate() + 1);
                    const minCheckout = nextDay.toISOString().split('T')[0];
                    checkOut.setAttribute('min', minCheckout);
                    // Also set checkout value if it's no longer valid
                    if (checkOut.value < minCheckout) {
                        checkOut.value = minCheckout;
                    }
                }
            });
        });
    </script>

</body>
</html>
