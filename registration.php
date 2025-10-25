<?php
    require_once('connect.php'); 
    
    // --- Helper Functions ---

    /**
     * Checks if two passwords match.
     */
    function checkMatchingPassword($password, $cpassword) {
        return $password === $cpassword;
    }

    /**
     * Checks if an email already exists in the guests table.
     */
    function checkExistingGuest($conn, $email) {
        $count = 0;
        $query = "SELECT COUNT(*) FROM guests WHERE email = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return false; // Handle prepare error
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return $count > 0;
    }

    function checkPasswordPattern($password){
        return preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/", trim($password)) === 1;
    }

    /**
     * Adds a new guest to the database.
     * Returns true on success, or an error message string on failure.
     */
    function addGuest($conn, $fname, $lname, $email, $phone, $password) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        $query = "INSERT INTO guests (fname, lname, email, password_hash, phone) VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return "Database error: Could not prepare statement.";
        }
        $stmt->bind_param("sssss", $fname, $lname, $email, $hashedPassword, $phone);

        if ($stmt->execute()) {
            $stmt->close();
            return true; 
        } else {
            // Handle potential duplicate entry error
            // 1062 is the MySQL error code for duplicate entry
            if ($stmt->errno == 1062) {
                $error_msg = "An account with this email already exists.";
            } else {
                $error_msg = "Error: " . $stmt->error;
            }
            $stmt->close();
            return $error_msg;
        }
    }


    // --- Form Processing ---

    $error_message = "";
    $success_message = "";

    if (isset($_POST['submit'])) {

        // 1. Data Sanitization 
        $fname = trim(filter_input(INPUT_POST, 'fname', FILTER_SANITIZE_SPECIAL_CHARS));
        $lname = trim(filter_input(INPUT_POST, 'lname', FILTER_SANITIZE_SPECIAL_CHARS));   
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        
        $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_SPECIAL_CHARS));

        $password = $_POST['password'];
        $cpassword = $_POST['cpassword'];


        // 2. Data Validation
        
        // Check for empty fields
        if (empty($fname) || empty($lname) || empty($email) || empty($phone) || empty($password) || empty($cpassword)) {
            $error_message = "All fields marked with * are required.";
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format. Please enter a valid email address.";
        }
        elseif (!preg_match('/^\+\d{7,15}$/', $phone)) {
            $error_message = "Invalid phone number. Please select your country and enter a valid number.";
        }
        
        elseif (!checkPasswordPattern($password)) {
            $error_message = "Password must be at least 6 characters long and contain at least one letter and one number.";
        }
        elseif (!checkMatchingPassword($password, $cpassword)) {
             $error_message = "Passwords do not match.";
        }
        elseif (checkExistingGuest($conn, $email)) {
             $error_message = "An account with this email already exists.";
        }
        
        // 3. Process Data (All checks passed)
        else {
            $result = addGuest($conn, $fname, $lname, $email, $phone, $password);
            
            if ($result === true) {
                $success_message = "Registration successful! You can now log in.";
                header("Location: login.php?status=success");
                exit;
            } else {
                $error_message = $result; 
            }
        }
    }
    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Registration | Hotel Management System</title>
    <link rel="stylesheet" href="style.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css"/>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    
    <div id="">
        <div id="div_main">
            <div id="div_content" class="form">

                <div id="div_subhead" class = "center">
                    <h2>Registration</h2>
                </div>

                <?php if (!empty($error_message)): ?>
                    <p style="color: red; font-weight: bold; text-align: center;"><?php echo $error_message; ?></p>
                <?php elseif (!empty($success_message)): ?>
                    <p style="color: green; font-weight: bold; text-align: center;"><?php echo $success_message; ?></p>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <label for="fname">First Name <span style="color: red;">*</span></label>
                    <input type="text" name="fname" required value="<?php echo isset($fname) ? htmlspecialchars($fname) : ''; ?>"> <br>

                    <label for="lname">Last Name <span style="color: red;">*</span></label>
                    <input type="text" name="lname" required value="<?php echo isset($lname) ? htmlspecialchars($lname) : ''; ?>"> <br>

                    <label for="email">Email <span style="color: red;">*</span></label>
                    <input type="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"> <br>

                    <label for="phone">Phone Number <span style="color: red;">*</span></label>
                    <input type="tel"  name="phone" id="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" required> <br>

                    <label for="password">Password <span style="color: red;">*</span></label>
                    <input type="password"  name="password" required 
                        pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$"
                        title="Password must be 6+ chars, with at least one letter and one number"> <br>
                    
                    <label for="cpassword">Confirm Password <span style="color: red;">*</span></label>
                    <input type="password"  name="cpassword" required> <br>
                    
                    <div class="center">
                        <input type="submit" name="submit" value="Register">
                    </div>

                </form> 
                <p class="center">
                    Already have an account? <a href="login.php">Log In here</a>
                </p>

            </div>
        </div>
    
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    
    <script>
      const phoneInput = document.querySelector("#phone");
    
      //Initialize the library
      const iti = window.intlTelInput(phoneInput, {
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        
        // Automatically selects the user's country based on their IP
        initialCountry: "auto", 
        geoIpLookup: function(callback) {
          fetch("https://ipapi.co/json")
            .then(res => res.json())
            .then(data => callback(data.country_code))
            .catch(() => callback("us"));
        },
        // It creates a hidden input with name="phone"
        // and fills it with the full international number.
        hiddenInput: "phone",
        
        // Puts the country code in the input
        separateDialCode: true,
      });

      // when page reloads.
      // We want to re-format the "sticky" number (e.g., +15551234567)
      // that PHP put back into the 'value' attribute.
      if (phoneInput.value.trim()) {
        iti.setNumber(phoneInput.value);
      }
    </script>
    
    <?php
        if (isset($conn)) {
            $conn->close();
        }
    ?>
</body>
</html>