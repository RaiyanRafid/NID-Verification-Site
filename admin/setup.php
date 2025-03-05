<?php
require_once '../config/database.php';

// Check if admin account already exists
$result = $conn->query("SELECT COUNT(*) as count FROM admins");
$row = $result->fetch_assoc();

if ($row['count'] > 0) {
    die("Admin account already exists. Please use the existing account or contact your system administrator.");
}

// Generate a secure random password
function generateSecurePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Create default admin account with secure password
$username = 'admin';
$password = generateSecurePassword();
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$first_name = 'System';
$last_name = 'Administrator';
$email = 'admin@example.com';
$phone = '1234567890';

$stmt = $conn->prepare("INSERT INTO admins (username, password, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $username, $hashed_password, $first_name, $last_name, $email, $phone);

if ($stmt->execute()) {
    // Log the action
    $admin_id = $stmt->insert_id;
    $log_stmt = $conn->prepare("INSERT INTO audit_log (action, description) VALUES (?, ?)");
    $action = "ADMIN_SETUP";
    $description = "Initial admin account created";
    $log_stmt->bind_param("ss", $action, $description);
    $log_stmt->execute();

    // Display success message with credentials
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Setup - ID Verification System</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../css/style.css">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Setup Completed Successfully!</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <strong>Important:</strong> Please save these credentials immediately. They will not be shown again.
                            </div>
                            
                            <h5>Admin Account Details:</h5>
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
                            <p><strong>Password:</strong> <?php echo htmlspecialchars($password); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($phone); ?></p>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> For security reasons, please change this password immediately after logging in.
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="login.php" class="btn btn-primary">
                                    Go to Login Page
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            // Prevent going back to this page
            history.pushState(null, null, document.URL);
            window.addEventListener('popstate', function () {
                history.pushState(null, null, document.URL);
            });
        </script>
    </body>
    </html>
    <?php
} else {
    echo "Error creating admin account: " . $conn->error;
}
?> 