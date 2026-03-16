<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required!';
    } else {
        // Query to find user
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verify password
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                
                // Redirect to originally requested page or dashboard
                if (isset($_GET['redirect'])) {
                    $redirect = $_GET['redirect'];
                    // Ensure it's a relative path within the application
                    if (strpos($redirect, '/DMS/') === 0) {
                        header("Location: " . $redirect);
                    } else {
                        header("Location: /DMS/dashboard.php");
                    }
                } else {
                    header("Location: /DMS/dashboard.php");
                }
                exit();
            } else {
                $error = 'Invalid password!';
            }
        } else {
            $error = 'User not found!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disaster Relief System - Login</title>
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

        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            max-width: 1200px;
            width: 100%;
            padding: 20px;
            position: relative;
            z-index: 2;
        }

        /* Left Side - Info Section */
        .login-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            animation: slideInLeft 0.8s ease;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .login-info h1 {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 15px;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.3);
        }

        .login-info p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            color: #E5E7EB;
            line-height: 1.6;
        }

        .features-list {
            list-style: none;
            margin-bottom: 30px;
        }

        .features-list li {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 1.05rem;
        }

        .features-list i {
            font-size: 1.5rem;
            color: #F97316;
            flex-shrink: 0;
        }

        .helpline-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            border-left: 4px solid #F97316;
        }

        .helpline-box h5 {
            color: #FED7AA;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .helpline-box .number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #F97316;
            font-family: monospace;
            margin: 10px 0;
        }

        .helpline-box p {
            font-size: 0.95rem;
            color: #D1D5DB;
            margin: 0;
        }

        /* Right Side - Login Form */
        .login-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            animation: slideInRight 0.8s ease;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
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

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .login-header h2 {
            font-size: 1.8rem;
            color: #1E3A8A;
            margin-bottom: 10px;
            font-weight: 900;
        }

        .login-header p {
            color: #6B7280;
            font-size: 0.95rem;
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

        .btn-login {
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

        .btn-login:hover {
            background: linear-gradient(135deg, #F97316 0%, #ea580c 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.3);
            color: white;
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .alert {
            margin-bottom: 20px;
            border-radius: 10px;
            border: none;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background: #FEE2E2;
            color: #DC2626;
        }

        .alert-warning {
            background: #FEF3C7;
            color: #D97706;
        }

        .demo-info {
            background: linear-gradient(135deg, #f0f9ff 0%, #fef3c7 100%);
            border-left: 4px solid #F97316;
            padding: 15px;
            border-radius: 8px;
            margin-top: 25px;
            font-size: 0.9rem;
            color: #5A5A5A;
        }

        .demo-info strong {
            color: #1E3A8A;
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            color: #D1D5DB;
            font-size: 0.9rem;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #1E3A8A;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-link a:hover {
            color: #F97316;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }

            .login-info {
                text-align: center;
            }

            .login-info h1 {
                font-size: 2rem;
            }

            .login-container {
                padding: 40px;
            }
        }

        @media (max-width: 768px) {
            body {
                padding: 20px;
            }

            .login-wrapper {
                max-width: 100%;
            }

            .login-container {
                padding: 30px 20px;
            }

            .login-info h1 {
                font-size: 1.8rem;
            }

            .login-info p {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side - Information -->
        <div class="login-info">
            <h1><i class="fas fa-shield-alt"></i> DRMS</h1>
            <p>Disaster Relief Management System</p>
            <p style="font-size: 1rem; color: #D1D5DB;">Coordinating rapid response to disasters and emergencies. Real-time tracking, resource management, and community support.</p>

            <ul class="features-list">
                <li><i class="fas fa-map-location-dot"></i> Real-time disaster tracking</li>
                <li><i class="fas fa-boxes"></i> Inventory management system</li>
                <li><i class="fas fa-users"></i> Resource coordination</li>
                <li><i class="fas fa-bell"></i> Live emergency alerts</li>
                <li><i class="fas fa-chart-line"></i> Analytics & reporting</li>
            </ul>

            <div class="helpline-box">
                <h5><i class="fas fa-phone-volume"></i> Emergency Support</h5>
                <div class="number">112</div>
                <p>Available 24/7 for immediate assistance</p>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-container">
            <div class="login-header">
                <div class="logo"><i class="fas fa-shield-alt"></i></div>
                <h2>Welcome Back</h2>
                <p>Access your disaster relief dashboard</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <strong>Error!</strong> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['timeout'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-clock"></i> <strong>Session Expired!</strong> Please login again.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter your username" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </button>
            </form>

            <div class="back-link">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            setTimeout(() => bsAlert.close(), 5000);
        });

        // Add focus animation to form
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
