<?php
    require_once('connect.php');

    if (isset($_POST['submit'])) {
        $fname = trim($_POST['fname']);
        $lname = trim($_POST['lname']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $cpassword = $_POST['cpassword']; 

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } elseif ($password !== $cpassword) {
            $error_message = "Passwords do not match.";
        } else {
            try {
                // Use a prepared statement to prevent SQL injection
                $check_email_query = "SELECT COUNT(*) FROM guests WHERE email = ?";
                $stmt_check = $conn->prepare($check_email_query);
                $stmt_check->bind_param("s", $email);
                $stmt_check->execute();
                $stmt_check->bind_result($email_count);
                $stmt_check->fetch();
                $stmt_check->close();

                if ($email_count > 0) {
                    $error_message = "An account with this email already exists.";
                } else {
                    // 4. Hash the password for security
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // 5. Insert the new guest data into the database
                    $insert_query = "INSERT INTO guests (first_name, last_name, phone_number, email, password) VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($insert_query);

                    // 's' for string, 'i' for integer, 'd' for double. Assuming phone is stored as a string.
                    $stmt_insert->bind_param("sssss", $fname, $lname, $phone, $email, $hashed_password);

                    if ($stmt_insert->execute()) {
                        // Registration successful, redirect the user to a welcome or login page
                        $success_message = "Registration successful! You can now log in.";
                        // For a real system, you'd use: header("Location: login.php"); exit();
                    } else {
                        // Error during database insertion
                        $error_message = "Error: Could not register user. Please try again. (" . $stmt_insert->error . ")";
                    }
                    $stmt_insert->close();
                }
            } catch (Exception $e) {
                // Handle any unexpected errors (e.g., connection issues)
                $error_message = "An unexpected error occurred: " . $e->getMessage();
            }
        }
    }
    // Close the connection at the end of the script execution (if not handled by connect.php)
    // $conn->close(); // Uncomment if your connect.php doesn't manage connection closing.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Registration | Hotel Management System</title>
    <link rel="stylesheet" href="default.css">
    
</head>
<body>
    

    <div id="">
        <div id="div_main">
            <div id="div_content" class="form">

                <div id="div_subhead" class = "center">
                    <h2>Registration</h2>
                </div>


                <?php if (!empty($error_message)): ?>
                    <p style="color: red; font-weight: bold;"><?php echo $error_message; ?></p>
                <?php elseif (!empty($success_message)): ?>
                    <p style="color: green; font-weight: bold;"><?php echo $success_message; ?></p>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <label for="fname">First Name <span style="color: red;">*</span></label>
                    <input type="text" name="fname" required> <br>

                    <label for="lname">Last Name <span style="color: red;">*</span></label>
                    <input type="text" name="lname" required> <br>

                    <label for="email">Email <span style="color: red;">*</span></label>
                    <input type="email" name="email" required> <br>

                    <label for="password">Password <span style="color: red;">*</span></label>
                    <input type="password"  name="password" required value = > <br>

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

    
</body>
</html>