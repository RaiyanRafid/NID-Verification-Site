<?php
session_start();
require_once '../../config/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Validate input
$required_fields = ['host', 'port', 'username', 'password', 'from_email', 'from_name', 'encryption'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        die(json_encode(['success' => false, 'message' => "Missing required field: $field"]));
    }
}

try {
    // Deactivate all existing settings
    $conn->query("UPDATE smtp_settings SET is_active = 0");

    // Insert new settings
    $stmt = $conn->prepare("
        INSERT INTO smtp_settings (
            host, port, username, password, from_email, from_name, encryption, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");

    $stmt->bind_param(
        "sisssss",
        $_POST['host'],
        $_POST['port'],
        $_POST['username'],
        $_POST['password'],
        $_POST['from_email'],
        $_POST['from_name'],
        $_POST['encryption']
    );

    if ($stmt->execute()) {
        // Log the action
        log_admin_action(
            $_SESSION['admin_id'],
            'UPDATE_SMTP',
            'Updated SMTP settings'
        );

        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Failed to save SMTP settings");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 