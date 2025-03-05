<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Prepare query with filters
$query = "SELECT * FROM customers WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($date_from)) {
    $query .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $query .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Generate filename with date and filters
$filename = 'customer_records_' . date('Y-m-d');
if (!empty($status_filter)) {
    $filename .= '_' . strtolower($status_filter);
}
if (!empty($date_from) && !empty($date_to)) {
    $filename .= '_' . $date_from . '_to_' . $date_to;
}
$filename .= '.csv';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers with descriptions
fputcsv($output, [
    'ID',
    'First Name',
    'Middle Name',
    'Last Name',
    'Email',
    'Phone',
    'Address',
    'Status',
    'Admin Comments',
    'Document Path',
    'Submission Date',
    'Last Updated'
]);

// Add records to CSV
$count = 0;
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['first_name'],
        $row['middle_name'],
        $row['last_name'],
        $row['email'],
        $row['phone'],
        $row['address'],
        $row['status'],
        $row['admin_comments'],
        $row['document_path'],
        date('Y-m-d H:i:s', strtotime($row['created_at'])),
        date('Y-m-d H:i:s', strtotime($row['updated_at']))
    ]);
    $count++;
}

// Log the export action with details
$log_stmt = $conn->prepare("INSERT INTO audit_log (action, description, user_id) VALUES (?, ?, ?)");
$action = "EXPORT";
$description = "Exported $count records to CSV" . 
    (!empty($status_filter) ? " (Status: $status_filter)" : "") .
    (!empty($date_from) && !empty($date_to) ? " (Date: $date_from to $date_to)" : "") .
    (!empty($search) ? " (Search: $search)" : "");
$log_stmt->bind_param("ssi", $action, $description, $_SESSION['admin_id']);
$log_stmt->execute();

// Close the output stream
fclose($output);
exit();
?> 