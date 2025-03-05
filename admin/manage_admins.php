<?php
session_start();
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/includes/functions.php');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Get all admins
$query = "SELECT id, username, first_name, last_name, email, phone, created_at FROM admins";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins - ID Verification System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .admin-card {
            transition: transform 0.3s ease;
        }
        .admin-card:hover {
            transform: translateY(-5px);
        }
        @media (max-width: 768px) {
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Manage Administrators</h5>
                        <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                            <i class="bi bi-plus-lg"></i> Add New Admin
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo h($row['username']); ?></td>
                                        <td><?php echo h($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo h($row['email']); ?></td>
                                        <td><?php echo h($row['phone']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-primary edit-admin" 
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-username="<?php echo h($row['username']); ?>"
                                                        data-firstname="<?php echo h($row['first_name']); ?>"
                                                        data-lastname="<?php echo h($row['last_name']); ?>"
                                                        data-email="<?php echo h($row['email']); ?>"
                                                        data-phone="<?php echo h($row['phone']); ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <?php if ($row['id'] != $_SESSION['admin_id']): ?>
                                                <button class="btn btn-sm btn-danger delete-admin" 
                                                        data-id="<?php echo $row['id']; ?>"
                                                        data-name="<?php echo h($row['first_name'] . ' ' . $row['last_name']); ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                                <?php endif; ?>
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

    <!-- Add Admin Modal -->
    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Administrator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addAdminForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveNewAdmin">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Administrator</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editAdminForm">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-3">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">New Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="edit_confirm_password" name="confirm_password">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEditAdmin">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addAdminModal = new bootstrap.Modal(document.getElementById('addAdminModal'));
            const editAdminModal = new bootstrap.Modal(document.getElementById('editAdminModal'));

            // Add new admin
            document.getElementById('saveNewAdmin').addEventListener('click', async function() {
                const formData = new FormData(document.getElementById('addAdminForm'));
                
                if (formData.get('password') !== formData.get('confirm_password')) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Passwords do not match!'
                    });
                    return;
                }

                try {
                    const response = await fetch('ajax/add_admin.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        addAdminModal.hide();
                        await Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message
                        });
                        location.reload();
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to add administrator'
                    });
                }
            });

            // Edit admin
            document.querySelectorAll('.edit-admin').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const username = this.dataset.username;
                    const firstname = this.dataset.firstname;
                    const lastname = this.dataset.lastname;
                    const email = this.dataset.email;
                    const phone = this.dataset.phone;

                    document.getElementById('edit_id').value = id;
                    document.getElementById('edit_username').value = username;
                    document.getElementById('edit_first_name').value = firstname;
                    document.getElementById('edit_last_name').value = lastname;
                    document.getElementById('edit_email').value = email;
                    document.getElementById('edit_phone').value = phone;
                    document.getElementById('edit_password').value = '';
                    document.getElementById('edit_confirm_password').value = '';

                    editAdminModal.show();
                });
            });

            // Save edited admin
            document.getElementById('saveEditAdmin').addEventListener('click', async function() {
                const formData = new FormData(document.getElementById('editAdminForm'));
                
                // Check if passwords match if a new password is being set
                if (formData.get('password')) {
                    if (formData.get('password') !== formData.get('confirm_password')) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Passwords do not match!'
                        });
                        return;
                    }
                }

                try {
                    const response = await fetch('ajax/update_admin.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        editAdminModal.hide();
                        await Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message
                        });
                        location.reload();
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to update administrator'
                    });
                }
            });

            // Delete admin
            document.querySelectorAll('.delete-admin').forEach(button => {
                button.addEventListener('click', async function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;

                    const result = await Swal.fire({
                        title: 'Delete Administrator?',
                        text: `Are you sure you want to delete ${name}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete!'
                    });

                    if (result.isConfirmed) {
                        try {
                            const formData = new FormData();
                            formData.append('id', id);

                            const response = await fetch('ajax/delete_admin.php', {
                                method: 'POST',
                                body: formData
                            });

                            const data = await response.json();
                            
                            if (data.success) {
                                await Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: data.message
                                });
                                location.reload();
                            } else {
                                throw new Error(data.message);
                            }
                        } catch (error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error.message || 'Failed to delete administrator'
                            });
                        }
                    }
                });
            });
        });
    </script>
</body>
</html> 