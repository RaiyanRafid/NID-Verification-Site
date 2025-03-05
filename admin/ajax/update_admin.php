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
    if (empty($_POST['id']) || empty($_POST['username']) || empty($_POST['first_name']) || 
        empty($_POST['last_name']) || empty($_POST['email']) || empty($_POST['phone'])) {
        throw new Exception('All fields except password are required');
    }

    // Validate email format
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Check if username or email already exists for other admins
    $stmt = $conn->prepare("SELECT id FROM admins WHERE (username = ? OR email = ?) AND id != ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("ssi", $_POST['username'], $_POST['email'], $_POST['id']);
    if (!$stmt->execute()) {
        throw new Exception('Failed to check existing admin');
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        throw new Exception('Username or email already exists');
    }

    // Prepare base query without password
    $sql = "UPDATE admins SET username = ?, first_name = ?, last_name = ?, email = ?, phone = ?";
    $params = [$_POST['username'], $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone']];
    $types = "sssss";

    // Add password to query if provided
    if (!empty($_POST['password'])) {
        $sql .= ", password = ?";
        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $types .= "s";
    }

    $sql .= " WHERE id = ?";
    $params[] = $_POST['id'];
    $types .= "i";

    // Update admin
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update administrator');
    }

    // Log the action
    log_admin_action($_SESSION['admin_id'], 'UPDATE_ADMIN', "Updated admin account for {$_POST['username']}");
    
    echo json_encode(['success' => true, 'message' => 'Administrator updated successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 