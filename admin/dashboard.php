<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('admin');

$user = get_user_info();
$error = '';
$success = '';

// Get admin-specific dashboard statistics
$total_requests = $conn->query("SELECT COUNT(*) as count FROM requests")->fetch_assoc()['count'];
$pending_approval = $conn->query("SELECT COUNT(*) as count FROM requests WHERE approval_status='pending'")->fetch_assoc()['count'];
$approved_requests = $conn->query("SELECT COUNT(*) as count FROM requests WHERE approval_status='approved'")->fetch_assoc()['count'];
$allocated_count = $conn->query("SELECT COUNT(*) as count FROM allocations WHERE delivery_status='delivered'")->fetch_assoc()['count'];
$low_stock = $conn->query("SELECT COUNT(*) as count FROM resources WHERE quantity < 50")->fetch_assoc()['count'];
$total_resources = $conn->query("SELECT SUM(quantity) as count FROM resources")->fetch_assoc()['count'] ?? 0;

// Get pending approval requests for quick actions
$pending_requests = $conn->query("SELECT r.id, r.resource_type, r.quantity, r.priority, d.type as disaster_type 
                                  FROM requests r 
                                  JOIN disasters d ON r.disaster_id = d.id 
                                  WHERE r.approval_status='pending' 
                                  ORDER BY r.priority='Critical' DESC, r.created_at ASC 
                                  LIMIT 5");

// Get recent allocations
$recent_allocations = $conn->query("SELECT a.id, a.delivery_status, r.resource_name, req.resource_type, 
                                    SUM(a.quantity_allocated) as qty
                                    FROM allocations a 
                                    JOIN resources r ON a.resource_id = r.id
                                    JOIN requests req ON a.request_id = req.id
                                    GROUP BY a.id
                                    ORDER BY a.created_at DESC 
                                    LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .dashboard-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            cursor: pointer;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.12);
            border-color: #1E3A8A;
        }
        .dashboard-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .dashboard-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1E3A8A;
            margin: 10px 0;
        }
        .dashboard-card p {
            color: #666;
            margin: 0;
        }
        .card-primary i { color: #007bff; }
        .card-warning i { color: #ffc107; }
        .card-success i { color: #28a745; }
        .card-danger i { color: #dc3545; }
        .card-info i { color: #17a2b8; }

        .approval-alert {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .quick-action-btn {
            margin: 2px;
            padding: 5px 10px;
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
                    <h2 class="mb-4"><i class="fas fa-shield-alt"></i> Admin Dashboard</h2>

                    <!-- Alerts -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($pending_approval > 0): ?>
                        <div class="approval-alert">
                            <i class="fas fa-bell"></i> <strong><?php echo $pending_approval; ?> request(s) pending approval</strong>
                            <a href="../requests/view_requests.php" class="float-end btn btn-sm btn-warning">View Now →</a>
                        </div>
                    <?php endif; ?>

                    <!-- Dashboard Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-2 mb-3" onclick="window.location.href='../requests/view_requests.php';" style="cursor:pointer;">
                            <div class="dashboard-card card-primary">
                                <i class="fas fa-file-alt"></i>
                                <h3><?php echo $total_requests; ?></h3>
                                <p>Total Requests</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3" onclick="window.location.href='../requests/view_requests.php';" style="cursor:pointer;">
                            <div class="dashboard-card card-warning">
                                <i class="fas fa-hourglass-half"></i>
                                <h3><?php echo $pending_approval; ?></h3>
                                <p>Pending Approval</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3" onclick="window.location.href='../allocations/allocate_resource.php';" style="cursor:pointer;">
                            <div class="dashboard-card card-success">
                                <i class="fas fa-check-circle"></i>
                                <h3><?php echo $approved_requests; ?></h3>
                                <p>Approved</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3" onclick="window.location.href='../allocations/view_allocations.php';" style="cursor:pointer;">
                            <div class="dashboard-card card-info">
                                <i class="fas fa-truck"></i>
                                <h3><?php echo $allocated_count; ?></h3>
                                <p>Delivered</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3" onclick="window.location.href='../resources/view_resources.php';" style="cursor:pointer;">
                            <div class="dashboard-card card-danger">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3><?php echo $low_stock; ?></h3>
                                <p>Low Stock</p>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3" onclick="window.location.href='../resources/view_resources.php';" style="cursor:pointer;">
                            <div class="dashboard-card card-primary">
                                <i class="fas fa-boxes"></i>
                                <h3><?php echo number_format($total_resources); ?></h3>
                                <p>Total Units</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-tasks"></i> Pending Approvals</h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Disaster</th>
                                                        <th>Resource</th>
                                                        <th>Qty</th>
                                                        <th>Priority</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($req = $pending_requests->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars(substr($req['disaster_type'], 0, 12)); ?></td>
                                                            <td><?php echo htmlspecialchars(substr($req['resource_type'], 0, 15)); ?></td>
                                                            <td><span class="badge bg-info"><?php echo $req['quantity']; ?></span></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo ($req['priority']=='Critical' ? 'danger' : ($req['priority']=='High' ? 'warning' : 'success')); ?>">
                                                                    <?php echo substr($req['priority'], 0, 1); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="p-3 text-muted text-center mb-0">✓ No pending approvals</p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="../requests/view_requests.php" class="btn btn-sm btn-warning w-100">
                                        <i class="fas fa-arrow-right"></i> Review All Requests
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-truck"></i> Recent Deliveries</h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php if ($recent_allocations && $recent_allocations->num_rows > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Resource</th>
                                                        <th>Qty</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while ($alloc = $recent_allocations->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars(substr($alloc['resource_name'], 0, 15)); ?></td>
                                                            <td><span class="badge bg-info"><?php echo $alloc['qty']; ?></span></td>
                                                            <td>
                                                                <span class="badge bg-<?php echo ($alloc['delivery_status']=='delivered' ? 'success' : ($alloc['delivery_status']=='in_transit' ? 'info' : 'warning')); ?>">
                                                                    <?php echo ucfirst($alloc['delivery_status']); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="p-3 text-muted text-center mb-0">No recent allocations</p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="../allocations/view_allocations.php" class="btn btn-sm btn-success w-100">
                                        <i class="fas fa-arrow-right"></i> View All Allocations
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
