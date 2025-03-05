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
    if (empty($_POST['id'])) {
        throw new Exception('Admin ID is required');
    }

    // Prevent self-deletion
    if ($_POST['id'] == $_SESSION['admin_id']) {
        throw new Exception('You cannot delete your own account');
    }

    // Check if admin exists
    $stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $_POST['id']);
    if (!$stmt->execute()) {
        throw new Exception('Failed to check admin existence');
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Administrator not found');
    }

    $admin = $result->fetch_assoc();

    // Delete admin
    $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $_POST['id']);
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete administrator');
    }

    // Log the action
    log_admin_action($_SESSION['admin_id'], 'DELETE_ADMIN', "Deleted admin account for {$admin['username']}");
    
    echo json_encode(['success' => true, 'message' => 'Administrator deleted successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 