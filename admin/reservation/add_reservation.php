<?php
// (We need the ../auth_check.php path to go from /reservation to /admin)
require_once(__DIR__ . '/../auth_check.php');
// (We need the ../../connect.php path to go from /reservation to the root)
require_once(__DIR__ . '/../../connect.php');

// Fetch all room types to populate the dropdown
$sql_roomtypes = "SELECT type_id, type_name, base_price FROM roomtypes ORDER BY type_name";
$result_roomtypes = $conn->query($sql_roomtypes);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Add Reservation</title>
    <link rel="stylesheet" href="../admin.css">
    <style>
        /* Styles for a cleaner form */
        .form-container {
            max-width: 800px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
    </style>
</head>

<body>

    <?php include "../component/navbar.php"; ?>

    <div class="dashboard-container">
        <?php include "../component/sidebar.php"; ?>

        <main class="content">
            <div class="page-header">
                <h1>Add New Reservation (Walk-in)</h1>
                <a href="reservations.php" class="btn">&larr; Back to Reservations</a>
            </div>

            <div class="form-container">
                <form action="handle_add_reservation.php" method="POST" onsubmit="return validateDates()">

                    <h2>Guest Details</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="fname">First Name</label>
                            <input type="text" id="fname" name="fname" required>
                        </div>
                        <div class="form-group">
                            <label for="lname">Last Name</label>
                            <input type="text" id="lname" name="lname" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                    </div>

                    <hr style="margin: 20px 0;">

                    <h2>Reservation Details</h2>
                    <div class="form-grid">
                        <div class="form-group full-width"> <label for="type_id">Room Type</label>
                            <select id="type_id" name="type_id" required>
                                <option value="" disabled selected>-- Select a room type --</option>
                                <?php
                                if ($result_roomtypes->num_rows > 0) {
                                    while ($row = $result_roomtypes->fetch_assoc()) {
                                        echo sprintf(
                                            '<option value="%d">%s (Price: %s)</option>',
                                            $row['type_id'],
                                            htmlspecialchars($row['type_name']),
                                            htmlspecialchars($row['base_price'])
                                        );
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="num_adults">Adults</label>
                            <input type="number" id="num_adults" name="num_adults" min="1" value="1" required>
                        </div>

                        <div class="form-group">
                            <label for="num_children">Children</label>
                            <input type="number" id="num_children" name="num_children" min="0" value="0" required>
                        </div>
                        <div class="form-group">
                            <label for="checkin_date">Check-in Date</label>
                            <input type="date" id="checkin_date" name="checkin_date" required>
                        </div>

                        <div class="form-group">
                            <label for="checkout_date">Check-out Date</label>
                            <input type="date" id="checkout_date" name="checkout_date" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Create Reservation</button>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <script>
        function validateDates() {
            const checkin = document.getElementById('checkin_date').value;
            const checkout = document.getElementById('checkout_date').value;

            if (!checkin || !checkout) {
                alert("Please select both check-in and check-out dates.");
                return false;
            }

            if (checkout <= checkin) {
                alert("Check-out date must be after the check-in date.");
                return false;
            }
            return true;
        }

        // Set minimum check-in date to today
        document.addEventListener('DOMContentLoaded', function () {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('checkin_date').setAttribute('min', today);
            document.getElementById('checkout_date').setAttribute('min', today);
        });
    </script>

</body>

</html>
<?php
$conn->close();
?>