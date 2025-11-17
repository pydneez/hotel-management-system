<?php
    require_once(__DIR__ . '/../auth_check.php');
    $sql_roomtypes = "SELECT type_id, type_name, base_price FROM RoomTypes ORDER BY type_name";
    $result_roomtypes = $conn->query($sql_roomtypes);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Add Reservation</title>
    <link rel="stylesheet" href="../admin.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
</head>

<body>
    <?php include "../component/navbar.php"; ?>

    <div class="dashboard-container">
        <?php include "../component/sidebar.php"; ?>

        <main class="content">
            <div class="content-header-row">
                <h1>Add New Reservation (Walk-in)</h1>
                
                <div class="header-actions">
                    <a href="reservations.php" class="btn btn-secondary">&larr; Back to Reservations</a>
                </div>
            </div>  
            
            <form action="handle_add_reservation.php" method="post" onsubmit="return validateDates()">
                
                <div id="ajax-message-area">
                    <?php if (!empty($_GET['error'])): ?>
                        <div class="form-message error"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-layout-grid"  style= "grid-template-columns: 1fr 1fr;">
                
                    <div class="background-card">
                        <h2>Guest Details</h2>
                        <label for="fname">First Name<span>*</span></label>
                        <input type="text" id="fname" name="fname" required>

                        <label for="lname">Last Name<span>*</span></label>
                        <input type="text" id="lname" name="lname" required>

                        <label for="email">Email<span>*</span></label>
                        <input type="email" id="email" name="email" required>

                        <label for="phone">Phone<span>*</span></label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>

                    <div class="background-card">
                        <h2>Reservation Details</h2>
                        <label for="type_id">Room Type<span>*</span></label>
                        <select id="type_id" name="type_id" required>
                            <option value="" disabled selected>-- Select a room type --</option>
                            <?php
                            if ($result_roomtypes && $result_roomtypes->num_rows > 0) {
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

                        <label for="num_adults">Adults<span>*</span></label>
                        <input type="number" id="num_adults" name="num_adults" min="1" value="1" required>


                        <label for="num_children">Children<span>*</span></label>
                        <input type="number" id="num_children" name="num_children" min="0" value="0" required>

                        <div class="form-layout-grid"  style= "grid-template-columns: 1fr 1fr; margin-bottom:1rem;">
                            <div>
                                <label for="checkin_date">Check-in Date<span>*</span></label>
                                <input type="date" id="checkin_date" name="checkin_date" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div>
                                <label for="checkout_date">Check-out Date<span>*</span></label>
                                <input type="date" id="checkout_date" name="checkout_date" required>
                            </div>
                        </div>
                        
                        <label>Payment Option<span>*</span></label>
                        <div class="form-layout-grid" style= "grid-template-columns: 1fr 1fr;">
                            <div>
                                <input type="radio" name="payment_method" value="Credit Card" checked> Credit / Debit Card
                            </div>
                            <div>
                                <input type="radio" name="payment_method" value="Cash">Pay with Cash 
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-submit-row">
                    <button type="submit" class="btn btn-primary">Create Reservation</button>
                </div>
            </form>
            

        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script>
      const phoneInput = document.querySelector("#phone");
    
      const iti = window.intlTelInput(phoneInput, {
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        
        initialCountry: "auto", 
        geoIpLookup: function(callback) {
          fetch("https://ipapi.co/json")
            .then(res => res.json())
            .then(data => callback(data.country_code))
            .catch(() => callback("us"));
        },
        hiddenInput: "phone",
    
        separateDialCode: true,
      });

      if (phoneInput.value.trim()) {
        iti.setNumber(phoneInput.value);
      }
    </script>

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
