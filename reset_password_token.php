<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$valid_token = false;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// If POST, get token from hidden field
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
}

// Validate token
if (!empty($token)) {
    $query = "SELECT pr.*, u.username FROM password_resets pr 
              JOIN users u ON pr.user_id = u.id 
              WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $reset_data = $result->fetch_assoc();
        $valid_token = true;
    } else {
        // Check if token exists but is expired or used
        $check_query = "SELECT pr.*, u.username FROM password_resets pr 
                        JOIN users u ON pr.user_id = u.id 
                        WHERE pr.token = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $token);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $check_data = $check_result->fetch_assoc();
            if ($check_data['used'] == 1) {
                $error = 'This reset link has already been used. Please request a new one.';
            } else {
                $error = 'This reset link has expired. Please request a new one.';
            }
        } else {
            $error = 'Invalid reset link. Please request a new one.';
        }
    }
} else {
    $error = 'No reset token provided. Please use the Forgot Password page to generate a reset link.';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Both password fields are required!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match!';
    } else {
        // Hash the new password using bcrypt
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the user's password
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $hashed_password, $reset_data['user_id']);

        if ($update_stmt->execute()) {
            // Mark token as used
            $mark_query = "UPDATE password_resets SET used = 1 WHERE id = ?";
            $mark_stmt = $conn->prepare($mark_query);
            $mark_stmt->bind_param("i", $reset_data['id']);
            $mark_stmt->execute();

            $success = 'Password has been reset successfully! You can now login with your new password.';
            $valid_token = false; // Hide the form after success
        } else {
            $error = 'Error updating password. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 50%, #F97316 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            top: -50px;
            right: -50px;
            animation: float 6s ease-in-out infinite;
        }

        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -100px;
            left: -100px;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(20px); }
        }

        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes checkmark {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }

        .reset-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.8s ease;
            max-width: 500px;
            width: 100%;
            position: relative;
            z-index: 2;
        }

        .reset-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 100%);
            border-radius: 50%;
            color: #F97316;
            font-size: 2.5rem;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(30, 58, 138, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }

        .logo.success-logo {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            animation: checkmark 0.6s ease forwards;
        }

        .reset-header h2 {
            font-size: 1.8rem;
            color: #1E3A8A;
            margin-bottom: 10px;
            font-weight: 900;
        }

        .reset-header p {
            color: #6B7280;
            font-size: 0.95rem;
        }

        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            border: 2px solid #93C5FD;
            border-radius: 50px;
            padding: 8px 20px;
            margin: 15px 0;
            font-weight: 600;
            color: #1E3A8A;
        }

        .user-badge i {
            color: #F97316;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #1E3A8A;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-group .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-group .input-wrapper i.field-icon {
            position: absolute;
            left: 15px;
            color: #F97316;
            font-size: 1.1rem;
            pointer-events: none;
        }

        .form-group .input-wrapper .toggle-password {
            position: absolute;
            right: 15px;
            color: #9CA3AF;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .form-group .input-wrapper .toggle-password:hover {
            color: #F97316;
        }

        .form-group input {
            width: 100%;
            padding: 12px 45px 12px 45px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #F9FAFB;
        }

        .form-group input:focus {
            outline: none;
            border-color: #F97316;
            background: white;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #F97316 0%, #ea580c 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);
            color: white;
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        .alert {
            margin-bottom: 20px;
            border-radius: 10px;
            border: none;
            animation: slideDown 0.4s ease;
        }

        .alert-danger { background: #FEE2E2; color: #DC2626; }
        .alert-success { background: #D1FAE5; color: #065F46; }

        .password-rules {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            color: #64748B;
        }

        .password-rules strong {
            color: #1E3A8A;
            display: block;
            margin-bottom: 5px;
        }

        .password-rules ul {
            margin: 0;
            padding-left: 18px;
        }

        .password-rules li {
            margin-bottom: 3px;
        }

        .back-links {
            text-align: center;
            margin-top: 25px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .back-links a {
            color: #1E3A8A;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .back-links a:hover { color: #F97316; }

        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #E5E7EB;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-bar .fill {
            height: 100%;
            border-radius: 2px;
            transition: all 0.3s ease;
            width: 0%;
        }

        .strength-text {
            font-size: 0.75rem;
            margin-top: 4px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            body { padding: 20px; }
            .reset-container { padding: 30px 20px; }
        }
    </style>
</head>

<body>
    <div class="reset-container">
        <?php if ($success): ?>
            <!-- Success State -->
            <div class="reset-header">
                <div class="logo success-logo"><i class="fas fa-check"></i></div>
                <h2>Password Reset!</h2>
                <p>Your password has been changed successfully.</p>
            </div>

            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <strong>Done!</strong> <?php echo htmlspecialchars($success); ?>
            </div>

            <a href="login.php?reset=success" class="btn-submit" style="text-decoration: none;">
                <i class="fas fa-sign-in-alt"></i> Go to Login
            </a>

        <?php elseif ($valid_token): ?>
            <!-- Reset Form State -->
            <div class="reset-header">
                <div class="logo"><i class="fas fa-lock-open"></i></div>
                <h2>Set New Password</h2>
                <p>Create a strong new password for your account</p>
                <div class="user-badge">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($reset_data['username']); ?>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="password-rules">
                <strong><i class="fas fa-shield-alt"></i> Password Requirements:</strong>
                <ul>
                    <li>Minimum 6 characters</li>
                    <li>Both fields must match</li>
                </ul>
            </div>

            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock field-icon"></i>
                        <input type="password" class="form-control" id="new_password" name="new_password"
                            placeholder="Enter new password" required minlength="6">
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('new_password', this)"></i>
                    </div>
                    <div class="strength-bar"><div class="fill" id="strengthFill"></div></div>
                    <div class="strength-text" id="strengthText"></div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock field-icon"></i>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                            placeholder="Confirm new password" required minlength="6">
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                    <div id="matchMessage" style="font-size: 0.8rem; margin-top: 5px; font-weight: 600;"></div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <i class="fas fa-save"></i> Update Password
                </button>
            </form>

        <?php else: ?>
            <!-- Error State -->
            <div class="reset-header">
                <div class="logo" style="background: linear-gradient(135deg, #DC2626 0%, #B91C1C 100%); color: white;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h2>Invalid Reset Link</h2>
                <p>This reset link is invalid, expired, or has already been used.</p>
            </div>

            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>

            <a href="forgot_password.php" class="btn-submit" style="text-decoration: none; background: linear-gradient(135deg, #F97316 0%, #ea580c 100%);">
                <i class="fas fa-redo"></i> Request New Reset Link
            </a>
        <?php endif; ?>

        <div class="back-links">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(fieldId, icon) {
            const field = document.getElementById(fieldId);
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

        // Password strength indicator
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        if (newPassword) {
            newPassword.addEventListener('input', function () {
                const val = this.value;
                const fill = document.getElementById('strengthFill');
                const text = document.getElementById('strengthText');
                let strength = 0;

                if (val.length >= 6) strength++;
                if (val.length >= 10) strength++;
                if (/[A-Z]/.test(val)) strength++;
                if (/[0-9]/.test(val)) strength++;
                if (/[^A-Za-z0-9]/.test(val)) strength++;

                const colors = ['#DC2626', '#F59E0B', '#F59E0B', '#10B981', '#059669'];
                const labels = ['Weak', 'Fair', 'Fair', 'Strong', 'Very Strong'];
                const widths = ['20%', '40%', '60%', '80%', '100%'];

                if (val.length === 0) {
                    fill.style.width = '0%';
                    text.textContent = '';
                } else {
                    const idx = Math.min(strength, 4);
                    fill.style.width = widths[idx];
                    fill.style.background = colors[idx];
                    text.textContent = labels[idx];
                    text.style.color = colors[idx];
                }

                checkMatch();
            });

            if (confirmPassword) {
                confirmPassword.addEventListener('input', checkMatch);
            }
        }

        function checkMatch() {
            const msg = document.getElementById('matchMessage');
            if (!confirmPassword || !msg) return;
            
            if (confirmPassword.value.length === 0) {
                msg.textContent = '';
            } else if (newPassword.value === confirmPassword.value) {
                msg.textContent = '✓ Passwords match';
                msg.style.color = '#059669';
            } else {
                msg.textContent = '✗ Passwords do not match';
                msg.style.color = '#DC2626';
            }
        }

        // Auto-dismiss alerts
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                try { new bootstrap.Alert(alert).close(); } catch(e) {}
            }, 8000);
        });

        // Input focus animation
        document.querySelectorAll('input[type="password"]').forEach(input => {
            input.addEventListener('focus', function () {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            input.addEventListener('blur', function () {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>

</html>
