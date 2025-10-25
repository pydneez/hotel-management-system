<?php
    require_once('../connect.php');
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
        // send to guest dashboard
        header("Location: ../dashboard.php"); 
        exit();
    }

    // authenticated and authorized staff confirmed
    $email = $_SESSION['email'];

    // Fetch employee details
    $stmt = $conn->prepare("SELECT fname, email, role FROM Employees WHERE email = ?");
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