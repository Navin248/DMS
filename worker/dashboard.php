<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('user');

$user = get_user_info();
$worker_id = $_SESSION['user_id'];

// Get worker-specific accurate statistics
$stats_query = "SELECT 
    COUNT(DISTINCT CASE WHEN r.approval_status = 'pending' THEN r.id END) as pending,
    COUNT(DISTINCT CASE WHEN r.approval_status = 'approved' THEN r.id END) as approved,
    COUNT(DISTINCT CASE WHEN a.delivery_status = 'pending' OR (r.status = 'allocated' AND a.id IS NULL) THEN r.id END) as allocated,
    COUNT(DISTINCT CASE WHEN a.delivery_status = 'delivered' OR r.status = 'delivered' THEN r.id END) as delivered,
    COUNT(DISTINCT CASE WHEN r.approval_status = 'rejected' THEN r.id END) as rejected
    FROM requests r
    LEFT JOIN allocations a ON r.id = a.request_id
    WHERE r.user_id=$worker_id";

$stats_result = $conn->query($stats_query)->fetch_assoc();

$my_pending = $stats_result['pending'] ?? 0;
$my_approved = $stats_result['approved'] ?? 0;
$my_allocated = $stats_result['allocated'] ?? 0;
$my_delivered = $stats_result['delivered'] ?? 0;
$my_rejected = $stats_result['rejected'] ?? 0;
$total_requests = $conn->query("SELECT COUNT(*) as count FROM requests WHERE user_id=$worker_id")->fetch_assoc()['count'];

// Get my recent requests with real-time status
$my_requests = $conn->query("SELECT r.id, r.resource_type, r.quantity, r.priority, r.status, r.approval_status, r.created_at, r.location, d.type as disaster_type,
                            COALESCE((SELECT MAX(delivery_status) FROM allocations WHERE request_id = r.id), r.status) as real_status
                            FROM requests r
                            LEFT JOIN disasters d ON r.disaster_id = d.id
                            WHERE r.user_id=$worker_id
                            ORDER BY r.created_at DESC
                            LIMIT 5");

// Get allocation status for my requests
$my_allocations = $conn->query("SELECT a.id, a.delivery_status, a.created_at, r.resource_name, 
                               a.quantity_allocated, req.resource_type
                               FROM allocations a
                               JOIN resources r ON a.resource_id = r.id
                               JOIN requests req ON a.request_id = req.id
                               WHERE req.user_id=$worker_id
                               ORDER BY a.created_at DESC
                               LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Dashboard - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .dashboard-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .dashboard-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .dashboard-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .dashboard-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0 5px 0;
        }
        .dashboard-card p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        .card-pending { border-top: 4px solid #ffc107; }
        .card-pending i { color: #ffc107; }
        .card-approved { border-top: 4px solid #28a745; }
        .card-approved i { color: #28a745; }
        .card-allocated { border-top: 4px solid #007bff; }
        .card-allocated i { color: #007bff; }
        .card-delivered { border-top: 4px solid #17a2b8; }
        .card-delivered i { color: #17a2b8; }
        .card-rejected { border-top: 4px solid #dc3545; }
        .card-rejected i { color: #dc3545; }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
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
                    <!-- Page Title -->
                    <h2 class="mb-4"><i class="fas fa-user"></i> Worker Dashboard</h2>

                    <!-- Welcome Message -->
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Welcome, <?php echo htmlspecialchars($user['username']); ?>!</strong> 
                        Track your requests and allocations here.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <!-- My Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-2 mb-3" onclick="window.location.href='my_requests.php';\" style="cursor:pointer;">
                            <div class="dashboard-card card-pending">
                                <i class="fas fa-hourglass-half"></i>
                                <h3><?php echo $my_pending; ?></h3>
                                <p>Pending</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3" onclick="window.location.href='my_requests.php';\" style="cursor:pointer;">
                            <div class="dashboard-card card-approved">
                                <i class="fas fa-check-circle"></i>
                                <h3><?php echo $my_approved; ?></h3>
                                <p>Approved</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3" onclick="window.location.href='my_requests.php';\" style="cursor:pointer;">
                            <div class="dashboard-card card-allocated">
                                <i class="fas fa-cube"></i>
                                <h3><?php echo $my_allocated; ?></h3>
                                <p>Allocated</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3" onclick="window.location.href='my_requests.php';\" style="cursor:pointer;">
                            <div class="dashboard-card card-delivered">
                                <i class="fas fa-truck"></i>
                                <h3><?php echo $my_delivered; ?></h3>
                                <p>Delivered</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3" onclick="window.location.href='my_requests.php';\" style="cursor:pointer;">
                            <div class="dashboard-card card-rejected">
                                <i class="fas fa-times-circle"></i>
                                <h3><?php echo $my_rejected; ?></h3>
                                <p>Rejected</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3" onclick="window.location.href='my_requests.php';\" style="cursor:pointer;">
                            <div class="dashboard-card">
                                <i class="fas fa-chart-bar" style="color: #1E3A8A;"></i>
                                <h3><?php echo $total_requests; ?></h3>
                                <p>Total</p>
                            </div>
                        </div>
                    </div>

                    <!-- My Requests and Allocations -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> My Recent Requests</h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php if ($my_requests && $my_requests->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Resource</th>
                                                        <th>Qty</th>
                                                        <th>Approval</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($req = $my_requests->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars(substr($req['resource_type'], 0, 12)); ?></td>
                                                            <td><span class="badge bg-info"><?php echo $req['quantity']; ?></span></td>
                                                            <td>
                                                                <span class="status-badge" style="background: <?php echo ($req['approval_status']=='approved' ? '#28a745' : ($req['approval_status']=='pending' ? '#ffc107' : '#dc3545')); ?>; color: white;">
                                                                    <?php echo substr(ucfirst($req['approval_status']), 0, 1); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php 
                                                                    $r_status = $req['real_status'];
                                                                    if ($r_status == 'pending') $bg = '#ffc107';
                                                                    elseif ($r_status == 'allocated') $bg = '#007bff';
                                                                    elseif ($r_status == 'in_transit') $bg = '#17a2b8';
                                                                    elseif ($r_status == 'delivered') $bg = '#28a745';
                                                                    else $bg = '#6c757d';
                                                                ?>
                                                                <span class="status-badge" style="background: <?php echo $bg; ?>; color: white;">
                                                                    <?php echo substr(ucfirst(str_replace('_', ' ', $r_status)), 0, 1); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="p-3 text-muted text-center mb-0">No requests yet</p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="my_requests.php" class="btn btn-sm btn-primary w-100">
                                        <i class="fas fa-arrow-right"></i> View All My Requests
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-truck"></i> My Allocations</h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php if ($my_allocations && $my_allocations->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Resource</th>
                                                        <th>Qty</th>
                                                        <th>Status</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($alloc = $my_allocations->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars(substr($alloc['resource_name'], 0, 12)); ?></td>
                                                            <td><span class="badge bg-info"><?php echo $alloc['quantity_allocated']; ?></span></td>
                                                            <td>
                                                                <span class="status-badge" style="background: <?php echo ($alloc['delivery_status']=='delivered' ? '#28a745' : ($alloc['delivery_status']=='in_transit' ? '#17a2b8' : '#ffc107')); ?>; color: white;">
                                                                    <?php echo substr(ucfirst($alloc['delivery_status']), 0, 1); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo date('M d', strtotime($alloc['created_at'])); ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="p-3 text-muted text-center mb-0">No allocations yet</p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="../requests/create_request.php" class="btn btn-sm btn-success w-100">
                                        <i class="fas fa-plus"></i> Create New Request
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    </script>
</body>
</html>
