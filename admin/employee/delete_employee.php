<?php
    require_once(__DIR__ . '/../auth_check.php');

    // Get and Validate the Employee ID from the URL
    $emp_id_to_delete = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $current_admin_email = $_SESSION['email']; // Get the logged-in admin's email

    if (empty($emp_id_to_delete)) {
        header("Location: employees.php?error=" . urlencode("Invalid employee ID."));
        exit;
    }

    try {
        // Security Check: Prevent self-deletion
        // We must fetch the email of the account we are about to delete
        $stmt_check = $conn->prepare("SELECT email FROM Employees WHERE emp_id = ?");
        $stmt_check->bind_param("i", $emp_id_to_delete);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $employee_to_delete = $result_check->fetch_assoc();
        $stmt_check->close();

        if (!$employee_to_delete) {
            throw new Exception("Employee not found.");
        }

        // Compare the target's email with the logged-in admin's email
        if ($employee_to_delete['email'] === $current_admin_email) {
            throw new Exception("You cannot delete your own account.");
        }

        // Proceed with Deletion
        $stmt_delete = $conn->prepare("DELETE FROM Employees WHERE emp_id = ?");
        $stmt_delete->bind_param("i", $emp_id_to_delete);
        
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                // Success
                header("Location: employees.php?status=delete_success");
            } else {
                throw new Exception("Employee not found or already deleted.");
            }
        } else {
            // This might fail if the employee is linked to other tables 
            // (e.g., as a manager) via foreign key constraints.
            throw new Exception("Failed to delete employee. They may be linked to other records.");
        }
        $stmt_delete->close();
        $conn->close();
        exit;

    } catch (Exception $e) {
        // Handle any errors
        $conn->close();
        header("Location: employees.php?error=" . urlencode($e->getMessage()));
        exit;
    }
?>