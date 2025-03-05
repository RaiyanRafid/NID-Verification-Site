<?php
session_start();
require_once('../config/database.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Base query
$query = "SELECT * FROM customers WHERE 1=1";
$params = [];
$types = "";

// Add filters
if ($status !== 'all') {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param, $search_param);
    $types .= "ssss";
}

// Add pagination
$offset = ($page - 1) * $per_page;
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM customers WHERE 1=1";
if ($status !== 'all') {
    $count_query .= " AND status = '$status'";
}
if (!empty($search)) {
    $count_query .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}
$total = $conn->query($count_query)->fetch_assoc()['total'];

// Format data
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'id' => $row['id'],
        'first_name' => htmlspecialchars($row['first_name']),
        'middle_name' => htmlspecialchars($row['middle_name']),
        'last_name' => htmlspecialchars($row['last_name']),
        'email' => htmlspecialchars($row['email']),
        'phone' => htmlspecialchars($row['phone']),
        'document_type' => $row['document_type'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'data' => $data,
    'total' => $total,
    'pages' => ceil($total / $per_page),
    'current_page' => $page
]); 