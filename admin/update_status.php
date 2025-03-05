<?php
// Prevent any unwanted output
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once('../config/database.php');
require_once 'includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Error handling function
function sendJsonResponse($success, $message, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    sendJsonResponse(false, 'Unauthorized', 401);
}

// Get and validate JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['id']) || !isset($data['status'])) {
    sendJsonResponse(false, 'Invalid request data', 400);
}

$id = intval($data['id']);
$status = trim($data['status']);
$rejection_reason = isset($data['rejection_reason']) ? trim($data['rejection_reason']) : '';
$admin_comments = isset($data['admin_comments']) ? trim($data['admin_comments']) : '';

try {
    // Validate status
    $allowed_statuses = ['Pending', 'Verified', 'Rejected'];
    if (!in_array($status, $allowed_statuses)) {
        sendJsonResponse(false, 'Invalid status: ' . $status, 400);
    }

    // Validate reason for rejection
    if ($status === 'Rejected' && empty($rejection_reason)) {
        sendJsonResponse(false, 'Reason is required for rejection', 400);
    }

    $conn->begin_transaction();

    // Get current status and details
    $check_stmt = $conn->prepare("SELECT status, first_name, last_name, email FROM customers WHERE id = ?");
    if (!$check_stmt) {
        throw new Exception('Failed to prepare check statement: ' . $conn->error);
    }
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if (!$result->num_rows) {
        throw new Exception('Request not found');
    }

    $customer = $result->fetch_assoc();
    $current_status = $customer['status'];

    if ($current_status === $status) {
        throw new Exception('Status is already ' . $status);
    }

    // Update status based on new status
    if ($status === 'Rejected') {
        $update_stmt = $conn->prepare("
            UPDATE customers 
            SET status = ?, 
                rejection_reason = ?, 
                admin_comments = ?
            WHERE id = ?
        ");
        if (!$update_stmt) {
            throw new Exception('Failed to prepare update statement: ' . $conn->error);
        }
        $update_stmt->bind_param("sssi", $status, $rejection_reason, $admin_comments, $id);
    } else {
        // For Pending or Verified status, clear rejection reason
        $update_stmt = $conn->prepare("
            UPDATE customers 
            SET status = ?, 
                rejection_reason = NULL,
                admin_comments = NULL
            WHERE id = ?
        ");
        if (!$update_stmt) {
            throw new Exception('Failed to prepare update statement: ' . $conn->error);
        }
        $update_stmt->bind_param("i", $id);
    }

    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update status: ' . $update_stmt->error);
    }

    if ($update_stmt->affected_rows === 0) {
        throw new Exception('No changes made');
    }

    // Log the action with status change details
    $admin_id = $_SESSION['admin_id'];
    $action = "Status changed from {$current_status} to {$status}";
    $description = "Admin changed status of verification request ID: {$id} from {$current_status} to {$status}" . 
                  ($status === 'Rejected' ? " (Reason: {$rejection_reason})" : "");

    $log_stmt = $conn->prepare("
        INSERT INTO audit_log (action, description, user_id, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    if (!$log_stmt) {
        throw new Exception('Failed to prepare log statement: ' . $conn->error);
    }
    $log_stmt->bind_param("ssi", $action, $description, $admin_id);

    if (!$log_stmt->execute()) {
        throw new Exception('Failed to log action: ' . $log_stmt->error);
    }

    // Send email notification
    require_once(__DIR__ . '/includes/functions.php');
    require_once(__DIR__ . '/includes/email_templates.php');

    $to = $customer['email'];
    $subject = "ID Verification Status Update";

    // Build HTML content
    $content = '
    <h2>Verification Status Update</h2>
    <p>Dear ' . htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) . ',</p>
    <p>Your ID verification request status has been updated to: ' . get_status_badge_html($status) . '</p>';

    switch ($status) {
        case 'Pending':
            $content .= '
            <p>Your verification request has been marked as pending for review.</p>
            <p>Our team will carefully review your submission and update you once the verification process is complete.</p>';
            break;
        case 'Verified':
            $content .= '
            <p>Congratulations! Your ID verification request has been approved.</p>
            <p>You can now access all features of our service.</p>
            <p>Thank you for choosing our service.</p>';
            break;
        case 'Rejected':
            $content .= '
            <p>Unfortunately, your ID verification request has been rejected.</p>
            <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 4px;">
                <strong>Reason for Rejection:</strong><br>
                ' . htmlspecialchars($rejection_reason) . '
            </div>
            <p>Please address the issues mentioned above and submit a new verification request.</p>
            <p><a href="' . $_SERVER['HTTP_HOST'] . '" class="button">Submit New Request</a></p>';
            break;
    }

    // Plain text version
    $plain_message = "Dear " . $customer['first_name'] . " " . $customer['last_name'] . ",\n\n";
    $plain_message .= "Your ID verification request status has been updated to: " . $status . "\n\n";
    
    switch ($status) {
        case 'Pending':
            $plain_message .= "Your verification request has been marked as pending for review.\n";
            $plain_message .= "Our team will carefully review your submission and update you once the verification process is complete.\n";
            break;
        case 'Verified':
            $plain_message .= "Congratulations! Your ID verification request has been approved.\n";
            $plain_message .= "You can now access all features of our service.\n";
            $plain_message .= "Thank you for choosing our service.\n";
            break;
        case 'Rejected':
            $plain_message .= "Unfortunately, your ID verification request has been rejected.\n\n";
            $plain_message .= "Reason for Rejection:\n" . $rejection_reason . "\n\n";
            $plain_message .= "Please address the issues mentioned above and submit a new verification request.\n";
            $plain_message .= "You can submit a new request at: " . $_SERVER['HTTP_HOST'] . "\n";
            break;
    }

    $plain_message .= "\nBest regards,\nID Verification Team";

    // Send email using SMTP
    if (!send_smtp_email($to, $subject, $content, $plain_message)) {
        error_log("Failed to send status update email to: " . $to);
    }

    $conn->commit();
    sendJsonResponse(true, 'Status updated successfully');

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in update_status.php: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage(), 500);
}
?> 