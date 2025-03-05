<?php
session_start();
require_once('../config/database.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$admin_id = isset($_GET['admin_id']) ? $_GET['admin_id'] : '';

// Base query
$query = "SELECT al.*, a.username 
          FROM audit_log al 
          LEFT JOIN admins a ON al.user_id = a.id 
          WHERE al.created_at BETWEEN ? AND ?";
$params = [$date_from . ' 00:00:00', $date_to . ' 23:59:59'];
$types = "ss";

if (!empty($action_type)) {
    $query .= " AND al.action = ?";
    $params[] = $action_type;
    $types .= "s";
}

if (!empty($admin_id)) {
    $query .= " AND al.user_id = ?";
    $params[] = $admin_id;
    $types .= "i";
}

$query .= " ORDER BY al.created_at DESC";

// Get admins for filter
$admins = $conn->query("SELECT id, username FROM admins ORDER BY username");

// Get unique action types
$actions = $conn->query("SELECT DISTINCT action FROM audit_log ORDER BY action");

// Prepare and execute query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - ID Verification System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .audit-item {
            transition: background-color 0.3s ease;
        }
        .audit-item:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .action-badge {
            min-width: 100px;
        }
        .table-responsive {
            margin-top: 20px;
        }
        .filter-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .badge {
            font-size: 0.9em;
            padding: 8px 12px;
        }
        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Audit Log</h5>
                        <a href="export_audit_log.php" class="btn btn-light btn-sm">
                            <i class="bi bi-download"></i> Export Log
                        </a>
                    </div>
                    <div class="card-body">
                        <form class="row g-3 mb-4 filter-form">
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Action Type</label>
                                <select class="form-select" name="action_type">
                                    <option value="">All Actions</option>
                                    <?php while ($action = $actions->fetch_assoc()): ?>
                                        <option value="<?php echo $action['action']; ?>" <?php echo $action_type === $action['action'] ? 'selected' : ''; ?>>
                                            <?php echo $action['action']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Admin</label>
                                <select class="form-select" name="admin_id">
                                    <option value="">All Admins</option>
                                    <?php while ($admin = $admins->fetch_assoc()): ?>
                                        <option value="<?php echo $admin['id']; ?>" <?php echo $admin_id == $admin['id'] ? 'selected' : ''; ?>>
                                            <?php echo $admin['username']; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Admin</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>IP Address</th>
                                        <th>User Agent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr class="audit-item">
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['username'] ?? 'System'); ?></td>
                                            <td>
                                                <?php
                                                $badge_class = 'bg-secondary';
                                                switch($row['action']) {
                                                    case 'LOGIN': $badge_class = 'bg-info'; break;
                                                    case 'VERIFY': $badge_class = 'bg-success'; break;
                                                    case 'REJECT': $badge_class = 'bg-danger'; break;
                                                    case 'UPDATE': $badge_class = 'bg-primary'; break;
                                                    case 'EXPORT': $badge_class = 'bg-warning text-dark'; break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $badge_class; ?> action-badge">
                                                    <?php echo $row['action']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td><?php echo htmlspecialchars($row['ip_address'] ?? 'N/A'); ?></td>
                                            <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($row['user_agent'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($row['user_agent'] ?? 'N/A'); ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 