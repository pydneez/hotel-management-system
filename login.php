<?php
    session_start();
    require_once('connect.php'); 

    function authenticateUser($conn, $email, $password) {
        $user_data = false;
        
        // Check if it's an employee email (ends with @hms.com)
        if (substr($email, -8) === '@hms.com') {
            $sql = "SELECT password_hash, role FROM Employees WHERE email = ?";
            $default_role = null; // Role will come from DB
        } else {
            $sql = "SELECT guest_id, password_hash FROM Guests WHERE email = ?";
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
            if ($user_data['role'] === 'guest') {
                $user_data['guest_id'] = $user['guest_id'];
            }
        }

        $stmt->close();
        return $user_data;
    }


    // --- Form Processing ---

    $email = "";
    $error_message = "";
    $success_message = ""; 

    // Check for a success message from the registration page
    if (isset($_GET['status']) && $_GET['status'] === 'success') {
        $success_message = "Registration successful!";
    }

    // Check for a redirect message from booking
    if (isset($_GET['message'])) {
        $error_message = htmlspecialchars($_GET['message']);
    }


    if (isset($_POST['submit'])) {
        $success_message = "";
        $error_message = ""; 

        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        } else {
            $userData = authenticateUser($conn, $email, $password);

            if ($userData) {
                // SUCCESS: Store session data
                $_SESSION['email'] = $userData['email'];
                $_SESSION['role'] = $userData['role'];
                
                // Store guest_id in session ---
                if ($userData['role'] === 'guest') {
                    $_SESSION['guest_id'] = $userData['guest_id'];
                }
                // Check if we need to redirect back to booking
                if (isset($_POST['redirect_url']) && !empty($_POST['redirect_url'])) {
                    if (isset($conn)) { $conn->close(); }
                    header("Location: " . $_POST['redirect_url']);
                    exit();
                }

                if (isset($_GET['redirect'])) {
                    $redirect_url .= "?redirect=" . urlencode($_GET['redirect']);
                    header("Location: " . $redirect_url);
                    exit();
                }

                // redirect based on role
                if ($userData['role'] === 'guest') {
                    header("Location: index.php"); 
                } else {
                    header("Location: admin/dashboard.php");
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
    <?php include "navbar.php"; ?>

    <div class="two-column-page">
        <div class="panel-content">
            <h1>Welcome to Royal Stay Hotel</h1>
            <p>"Exceptional service starts here."</p>
        </div>

        <div class="panel-form">
            <div id="div_content" class="form">

                <div id="div_subhead" class="center">
                    <h2>Login</h2>
                </div>

                <?php if (!empty($error_message)): ?>
                    <p style="color: red; font-weight: bold; text-align: center;"><?php echo $error_message; ?></p>
                <?php elseif (!empty($success_message)): ?>
                    <p style="color: green; font-weight: bold; text-align: center;"><?php echo $success_message; ?></p>
                <?php endif; ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">

                    <?php if (isset($_GET['redirect'])): ?>
                        <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                    <?php endif; ?>

                    <label for="email">Email <span style="color: red;">*</span></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required> <br>

                    <label for="password">Password <span style="color: red;">*</span></label>
                    <input type="password" name="password" required> <br>

                    <div class="center">
                        <input type="submit" name="submit" value="Login">
                    </div>

                </form>

                <?php
                    $reg_url = "registration.php";
                    if (isset($_GET['redirect'])) {
                        // Pass the redirect parameter to the registration page
                        $reg_url .= "?redirect=" . urlencode($_GET['redirect']);
                    }
                ?>
                <p class="center">
                    Don't have an account? <a href="<?php echo htmlspecialchars($reg_url); ?>">Sign up</a>
                </p>

            </div>
        </div>
    </div>

    <?php
        if (isset($conn)) {
            $conn->close();
        }
    ?>
</body>
</html>