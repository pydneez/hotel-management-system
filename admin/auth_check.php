<?php
    require_once(__DIR__ . '/../connect.php');
    session_start();

    // Authentication Check
    // Are they logged in at all?
    if (!isset($_SESSION['email'])) {
        // send to login
        header("Location: ../login.php?error=nosession");
        exit();
    }
    //  Authorization Check
    // Are they an employee or guest? 
    if (!isset($_SESSION['role']) || $_SESSION['role'] === 'guest') {
        // logged in and is a guests!
        header("Location: ../dashboard.php"); 
        exit();
    }

    
        
    // --- 3. NEW: Authorization Check (Staff vs Admin) ---
    if ($_SESSION['role'] === 'Staff') {
        // Staff are restricted. Define restricted areas.
        $restricted_paths = [
            '/admin/employee/',      // Employee management
            '/admin/room/',          // Room management (add/edit types)
            '/admin/analytic/'   // Financial analytics
        ];
        
        // Get the current page path
        $current_script = $_SERVER['SCRIPT_NAME'];
        
        foreach ($restricted_paths as $path) {
            // Check if the current script path *starts with* a restricted path
            if (strpos($current_script, $path) === 0) {
                // User is 'Staff' and is trying to access a restricted page.
                // Send them to dashboard with an error.
                header("Location: /admin/dashboard.php?error=" . urlencode("Access Denied"));
                exit();
            }
        }
    }

    // --- 4. Get Fresh User Data ---
    // We've confirmed they are a logged-in employee.
    // Let's fetch their data to use on the page (e.g., "Welcome, [Name]").
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT email, role, fname, lname FROM Employees WHERE email = ?"); 
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    $stmt->close();

    // What if their account was deleted while they were logged in?
    if (!$employee) {
        // Force a logout
        session_unset();
        session_destroy();
        header("Location: ../login.php?error=deleted");
        exit();
    }
?>