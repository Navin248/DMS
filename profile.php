<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';

check_login();

$user = get_user_info();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_profile') {
        $new_email = isset($_POST['email']) ? trim($_POST['email']) : '';
        
        if (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address!';
        } else {
            // Check if email is already taken by another user
            if (!empty($new_email)) {
                $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $check_stmt->bind_param("si", $new_email, $_SESSION['user_id']);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error = 'This email is already in use by another account!';
                }
            }
            
            if (empty($error)) {
                $email_value = !empty($new_email) ? $new_email : null;
                $update_stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $update_stmt->bind_param("si", $email_value, $_SESSION['user_id']);
                if ($update_stmt->execute()) {
                    $_SESSION['email'] = $email_value;
                    $success = 'Profile updated successfully!';
                    $user = get_user_info(); // Refresh user data
                } else {
                    $error = 'Error updating profile: ' . $conn->error;
                }
            }
        }
    }
    
    if ($action == 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All fields are required!';
        } elseif ($new_password != $confirm_password) {
            $error = 'New passwords do not match!';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long!';
        } else {
            // Verify current password (supports both bcrypt and legacy MD5)
            $current_valid = false;
            if (password_verify($current_password, $user['password'])) {
                $current_valid = true;
            } elseif (md5($current_password) === $user['password']) {
                $current_valid = true;
            }
            
            if (!$current_valid) {
                $error = 'Current password is incorrect!';
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Error updating password: ' . $conn->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row" style="min-height: 100vh;">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 p-4" style="background-color: #F3F4F6;">
                <!-- Header -->
                <?php include 'includes/header.php'; ?>
                
                <div class="container mt-4">
                    <h2><i class="fas fa-user-circle"></i> User Profile</h2>
                    <hr>

                    <!-- Alert Messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Information -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-circle" style="font-size: 80px; color: #F97316;"></i>
                                    <h3 class="mt-3"><?php echo htmlspecialchars($user['username']); ?></h3>
                                    <?php if (!empty($user['email'])): ?>
                                        <p class="text-muted mb-1"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-muted">
                                        <span class="badge bg-primary"><?php echo strtoupper($user['role']); ?></span>
                                    </p>
                                    <small class="text-muted">
                                        Joined: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                    </small>
                                </div>
                            </div>

                            <!-- Update Email Form -->
                            <div class="card mt-3">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0"><i class="fas fa-envelope"></i> Update Email</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="update_profile">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                                   placeholder="Enter your email address">
                                        </div>
                                        <button type="submit" class="btn btn-info text-white">
                                            <i class="fas fa-save"></i> Update Email
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <!-- Change Password Form -->
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-lock"></i> Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="change_password">

                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password</label>
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        </div>

                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Update Password
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Account Information -->
                            <div class="card mt-4">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Account Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Username:</strong>
                                            <p><?php echo htmlspecialchars($user['username']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Email:</strong>
                                            <p><?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?></p>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Role:</strong>
                                            <p><span class="badge bg-primary"><?php echo strtoupper($user['role']); ?></span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Account Created:</strong>
                                            <p><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>Last Updated:</strong>
                                            <p><?php echo date('M d, Y H:i', strtotime($user['updated_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
