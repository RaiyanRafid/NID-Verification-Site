<?php
session_start();
require_once '../config/database.php';
require_once 'includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get current SMTP settings
$smtp_settings = $conn->query("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Settings - ID Verification System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">SMTP Configuration</h5>
                    </div>
                    <div class="card-body">
                        <form id="smtpForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="host" class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" id="host" name="host" value="<?php echo h($smtp_settings['host'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="port" class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" id="port" name="port" value="<?php echo h($smtp_settings['port'] ?? '587'); ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">SMTP Username</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo h($smtp_settings['username'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" id="password" name="password" value="<?php echo h($smtp_settings['password'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="from_email" class="form-label">From Email</label>
                                    <input type="email" class="form-control" id="from_email" name="from_email" value="<?php echo h($smtp_settings['from_email'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="from_name" class="form-label">From Name</label>
                                    <input type="text" class="form-control" id="from_name" name="from_name" value="<?php echo h($smtp_settings['from_name'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="encryption" class="form-label">Encryption</label>
                                    <select class="form-select" id="encryption" name="encryption">
                                        <option value="none" <?php echo ($smtp_settings['encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                                        <option value="tls" <?php echo ($smtp_settings['encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo ($smtp_settings['encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="test_email" class="form-label">Test Email (for testing SMTP)</label>
                                    <input type="email" class="form-control" id="test_email" name="test_email">
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Settings
                                </button>
                                <button type="button" class="btn btn-success" id="testSmtp">
                                    <i class="bi bi-envelope-check"></i> Test SMTP
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="loading-spinner" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const smtpForm = document.getElementById('smtpForm');
            const testSmtpBtn = document.getElementById('testSmtp');
            const loadingSpinner = document.querySelector('.loading-spinner');

            function showLoading() {
                loadingSpinner.style.display = 'block';
            }

            function hideLoading() {
                loadingSpinner.style.display = 'none';
            }

            smtpForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                showLoading();

                try {
                    const formData = new FormData(smtpForm);
                    const response = await fetch('ajax/save_smtp.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'SMTP settings have been saved successfully.',
                            timer: 2000
                        });
                    } else {
                        throw new Error(result.message || 'Failed to save SMTP settings');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to save SMTP settings'
                    });
                } finally {
                    hideLoading();
                }
            });

            testSmtpBtn.addEventListener('click', async function() {
                const testEmail = document.getElementById('test_email').value;
                if (!testEmail) {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Warning',
                        text: 'Please enter a test email address'
                    });
                    return;
                }

                showLoading();
                try {
                    const formData = new FormData(smtpForm);
                    formData.append('test_email', testEmail);
                    
                    const response = await fetch('ajax/test_smtp.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();
                    
                    if (result.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Test email sent successfully. Please check your inbox.'
                        });
                    } else {
                        throw new Error(result.message || 'Failed to send test email');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to send test email'
                    });
                } finally {
                    hideLoading();
                }
            });
        });
    </script>
</body>
</html> 