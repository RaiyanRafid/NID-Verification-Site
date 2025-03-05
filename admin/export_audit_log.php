<?php
session_start();
require_once('../config/database.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get audit log entries
$stmt = $conn->prepare("
    SELECT 
        audit_log.timestamp,
        admins.username,
        audit_log.action
    FROM audit_log 
    LEFT JOIN admins ON audit_log.admin_id = admins.id 
    ORDER BY timestamp DESC
");
$stmt->execute();
$result = $stmt->get_result();

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d_H-i-s') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, ['Timestamp', 'Admin Username', 'Action']);

// Add data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        date('Y-m-d H:i:s', strtotime($row['timestamp'])),
        $row['username'],
        $row['action']
    ]);
}

// Log the export action
$admin_id = $_SESSION['admin_id'];
$action = "Exported audit log";
$log_stmt = $conn->prepare("INSERT INTO audit_log (admin_id, action, timestamp) VALUES (?, ?, NOW())");
$log_stmt->bind_param("is", $admin_id, $action);
$log_stmt->execute();

fclose($output);
?> 