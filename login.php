<?php
    session_start(); // Must be at the very top
    require_once('connect.php'); 

    // --- Helper Function ---

    function authenticateUser($conn, $email, $password) {
        $user_data = false;
        
        // Check if it's an employee email (ends with @hms.com)
        if (substr($email, -8) === '@hms.com') {
            $sql = "SELECT password_hash, role FROM Employees WHERE email = ?";
            $default_role = null; // Role will come from DB
        } else {
            $sql = "SELECT password_hash FROM Guests WHERE email = ?";
            $default_role = "guest"; // Guests get a 'guest' role
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
             return false; // DB error
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        // Verify user exists and password is correct
        if ($user && password_verify($password, $user['password_hash'])) {
            $user_data = [
                'email' => $email,
                'role' => $default_role ?? $user['role'] // Use 'guest' or the DB role
            ];
        }

        $stmt->close();
        return $user_data;
    }


    // --- Form Processing ---

    $email = "";
    $error_message = "";
    $success_message = ""; 

    //  success message from the registration page
    if (isset($_GET['status']) && $_GET['status'] === 'success') {
        $success_message = "Registration successful! You can now log in.";
    }

    if (isset($_POST['submit'])) {
        $success_message = ""; 
        
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } else {
            // Call the helper function to authenticate
            $userData = authenticateUser($conn, $email, $password);

            if ($userData) {
                $_SESSION['email'] = $userData['email'];
                $_SESSION['role'] = $userData['role'];

                // Critical: Redirect based on role
                if ($userData['role'] === 'guest') {
                    header("Location: dashboard.php"); // Guest dashboard
                } else {
                    header("Location: admin/dashboard.php"); // Admin/Employee dashboard
                }
                exit();

                
            } else {
                // FAILURE: Generic error message for security
                $error_message = "Incorrect email or password.";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Portal | Hotel Management System</title>
        <link rel="stylesheet" href="style.css">

    </head>
    <body>
        <div id="div_main">
            <div id="div_content" class="form">

                <div id="div_subhead" class="center">
                    <h2>Login</h2>
                </div>

                <!-- Error/Success message block-->
                <?php if (!empty($error_message)): ?>
                    <p style="color: red; font-weight: bold; text-align: center;"><?php echo $error_message; ?></p>
                <?php elseif (!empty($success_message)): ?>
                    <p style="color: green; font-weight: bold; text-align: center;"><?php echo $success_message; ?></p>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    
                    <label for="email">Email <span style="color: red;">*</span></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required> <br>

                    <label for="password">Password <span style="color: red;">*</span></label>
                    <input type="password" name="password" required> <br>

                    <div class="center">
                        <input type="submit" name="submit" value="Login">
                    </div>

                </form> 
                
                <!-- links back to your registration page -->
                <p class="center">
                    Don't have an account? <a href="registration.php">Sign up here</a>
                </p>

            </div>
        </div>

        <?php
            // Close the connection at the very end
            if (isset($conn)) {
                $conn->close();
            }
        ?>
    </body>
</html>
