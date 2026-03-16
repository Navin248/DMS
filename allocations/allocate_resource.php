<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocate Resource - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row" style="min-height: 100vh;">
            <?php include '../includes/sidebar.php'; ?>
            <div class="col-md-9 p-4" style="background-color: #F3F4F6;">
                <?php include '../includes/header.php'; ?>
                <div class="container mt-4">
                    <h2><i class="fas fa-plus-circle"></i> Allocate Resource</h2>
                    <hr>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Coming Soon!</strong> Allocations management module is being developed.
                    </div>
                    <a href="view_allocations.php" class="btn btn-primary">← Back to Allocations</a>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
