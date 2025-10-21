<?php
require_once('connect.php');
session_start();

$email = "";
$error_message = "";

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        $stmt = $conn->prepare("SELECT password_hash, role FROM Employees WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $user['role'];
            header("Location: admin/dashboard.php");
            exit();
        } else {
            $error_message = "Incorrect email or password.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-container">
    <form method="POST" action="">
        <h2>Login</h2>
        <label for="email">Email <span style="color: red;">*</span></label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

        <label for="password">Password <span style="color: red;">*</span></label>
        <input type="password" name="password" required>

        <input type="submit" name="submit" value="Login">

        <?php if (!empty($error_message)): ?>
            <p class="error" style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
