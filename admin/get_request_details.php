<?php
session_start();
require_once('../config/database.php');

// Set JSON header first
header('Content-Type: application/json');

// Error handling
function sendJsonError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    sendJsonError('Unauthorized', 401);
}

// Validate input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    sendJsonError('Invalid request ID');
}

try {
    $id = intval($_GET['id']);
    
    // Prepare and execute query with error handling
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    if (!$stmt->execute()) {
        throw new Exception("Execution error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        sendJsonError('Request not found', 404);
    }

    // Log the view action
    try {
        $user_id = $_SESSION['admin_id'];
        $action = "Viewed request details for ID: " . $id;
        $description = "Admin viewed customer verification details";
        
        $log_stmt = $conn->prepare("INSERT INTO audit_log (action, description, user_id, created_at) VALUES (?, ?, ?, NOW())");
        if ($log_stmt) {
            $log_stmt->bind_param("ssi", $action, $description, $user_id);
            $log_stmt->execute();
        }
    } catch (Exception $e) {
        // Log the error but don't stop the process
        error_log("Audit log error: " . $e->getMessage());
    }

    // Clean and prepare the data
    $document_path = $row['document_path'];
    $document_path_back = $row['document_path_back'];

    if (!empty($document_path)) {
        // Ensure the path is properly formatted
        $document_path = str_replace('\\', '/', $document_path);
        // Make sure we're only using the filename for security
        $filename = basename($document_path);
        // Check if file exists
        $full_path = '../uploads/' . $filename;
        if (file_exists($full_path)) {
            $row['document_path'] = $full_path;
        } else {
            $row['document_path'] = null;
            error_log("Document not found: " . $full_path);
        }
    }

    if (!empty($document_path_back)) {
        $document_path_back = str_replace('\\', '/', $document_path_back);
        $filename_back = basename($document_path_back);
        $full_path_back = '../uploads/' . $filename_back;
        if (file_exists($full_path_back)) {
            $row['document_path_back'] = $full_path_back;
        } else {
            $row['document_path_back'] = null;
            error_log("Back side document not found: " . $full_path_back);
        }
    }

    // Format dates
    $created_at = new DateTime($row['created_at']);
    $updated_at = new DateTime($row['updated_at']);

    // Send the response
    echo json_encode([
        'id' => $row['id'],
        'first_name' => $row['first_name'],
        'middle_name' => $row['middle_name'] ?? '',
        'last_name' => $row['last_name'],
        'email' => $row['email'],
        'phone' => $row['phone'],
        'address' => $row['address'],
        'document_type' => $row['document_type'],
        'status' => $row['status'],
        'rejection_reason' => $row['rejection_reason'] ?? '',
        'document_path' => $row['document_path'],
        'document_path_back' => $row['document_path_back'] ?? null,
        'created_at' => $created_at->format('Y-m-d H:i:s'),
        'updated_at' => $updated_at->format('Y-m-d H:i:s'),
        'success' => true
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Error in get_request_details.php: " . $e->getMessage());
    sendJsonError($e->getMessage(), 500);
}
?> 