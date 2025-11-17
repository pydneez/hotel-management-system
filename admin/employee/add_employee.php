<?php
    require_once(__DIR__ . '/../auth_check.php'); 
    
    $error_message = "";
    $success_message = "";
    $form_data = []; 
    if (isset($_POST['submit'])) {
        $form_data = $_POST;
        $fname = trim(filter_input(INPUT_POST, 'fname', FILTER_SANITIZE_SPECIAL_CHARS));
        $lname = trim(filter_input(INPUT_POST, 'lname', FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
        $password = $_POST['password']; 
        $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($fname) || empty($lname) || empty($email) || empty($password) || empty($role)) {
            $error_message = "All fields are required.";
        }
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Invalid email format.";
        }
        elseif (substr($email, -8) !== '@hms.com') {
            $error_message = "Email address must end with @hms.com";
        }
        elseif (!in_array($role, ['Admin', 'Staff'])) {
             $error_message = "Invalid role selected.";
        }
        elseif (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/", $password)) {
             $error_message = "Password must be 6+ chars, with at least one letter and one number.";
        }
        else {
            try {
                $stmt_check = $conn->prepare("SELECT email FROM Employees WHERE email = ?");
                $stmt_check->bind_param("s", $email);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                    $error_message = "An employee with this email address already exists.";
                } else {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    
                    $stmt_insert = $conn->prepare("
                        INSERT INTO Employees (fname, lname, email, password_hash, role, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt_insert->bind_param("sssss", $fname, $lname, $email, $password_hash, $role);
                    
                    if ($stmt_insert->execute()) {
                        $success_message = "New employee '$fname $lname' added successfully!";
                        $form_data = []; // Clear form on success
                    } else {
                         throw new Exception("Database insert failed: " . $stmt_insert->error);
                    }
                    $stmt_insert->close();
                }
                $stmt_check->close();
                

            } catch (Exception $e) {
                $error_message = "An error occurred: " . $e->getMessage();
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
    <title>Dashboard | Add Employee</title>
    <link rel="stylesheet" href="../admin.css">
</head>

<body>
    <?php include "../component/navbar.php"; ?>

    <div class="dashboard-container">
        <?php include "../component/sidebar.php"; ?>

        <main class="content">
            <div class="content-header-row">
                <h1>Add New Employee</h1>
                
                <div class="header-actions">
                    <a href="employees.php" class="btn btn-secondary">&larr; Back to Employees</a>
                </div>
            </div>  
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                
                <div id="ajax-message-area">
                    <?php if (!empty($error_message)): ?>
                        <div class="form-message error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success_message)): ?>
                        <div class="form-message success"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                </div>

                <div class="background-card" style="max-width: 600px;">
                    <h2>Employee Details</h2>
                    
                    <div class="form-row-grid">
                        <div>
                            <label for="fname">First Name<span>*</span></label>
                            <input type="text" id="fname" name="fname" 
                                   value="<?php echo htmlspecialchars($form_data['fname'] ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="lname">Last Name<span>*</span></label>
                            <input type="text" id="lname" name="lname" 
                                   value="<?php echo htmlspecialchars($form_data['lname'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <label for="email">Email<span>*</span></label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" 
                           placeholder="e.g., staff.member@hms.com" required>
                   
                    
                    <label for="password">Password<span>*</span></label>
                    <input type="password" id="password" name="password" 
                           pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$"
                           title="Must be 6+ chars, with at least one letter and one number" required>
                  
                    <label for="role">Role<span>*</span></label>
                    <select id="role" name="role" required>
                        <option value="Staff" <?php echo (!isset($form_data['role']) || $form_data['role'] == 'Staff') ? 'selected' : ''; ?>>
                            Staff (Standard access)
                        </option>
                        <option value="Admin" <?php echo (isset($form_data['role']) && $form_data['role'] == 'Admin') ? 'selected' : ''; ?>>
                            Admin (Full access)
                        </option>
                    </select>

                    <div class="form-submit-row">
                        <button type="submit" name="submit" class="btn btn-primary">Add Employee</button>
                    </div>
                </div>

                
            </form>
            

        </main>
    </div>

</body>
</html>