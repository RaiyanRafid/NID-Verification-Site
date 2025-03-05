<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$document_type_filter = isset($_GET['document_type']) ? $_GET['document_type'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare base query
$query = "SELECT * FROM customers WHERE 1=1";
$params = [];
$types = "";

// Add filters
if (!empty($status_filter)) {
    $query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($document_type_filter)) {
    $query .= " AND document_type = ?";
    $params[] = $document_type_filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param, $search_param);
    $types .= "ssss";
}

$query .= " ORDER BY created_at DESC";

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Verified' THEN 1 ELSE 0 END) as verified,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN document_type = 'nid' THEN 1 ELSE 0 END) as nid_count,
    SUM(CASE WHEN document_type = 'passport' THEN 1 ELSE 0 END) as passport_count,
    SUM(CASE WHEN document_type = 'birth' THEN 1 ELSE 0 END) as birth_count
FROM customers";
$stats = $conn->query($stats_query)->fetch_assoc();

// Document type statistics
$doc_stats_query = "SELECT document_type, COUNT(*) as count FROM customers GROUP BY document_type";
$doc_stats_result = $conn->query($doc_stats_query);
$doc_stats = [];
while ($row = $doc_stats_result->fetch_assoc()) {
    $doc_stats[$row['document_type']] = $row['count'];
}

// Execute the main query for the table
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Recent activity
$recent_activity = $conn->query("
    SELECT c.*, a.action, a.created_at as activity_time 
    FROM customers c 
    JOIN audit_log a ON a.description LIKE CONCAT('%', c.id, '%')
    ORDER BY a.created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ID Verification System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Responsive Breakpoints */
        @media (max-width: 576px) {
            .container-fluid {
                padding: 0.5rem;
            }
            .card {
                margin-bottom: 1rem;
            }
            .stats-card h2 {
                font-size: 1.5rem;
            }
            .stats-card h5 {
                font-size: 1rem;
            }
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            .btn-group-sm {
                display: flex;
                width: 100%;
            }
            .btn-group-sm .btn {
                flex: 1;
            }
            .table-responsive {
                font-size: 0.875rem;
            }
            .document-preview img {
                width: 40px;
                height: 40px;
            }
        }

        @media (max-width: 768px) {
            .modal-dialog {
                margin: 0.5rem;
            }
            .nav-pills .nav-link {
                padding: 0.5rem;
                font-size: 0.875rem;
            }
            .card-header h5 {
                font-size: 1rem;
            }
            .search-box {
                width: 100%;
                margin-top: 1rem;
            }
            .table th {
                white-space: nowrap;
            }
        }

        @media (max-width: 992px) {
            .col-md-3 {
                margin-bottom: 1rem;
            }
            .document-preview-section {
                padding: 0.5rem;
            }
        }

        /* Base Styles */
        .modal-dialog-scrollable {
            max-height: 90vh;
        }
        .modal-body {
            max-height: calc(90vh - 200px);
            overflow-y: auto;
            padding: 1.5rem;
        }
        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            white-space: nowrap;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .document-preview {
            max-width: 100%;
            height: auto;
            margin-top: 1rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .document-preview:hover {
            transform: scale(1.05);
        }
        .nav-pills .nav-link.active {
            background-color: #0d6efd;
            color: white;
        }
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .modal-xl {
            max-width: 90%;
        }
        .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }
        .modal-header {
            border-bottom: 1px solid #dee2e6;
            padding: 1.5rem;
        }
        .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 1.5rem;
        }
        .table th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        .preview-container {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .btn-group-vertical {
            width: 100%;
        }
        .btn-group-vertical .btn {
            text-align: left;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px !important;
        }
        .stats-card {
            transition: transform 0.3s ease;
            height: 100%;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .search-box {
            position: relative;
        }
        .search-box .form-control {
            padding-right: 40px;
        }
        .search-box .search-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }

        /* Loading Spinner */
        .loading-spinner {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            background: rgba(255, 255, 255, 0.8);
            padding: 2rem;
            border-radius: 8px;
            display: none;
        }

        /* Image Preview Styles */
        .preview-image {
            max-width: 100%;
            height: auto;
            object-fit: contain;
            cursor: pointer;
        }
        .document-preview-section {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .document-preview-section img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .document-preview-section img:hover {
            transform: scale(1.05);
        }
        
        /* Fullscreen Modal */
        .modal-fullscreen {
            padding: 0;
        }
        .modal-fullscreen .modal-content {
            min-height: 100vh;
            border-radius: 0;
        }
        #fullSizeImage {
            max-height: 90vh;
            max-width: 100%;
            object-fit: contain;
        }
        
        /* Header Responsiveness */
        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1rem;
            }
            .navbar .container-fluid {
                padding: 0.5rem;
            }
            .dropdown-menu {
                position: fixed !important;
                top: auto !important;
                right: 0 !important;
                left: 0 !important;
                bottom: 0;
                margin: 0;
                border-radius: 1rem 1rem 0 0;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
                transform: translateY(100%);
                transition: transform 0.3s ease-in-out;
            }
            .dropdown-menu.show {
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Quick Filters</h5>
                    </div>
                    <div class="card-body">
                        <div class="nav flex-column nav-pills">
                            <button class="nav-link active mb-2" data-status="pending">Pending Requests</button>
                            <button class="nav-link mb-2" data-status="approved">Approved Requests</button>
                            <button class="nav-link mb-2" data-status="rejected">Rejected Requests</button>
                            <button class="nav-link" data-status="all">All Requests</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="change_password.php" class="btn btn-outline-primary">
                                <i class="bi bi-key"></i> Change Password
                            </a>
                            <a href="export.php" class="btn btn-outline-success">
                                <i class="bi bi-file-earmark-excel"></i> Export Data
                            </a>
                            <a href="audit_log.php" class="btn btn-outline-info">
                                <i class="bi bi-journal-text"></i> View Audit Log
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-9">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stats-card bg-primary text-white">
                            <div class="card-body">
                                <h5>Total Requests</h5>
                                <h2><?php echo $stats['total']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-warning text-dark">
                            <div class="card-body">
                                <h5>Pending</h5>
                                <h2><?php echo $stats['pending']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-success text-white">
                            <div class="card-body">
                                <h5>Verified</h5>
                                <h2><?php echo $stats['verified']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stats-card bg-danger text-white">
                            <div class="card-body">
                                <h5>Rejected</h5>
                                <h2><?php echo $stats['rejected']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">NID Submissions</h5>
                                <h3><?php echo $stats['nid_count']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Passport Submissions</h5>
                                <h3><?php echo $stats['passport_count']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Birth Certificate Submissions</h5>
                                <h3><?php echo $stats['birth_count']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Verification Requests</h5>
                        <div class="input-group w-auto">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search...">
                            <button class="btn btn-light" type="button" id="searchButton">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="requestsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Document Type</th>
                                        <th>Status</th>
                                        <th>Documents</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr data-status="<?php echo strtolower($row['status']); ?>">
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']); ?></td>
                                        <td>
                                            <div><?php echo htmlspecialchars($row['email']); ?></div>
                                            <div><?php echo htmlspecialchars($row['phone']); ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $doc_type = strtoupper($row['document_type']);
                                            $doc_badge_class = 'bg-secondary';
                                            switch($row['document_type']) {
                                                case 'nid': $doc_badge_class = 'bg-info'; break;
                                                case 'passport': $doc_badge_class = 'bg-primary'; break;
                                                case 'birth': $doc_badge_class = 'bg-success'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $doc_badge_class; ?>"><?php echo $doc_type; ?></span>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = 'bg-secondary';
                                            switch($row['status']) {
                                                case 'Pending': $status_class = 'bg-warning text-dark'; break;
                                                case 'Verified': $status_class = 'bg-success'; break;
                                                case 'Rejected': $status_class = 'bg-danger'; break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?> status-badge">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($row['document_type'] === 'nid'): ?>
                                                <img src="../<?php echo htmlspecialchars($row['document_path']); ?>" 
                                                     class="document-preview img-thumbnail me-2" 
                                                     alt="Front" 
                                                     style="width: 50px; height: 50px;"
                                                     onclick="previewImage('<?php echo htmlspecialchars($row['document_path']); ?>', 'Front Side')">
                                                <?php if ($row['document_path_back']): ?>
                                                    <img src="../<?php echo htmlspecialchars($row['document_path_back']); ?>" 
                                                         class="document-preview img-thumbnail" 
                                                         alt="Back" 
                                                         style="width: 50px; height: 50px;"
                                                         onclick="previewImage('<?php echo htmlspecialchars($row['document_path_back']); ?>', 'Back Side')">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <img src="../<?php echo htmlspecialchars($row['document_path']); ?>" 
                                                     class="document-preview img-thumbnail" 
                                                     alt="Document" 
                                                     style="width: 50px; height: 50px;"
                                                     onclick="previewImage('<?php echo htmlspecialchars($row['document_path']); ?>', 'Document')">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn btn-sm btn-info view-details" data-id="<?php echo $row['id']; ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn <?php echo $row['status'] === 'Pending' ? 'btn-warning' : 'btn-outline-warning'; ?> status-change" 
                                                    data-id="<?php echo $row['id']; ?>" 
                                                    data-status="Pending"
                                                    <?php echo $row['status'] === 'Pending' ? 'disabled' : ''; ?>>
                                                    <i class="bi bi-clock"></i>
                                                </button>
                                                <button type="button" class="btn <?php echo $row['status'] === 'Verified' ? 'btn-success' : 'btn-outline-success'; ?> status-change"
                                                    data-id="<?php echo $row['id']; ?>" 
                                                    data-status="Verified"
                                                    <?php echo $row['status'] === 'Verified' ? 'disabled' : ''; ?>>
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                                <button type="button" class="btn <?php echo $row['status'] === 'Rejected' ? 'btn-danger' : 'btn-outline-danger'; ?> status-change"
                                                    data-id="<?php echo $row['id']; ?>" 
                                                    data-status="Rejected"
                                                    <?php echo $row['status'] === 'Rejected' ? 'disabled' : ''; ?>>
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="fullImageModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content bg-dark">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-white"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-4">
                    <img id="fullSizeImage" src="" class="img-fluid" alt="Full Size Preview" style="max-height: 90vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    Loading...
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
            
            // Show loading spinner
            function showLoading() {
                document.querySelector('.loading-spinner').style.display = 'block';
            }

            // Hide loading spinner
            function hideLoading() {
                document.querySelector('.loading-spinner').style.display = 'none';
            }

            // Filter functionality with animation
            function filterRequests(status) {
                document.querySelectorAll('#requestsTable tbody tr').forEach(row => {
                    const rowStatus = row.dataset.status;
                    row.style.opacity = '0';
                    setTimeout(() => {
                        if (status === 'all' || rowStatus === status) {
                            row.style.display = '';
                            setTimeout(() => {
                                row.style.opacity = '1';
                            }, 50);
                        } else {
                            row.style.display = 'none';
                        }
                    }, 300);
                });
            }

            // Initialize with pending requests
            filterRequests('pending');

            document.querySelectorAll('.nav-pills .nav-link').forEach(button => {
                button.addEventListener('click', function() {
                    const status = this.dataset.status;
                    document.querySelectorAll('.nav-pills .nav-link').forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    filterRequests(status);
                });
            });

            // Make sure "Pending Requests" button is active by default
            document.querySelector('[data-status="pending"]').classList.add('active');

            // Enhanced search functionality
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;

            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const searchTerm = this.value.toLowerCase();
                    document.querySelectorAll('#requestsTable tbody tr').forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.style.display = text.includes(searchTerm) ? '' : 'none';
                            if (row.style.display === '') {
                                row.style.opacity = '1';
                            }
                        }, 300);
                    });
                }, 300);
            });

            // Create full image preview modal instance
            const fullImageModal = new bootstrap.Modal(document.getElementById('fullImageModal'));
            
            // Function to preview image in full screen
            window.previewFullImage = function(src, title) {
                const modal = document.getElementById('fullImageModal');
                const image = document.getElementById('fullSizeImage');
                const modalTitle = modal.querySelector('.modal-title');
                
                image.src = '../' + src;
                modalTitle.textContent = title;
                
                fullImageModal.show();
            };

            // Status change functionality
            document.querySelectorAll('.status-change').forEach(button => {
                button.addEventListener('click', async function() {
                    const id = this.dataset.id;
                    const newStatus = this.dataset.status;
                    const currentStatus = this.closest('tr')?.querySelector('.status-badge')?.textContent.trim() || '';
                    
                    let confirmConfig = {
                        title: `Change Status to ${newStatus}`,
                        text: `Are you sure you want to change the status from ${currentStatus} to ${newStatus}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, change it!',
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading()
                    };

                    // If changing to Rejected status, ask for reason
                    if (newStatus === 'Rejected') {
                        confirmConfig = {
                            ...confirmConfig,
                            input: 'textarea',
                            inputLabel: 'Reason for rejection',
                            inputPlaceholder: 'Enter the reason for rejection...',
                            inputAttributes: {
                                required: 'true'
                            },
                            validationMessage: 'Please enter a reason for rejection'
                        };
                    }

                    // Set button colors based on status
                    if (newStatus === 'Pending') {
                        confirmConfig.confirmButtonColor = '#ffc107';
                    } else if (newStatus === 'Verified') {
                        confirmConfig.confirmButtonColor = '#28a745';
                    } else {
                        confirmConfig.confirmButtonColor = '#dc3545';
                    }

                    try {
                        const result = await Swal.fire(confirmConfig);

                        if (result.isConfirmed) {
                            showLoading();
                            const response = await fetch('update_status.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    id: id,
                                    status: newStatus,
                                    reason: newStatus === 'Rejected' ? result.value : null
                                })
                            });

                            const data = await response.json();
                            if (!data.success) {
                                throw new Error(data.message);
                            }

                            await Swal.fire({
                                icon: 'success',
                                title: 'Status Updated!',
                                text: `The request has been marked as ${newStatus}.`,
                                timer: 1500
                            });

                            location.reload();
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Failed to update status'
                        });
                    } finally {
                        hideLoading();
                    }
                });
            });

            // View details function
            document.querySelectorAll('.view-details').forEach(button => {
                button.addEventListener('click', async function() {
                    const id = this.dataset.id;
                    showLoading();
                    
                    try {
                        const response = await fetch(`get_request_details.php?id=${id}`);
                        const data = await response.json();
                        
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        
                        // Update modal content with animation
                        const contentDiv = document.getElementById('detailsContent');
                        contentDiv.innerHTML = `
                            <dl class="row">
                                <dt class="col-sm-4">Name</dt>
                                <dd class="col-sm-8">${data.first_name} ${data.middle_name} ${data.last_name}</dd>
                                
                                <dt class="col-sm-4">Email</dt>
                                <dd class="col-sm-8">${data.email}</dd>
                                
                                <dt class="col-sm-4">Phone</dt>
                                <dd class="col-sm-8">${data.phone}</dd>
                                
                                <dt class="col-sm-4">Address</dt>
                                <dd class="col-sm-8">${data.address}</dd>
                                
                                <dt class="col-sm-4">Document Type</dt>
                                <dd class="col-sm-8">${data.document_type.toUpperCase()}</dd>
                                
                                <dt class="col-sm-4">Status</dt>
                                <dd class="col-sm-8">
                                    <span class="badge ${getStatusBadgeClass(data.status)}">
                                        ${data.status}
                                    </span>
                                </dd>
                                
                                ${data.rejection_reason ? `
                                <dt class="col-sm-4">Rejection Reason</dt>
                                <dd class="col-sm-8">${data.rejection_reason}</dd>
                                ` : ''}
                                
                                <dt class="col-sm-4">Submitted</dt>
                                <dd class="col-sm-8">${data.created_at}</dd>
                                
                                <dt class="col-sm-4">Last Updated</dt>
                                <dd class="col-sm-8">${data.updated_at}</dd>
                            </dl>

                            <div class="document-preview-section">
                                <div class="document-preview-title">Document Images</div>
                                ${data.document_type === 'nid' ? `
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-2">Front Side</p>
                                            <img src="../${data.document_path}" 
                                                 class="img-fluid mb-3" 
                                                 alt="NID Front"
                                                 onclick="previewFullImage('${data.document_path}', 'NID Front Side')">
                                        </div>
                                        ${data.document_path_back ? `
                                        <div class="col-md-6">
                                            <p class="mb-2">Back Side</p>
                                            <img src="../${data.document_path_back}" 
                                                 class="img-fluid mb-3" 
                                                 alt="NID Back"
                                                 onclick="previewFullImage('${data.document_path_back}', 'NID Back Side')">
                                        </div>
                                        ` : ''}
                                    </div>
                                ` : `
                                    <div class="text-center">
                                        <img src="../${data.document_path}" 
                                             class="img-fluid" 
                                             alt="${data.document_type.toUpperCase()}"
                                             onclick="previewFullImage('${data.document_path}', '${data.document_type.toUpperCase()} Document')">
                                    </div>
                                `}
                            </div>
                        `;

                        detailsModal.show();
                    } catch (error) {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Failed to load request details'
                        });
                    } finally {
                        hideLoading();
                    }
                });
            });

            function getStatusBadgeClass(status) {
                switch(status) {
                    case 'Pending': return 'bg-warning text-dark';
                    case 'Verified': return 'bg-success';
                    case 'Rejected': return 'bg-danger';
                    default: return 'bg-secondary';
                }
            }

            // Handle ESC key for full image modal
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    fullImageModal.hide();
                }
            });

            // Handle click outside image to close
            document.getElementById('fullImageModal').addEventListener('click', function(event) {
                if (event.target === this || event.target.classList.contains('modal-body')) {
                    fullImageModal.hide();
                }
            });

            // Add loading spinner to body
            const spinner = document.createElement('div');
            spinner.className = 'loading-spinner';
            spinner.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            `;
            document.body.appendChild(spinner);
        });
    </script>
</body>
</html> 