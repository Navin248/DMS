<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('admin');

$user = get_user_info();
$error = '';
$success = '';

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action == 'reset_password') {
        $target_user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Both password fields are required!';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match!';
        } elseif ($target_user_id <= 0) {
            $error = 'Invalid user selected!';
        } else {
            // Hash password using bcrypt
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $hashed_password, $target_user_id);

            if ($update_stmt->execute()) {
                // Get the username for the success message
                $user_query = "SELECT username FROM users WHERE id = ?";
                $user_stmt = $conn->prepare($user_query);
                $user_stmt->bind_param("i", $target_user_id);
                $user_stmt->execute();
                $target_user = $user_stmt->get_result()->fetch_assoc();

                $success = 'Password for user "' . htmlspecialchars($target_user['username']) . '" has been reset successfully!';
            } else {
                $error = 'Error updating password: ' . $conn->error;
            }
        }
    }
}

// Get all users
$users_query = "SELECT id, username, email, role, location, created_at, updated_at FROM users ORDER BY role ASC, username ASC";
$users_result = $conn->query($users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .user-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .user-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
            border-color: #1E3A8A;
        }
        .role-badge-admin {
            background: linear-gradient(135deg, #1E3A8A, #0f2847);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .role-badge-user {
            background: linear-gradient(135deg, #F97316, #ea580c);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .btn-reset-password {
            background: linear-gradient(135deg, #F97316, #ea580c);
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reset-password:hover {
            background: linear-gradient(135deg, #1E3A8A, #0f2847);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }
        .page-header {
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        .page-header h2 {
            margin: 0;
            font-weight: 800;
        }
        .page-header p {
            margin: 5px 0 0;
            opacity: 0.8;
            font-size: 0.95rem;
        }
        .stats-mini {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        .stats-mini .stat {
            background: rgba(255,255,255,0.15);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .stats-mini .stat strong {
            display: block;
            font-size: 1.2rem;
        }
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .table thead th {
            background: #F8FAFC;
            color: #1E3A8A;
            font-weight: 700;
            border-bottom: 2px solid #E2E8F0;
            padding: 14px 16px;
        }
        .table tbody td {
            padding: 14px 16px;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background: #FFF7ED;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1rem;
        }
        .avatar-admin {
            background: linear-gradient(135deg, #1E3A8A, #0f2847);
            color: white;
        }
        .avatar-user {
            background: linear-gradient(135deg, #F97316, #ea580c);
            color: white;
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }
        .modal-header {
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }
        .modal-header .btn-close {
            filter: invert(1);
        }
        .modal-body {
            padding: 30px;
        }
        .modal-footer {
            border-top: 1px solid #E5E7EB;
            padding: 15px 30px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row" style="min-height: 100vh;">
            <?php include '../includes/sidebar.php'; ?>
            
            <div class="col-md-9 p-4" style="background-color: #F3F4F6;">
                <?php include '../includes/header.php'; ?>
                
                <div class="mt-4">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h2><i class="fas fa-users-cog"></i> Manage Users</h2>
                        <p>View all users and manage their passwords securely</p>
                        <?php
                        $total_users = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
                        $admin_count = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='admin'")->fetch_assoc()['c'];
                        $user_count = $total_users - $admin_count;
                        ?>
                        <div class="stats-mini">
                            <div class="stat">
                                <strong><?php echo $total_users; ?></strong> Total Users
                            </div>
                            <div class="stat">
                                <strong><?php echo $admin_count; ?></strong> Admins
                            </div>
                            <div class="stat">
                                <strong><?php echo $user_count; ?></strong> Coordinators
                            </div>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <strong>Error!</strong> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <strong>Success!</strong> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Users Table -->
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Location</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $count = 1;
                                    if ($users_result && $users_result->num_rows > 0):
                                        while ($u = $users_result->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <td><strong><?php echo $count++; ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="user-avatar <?php echo $u['role'] == 'admin' ? 'avatar-admin' : 'avatar-user'; ?>">
                                                        <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($u['email'] ?? '—'); ?></td>
                                            <td>
                                                <span class="<?php echo $u['role'] == 'admin' ? 'role-badge-admin' : 'role-badge-user'; ?>">
                                                    <?php echo strtoupper($u['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($u['location'] ?? 'N/A'); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                            <td>
                                                <button class="btn-reset-password" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#resetModal"
                                                        data-userid="<?php echo $u['id']; ?>"
                                                        data-username="<?php echo htmlspecialchars($u['username']); ?>"
                                                        data-role="<?php echo htmlspecialchars($u['role']); ?>">
                                                    <i class="fas fa-key"></i> Reset Password
                                                </button>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile;
                                    else: 
                                    ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-users" style="font-size: 2rem;"></i>
                                                <p class="mt-2">No users found</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="resetModalLabel">
                        <i class="fas fa-key"></i> Reset Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="modalUserId">

                        <div class="text-center mb-4">
                            <div class="user-avatar avatar-admin mx-auto" id="modalAvatar" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                A
                            </div>
                            <h5 class="mt-2 mb-0" id="modalUsername">Username</h5>
                            <span class="role-badge-admin" id="modalRole">ADMIN</span>
                        </div>

                        <div class="alert alert-warning py-2 px-3" style="font-size: 0.85rem;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            This will immediately change the user's password. The user will need to use the new password to log in.
                        </div>

                        <div class="mb-3">
                            <label for="modalNewPassword" class="form-label fw-bold">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock text-warning"></i></span>
                                <input type="password" class="form-control" id="modalNewPassword" 
                                       name="new_password" placeholder="Enter new password" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleModalPassword('modalNewPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="modalConfirmPassword" class="form-label fw-bold">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock text-warning"></i></span>
                                <input type="password" class="form-control" id="modalConfirmPassword" 
                                       name="confirm_password" placeholder="Confirm new password" required minlength="6">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleModalPassword('modalConfirmPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div id="modalMatchMessage" style="font-size: 0.8rem; margin-top: 5px; font-weight: 600;"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="modalSubmitBtn" style="background: linear-gradient(135deg, #1E3A8A, #0f2847); border: none;">
                            <i class="fas fa-save"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Populate modal with user data
        document.getElementById('resetModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-userid');
            const username = button.getAttribute('data-username');
            const role = button.getAttribute('data-role');

            document.getElementById('modalUserId').value = userId;
            document.getElementById('modalUsername').textContent = username;
            
            const avatar = document.getElementById('modalAvatar');
            avatar.textContent = username.charAt(0).toUpperCase();
            
            const roleSpan = document.getElementById('modalRole');
            roleSpan.textContent = role.toUpperCase();
            roleSpan.className = role === 'admin' ? 'role-badge-admin' : 'role-badge-user';
            avatar.className = 'user-avatar mx-auto ' + (role === 'admin' ? 'avatar-admin' : 'avatar-user');
            avatar.style.width = '60px';
            avatar.style.height = '60px';
            avatar.style.fontSize = '1.5rem';

            // Clear previous values
            document.getElementById('modalNewPassword').value = '';
            document.getElementById('modalConfirmPassword').value = '';
            document.getElementById('modalMatchMessage').textContent = '';
        });

        // Toggle password visibility in modal
        function toggleModalPassword(fieldId, btn) {
            const field = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password match checking in modal
        const modalNewPass = document.getElementById('modalNewPassword');
        const modalConfirmPass = document.getElementById('modalConfirmPassword');

        if (modalConfirmPass) {
            modalConfirmPass.addEventListener('input', function() {
                const msg = document.getElementById('modalMatchMessage');
                if (this.value.length === 0) {
                    msg.textContent = '';
                } else if (modalNewPass.value === this.value) {
                    msg.textContent = '✓ Passwords match';
                    msg.style.color = '#059669';
                } else {
                    msg.textContent = '✗ Passwords do not match';
                    msg.style.color = '#DC2626';
                }
            });
        }

        // Auto-dismiss alerts
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                try { new bootstrap.Alert(alert).close(); } catch(e) {}
            }, 5000);
        });
    </script>
</body>
</html>
