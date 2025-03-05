<?php
// Set JSON header first
header('Content-Type: application/json');

// Prevent any unwanted output
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../includes/functions.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Validate input
    $required_fields = ['username', 'first_name', 'last_name', 'email', 'phone', 'password'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception('All fields are required');
        }
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("ss", $_POST['username'], $_POST['email']);
    if (!$stmt->execute()) {
        throw new Exception('Failed to check existing admin');
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        throw new Exception('Username or email already exists');
    }

    // Hash password
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert new admin
    $stmt = $conn->prepare("INSERT INTO admins (username, password, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("ssssss", 
        $_POST['username'],
        $hashed_password,
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['phone']
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to create administrator');
    }

    // Log the action
    $admin_id = $stmt->insert_id;
    log_admin_action($_SESSION['admin_id'], 'CREATE_ADMIN', "Created new admin account for {$_POST['username']}");
    
    echo json_encode(['success' => true, 'message' => 'Administrator created successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 