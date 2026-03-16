<?php
// Security middleware - Check if user is logged in and has required role
// Note: session_start() should be called in the main files, not here

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /DMS/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit();
    }
}

function check_role($required_role = 'user') {
    if (!isset($_SESSION['role']) || ($_SESSION['role'] != $required_role && $required_role != 'any')) {
        header("Location: /DMS/dashboard.php?error=Unauthorized Access");
        exit();
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_info() {
    global $conn;
    if (!is_logged_in()) return null;
    
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Session timeout (30 minutes)
$timeout_duration = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_destroy();
    header("Location: /DMS/login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();
?>
