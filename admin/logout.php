<?php
session_start();

// Log the logout action
if (isset($_SESSION['admin_id'])) {
    require_once '../config/database.php';
    
    $log_stmt = $conn->prepare("INSERT INTO audit_log (action, description, user_id) VALUES (?, ?, ?)");
    $action = "LOGOUT";
    $description = "Admin logged out";
    $log_stmt->bind_param("ssi", $action, $description, $_SESSION['admin_id']);
    $log_stmt->execute();
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?> 