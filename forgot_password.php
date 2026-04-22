<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_input = isset($_POST['username']) ? trim($_POST['username']) : '';

    if (empty($login_input)) {
        $error = 'Please enter your username or email!';
    } else {
        // Check if user exists by email or username
        if (strpos($login_input, '@') !== false) {
            $query = "SELECT id, username, email FROM users WHERE email = ?";
        } else {
            $query = "SELECT id, username, email FROM users WHERE username = ?";
        }
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $login_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Invalidate any previous unused tokens for this user
            $invalidate_query = "UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0";
            $inv_stmt = $conn->prepare($invalidate_query);
            $inv_stmt->bind_param("i", $user['id']);
            $inv_stmt->execute();

            // Generate secure token
            $token = bin2hex(random_bytes(32));

            // Store token in database — use MySQL's NOW() + 1 HOUR to avoid timezone mismatch
            $insert_query = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))";
            $ins_stmt = $conn->prepare($insert_query);
            $ins_stmt->bind_param("is", $user['id'], $token);

            if ($ins_stmt->execute()) {
                // Build reset link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $reset_link = $protocol . '://' . $host . '/DMS/reset_password_token.php?token=' . $token;
                $success = 'Password reset link has been generated successfully! Use the link below to reset your password.';
            } else {
                $error = 'Something went wrong. Please try again.';
            }
        } else {
            $error = 'User not found! Please check your username or email and try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Disaster Relief System</title>
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

        /* Animated background elements */
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

        .forgot-container {
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

        .forgot-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #F97316 0%, #ea580c 100%);
            border-radius: 50%;
            color: white;
            font-size: 2.5rem;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }

        .forgot-header h2 {
            font-size: 1.8rem;
            color: #1E3A8A;
            margin-bottom: 10px;
            font-weight: 900;
        }

        .forgot-header p {
            color: #6B7280;
            font-size: 0.95rem;
            line-height: 1.5;
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

        .form-group .input-wrapper i {
            position: absolute;
            left: 15px;
            color: #F97316;
            font-size: 1.1rem;
            pointer-events: none;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
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

        .btn-reset {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #F97316 0%, #ea580c 100%);
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

        .btn-reset:hover {
            background: linear-gradient(135deg, #1E3A8A 0%, #0f2847 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(30, 58, 138, 0.3);
            color: white;
        }

        .btn-reset:active {
            transform: translateY(-1px);
        }

        .alert {
            margin-bottom: 20px;
            border-radius: 10px;
            border: none;
            animation: slideDown 0.4s ease;
        }

        .alert-danger {
            background: #FEE2E2;
            color: #DC2626;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
        }

        .reset-link-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-left: 4px solid #1E3A8A;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            word-break: break-all;
        }

        .reset-link-box label {
            display: block;
            font-weight: 700;
            color: #1E3A8A;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .reset-link-box a {
            color: #F97316;
            font-weight: 600;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.3s ease;
        }

        .reset-link-box a:hover {
            color: #1E3A8A;
            text-decoration: underline;
        }

        .reset-link-box .copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            padding: 8px 16px;
            background: #1E3A8A;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .reset-link-box .copy-btn:hover {
            background: #F97316;
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

        .back-links a:hover {
            color: #F97316;
        }

        .info-box {
            background: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            color: #92400E;
        }

        .info-box i {
            color: #F59E0B;
            margin-right: 6px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 20px; }
            .forgot-container { padding: 30px 20px; }
        }
    </style>
</head>

<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <div class="logo"><i class="fas fa-key"></i></div>
            <h2>Forgot Password?</h2>
            <p>Don't worry! Enter your username or email below and we'll generate a password reset link for you.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <strong>Success!</strong> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($reset_link)): ?>
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                Enter the username or email associated with your account. A reset link will be generated for you.
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-info-circle" style="color: #F97316;"></i> Username or Email
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="username" name="username"
                            placeholder="Enter your username or email" required autofocus>
                    </div>
                </div>

                <button type="submit" class="btn-reset">
                    <i class="fas fa-paper-plane"></i> Generate Reset Link
                </button>
            </form>
        <?php else: ?>
            <div class="reset-link-box">
                <label><i class="fas fa-link"></i> Your Password Reset Link:</label>
                <a href="<?php echo htmlspecialchars($reset_link); ?>" id="resetLink">
                    <?php echo htmlspecialchars($reset_link); ?>
                </a>
                <br>
                <button class="copy-btn" onclick="copyLink()">
                    <i class="fas fa-copy"></i> <span id="copyText">Copy Link</span>
                </button>
            </div>

            <div class="info-box" style="margin-top: 15px;">
                <i class="fas fa-clock"></i>
                This link will expire in <strong>1 hour</strong>. Use it to set a new password.
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <a href="<?php echo htmlspecialchars($reset_link); ?>" class="btn-reset" style="text-decoration: none; display: inline-flex; width: auto; padding: 12px 30px;">
                    <i class="fas fa-arrow-right"></i> Reset My Password Now
                </a>
            </div>
        <?php endif; ?>

        <div class="back-links">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyLink() {
            const link = document.getElementById('resetLink').href;
            navigator.clipboard.writeText(link).then(() => {
                const copyText = document.getElementById('copyText');
                copyText.textContent = 'Copied!';
                setTimeout(() => { copyText.textContent = 'Copy Link'; }, 2000);
            });
        }

        // Auto-dismiss alerts after 8 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 8000);
        });

        // Input focus animation
        document.querySelectorAll('input').forEach(input => {
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
