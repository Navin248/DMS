<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('user');

$user = get_user_info();
$worker_id = $_SESSION['user_id'];

// Get filter from URL
$filter_status = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filter_approval = isset($_GET['approval']) ? $_GET['approval'] : 'all';

// Build WHERE clause
$where = "WHERE r.user_id=$worker_id";
if ($filter_status !== 'all') {
    $where .= " AND r.status='$filter_status'";
}
if ($filter_approval !== 'all') {
    $where .= " AND r.approval_status='$filter_approval'";
}

// Get requests
$query = "SELECT r.id, r.resource_type, r.quantity, r.priority, r.status, r.approval_status, 
          r.created_at, r.approved_by, r.approval_date, r.location, d.type as disaster_type, d.location as disaster_location,
          u.username as approved_by_name,
          COALESCE(a.delivery_status, 'pending') as delivery_status, a.id as has_allocation
          FROM requests r
          LEFT JOIN disasters d ON r.disaster_id = d.id
          LEFT JOIN users u ON r.approved_by = u.id
          LEFT JOIN allocations a ON r.id = a.request_id
          $where
          ORDER BY r.created_at DESC";

$requests = $conn->query($query);
$total = $requests->num_rows;

// Get statistics
$stats = [];
$stats['pending'] = $conn->query("SELECT COUNT(*) as count FROM requests WHERE user_id=$worker_id AND status='pending'")->fetch_assoc()['count'];
$stats['allocated'] = $conn->query("SELECT COUNT(*) as count FROM requests WHERE user_id=$worker_id AND status='allocated'")->fetch_assoc()['count'];
$stats['in_transit'] = $conn->query("SELECT COUNT(*) as count FROM requests WHERE user_id=$worker_id AND status='in_transit'")->fetch_assoc()['count'];
$stats['delivered'] = $conn->query("SELECT COUNT(*) as count FROM requests WHERE user_id=$worker_id AND status='delivered'")->fetch_assoc()['count'];

$stats['pending_approval'] = $conn->query("SELECT COUNT(*) as count FROM requests WHERE user_id=$worker_id AND approval_status='pending'")->fetch_assoc()['count'];
$stats['approved'] = $conn->query("SELECT COUNT(*) as count FROM requests WHERE user_id=$worker_id AND approval_status='approved'")->fetch_assoc()['count'];
$stats['rejected'] = $conn->query("SELECT COUNT(*) as count FROM requests WHERE user_id=$worker_id AND approval_status='rejected'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .quick-stats {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .stat-badge {
            background: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s;
        }
        .stat-badge:hover {
            border-color: #007bff;
        }
        .stat-badge.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .priority-critical { color: #dc3545; font-weight: bold; }
        .priority-high { color: #fd7e14; font-weight: bold; }
        .priority-medium { color: #ffc107; }
        .priority-low { color: #28a745; }

        .approval-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .approval-badge-pending { background: #fff3cd; color: #856404; }
        .approval-badge-approved { background: #d4edda; color: #155724; }
        .approval-badge-rejected { background: #f8d7da; color: #721c24; }
        
        .request-row {
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 12px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .request-row:hover {
            background: #f8f9fa;
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0, 123, 255, 0.15);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }
        .status-pending { background: #ffc107; }
        .status-allocated { background: #007bff; }
        .status-in_transit { background: #17a2b8; }
        .status-delivered { background: #28a745; }
        .status-completed { background: #28a745; }

        .request-row {
            padding: 15px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 12px;
            background: white;
            transition: all 0.3s;
            cursor: pointer;
            user-select: none;
        }
        .request-row:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            background-color: #f8f9fa;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-list"></i> My Requests</h2>
                        <a href="dashboard.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>

                    <!-- Quick Stats -->
                    <div class="quick-stats">
                        <a href="?filter=all&approval=all" class="stat-badge <?php echo ($filter_status === 'all' && $filter_approval === 'all') ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i> All Requests (<?php echo $total; ?>)
                        </a>
                        <a href="?filter=pending&approval=all" class="stat-badge <?php echo ($filter_status === 'pending' && $filter_approval === 'all') ? 'active' : ''; ?>">
                            <i class="fas fa-hourglass-half"></i> Pending (<?php echo $stats['pending']; ?>)
                        </a>
                        <a href="?filter=allocated&approval=all" class="stat-badge <?php echo ($filter_status === 'allocated' && $filter_approval === 'all') ? 'active' : ''; ?>">
                            <i class="fas fa-cube"></i> Allocated (<?php echo $stats['allocated']; ?>)
                        </a>
                        <a href="?filter=in_transit&approval=all" class="stat-badge <?php echo ($filter_status === 'in_transit' && $filter_approval === 'all') ? 'active' : ''; ?>">
                            <i class="fas fa-truck"></i> In Transit (<?php echo $stats['in_transit']; ?>)
                        </a>
                        <a href="?filter=delivered&approval=all" class="stat-badge <?php echo ($filter_status === 'delivered' && $filter_approval === 'all') ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Delivered (<?php echo $stats['delivered']; ?>)
                        </a>
                    </div>

                    <!-- Approval Filter -->
                    <div class="quick-stats">
                        <a href="?filter=all&approval=all" class="stat-badge <?php echo ($filter_approval === 'all') ? 'active' : ''; ?>">
                            All Approvals
                        </a>
                        <a href="?filter=all&approval=pending" class="stat-badge <?php echo ($filter_approval === 'pending') ? 'active' : ''; ?>">
                            Pending Approval (<?php echo $stats['pending_approval']; ?>)
                        </a>
                        <a href="?filter=all&approval=approved" class="stat-badge <?php echo ($filter_approval === 'approved') ? 'active' : ''; ?>">
                            Approved (<?php echo $stats['approved']; ?>)
                        </a>
                        <a href="?filter=all&approval=rejected" class="stat-badge <?php echo ($filter_approval === 'rejected') ? 'active' : ''; ?>">
                            Rejected (<?php echo $stats['rejected']; ?>)
                        </a>
                    </div>

                    <!-- Requests List -->
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-file-alt"></i> Requests (<?php echo $total; ?>)</h5>
                            <a href="../requests/create_request.php" class="btn btn-light btn-sm">
                                <i class="fas fa-plus"></i> New Request
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if ($total > 0): ?>
                                <?php while ($req = $requests->fetch_assoc()): ?>
                                    <div class="request-row" onclick="window.location.href='view_request_detail.php?id=<?php echo $req['id']; ?>';">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <h6 class="mb-2"><?php echo htmlspecialchars($req['resource_type']); ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker"></i> <?php echo htmlspecialchars($req['location']); ?> 
                                                    <?php if ($req['disaster_type']): ?>
                                                        <br><i class="fas fa-exclamation"></i> (<?php echo htmlspecialchars($req['disaster_type']); ?>)
                                                    <?php else: ?>
                                                        <br><span class="badge bg-secondary">New Incident</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <div class="mb-1">
                                                    <strong><?php echo $req['quantity']; ?></strong> units
                                                </div>
                                                <span class="priority-<?php echo strtolower($req['priority']); ?>">
                                                    <i class="fas fa-exclamation-circle"></i> <?php echo ucfirst($req['priority']); ?>
                                                </span>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <span class="approval-badge approval-badge-<?php echo $req['approval_status']; ?>">
                                                    <?php echo ucfirst($req['approval_status']); ?>
                                                </span>
                                                <?php if ($req['approval_status'] !== 'pending' && $req['approved_by_name']): ?>
                                                    <small class="d-block text-muted mt-1">by <?php echo htmlspecialchars($req['approved_by_name']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-2 text-center">
                                                <span class="status-badge status-<?php echo strtolower($req['delivery_status']); ?>">
                                                    <?php if ($req['has_allocation']): ?>
                                                        <!-- Show delivery status if allocated -->
                                                        <?php echo ucfirst(str_replace('_', ' ', $req['delivery_status'])); ?>
                                                    <?php else: ?>
                                                        <!-- Show request status if not allocated -->
                                                        <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                                                    <?php endif; ?>
                                                </span>
                                                <small class="d-block text-muted mt-1"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></small>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <a href="view_request_detail.php?id=<?php echo $req['id']; ?>" class="btn btn-info btn-sm" onclick="event.stopPropagation();">
                                                    <i class="fas fa-eye"></i> View Details
                                                </a>
                                                <?php if ($req['approval_status'] === 'pending'): ?>
                                                    <a href="../requests/edit_request.php?id=<?php echo $req['id']; ?>" class="btn btn-warning btn-sm" onclick="event.stopPropagation();">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle"></i> No requests found. 
                                    <a href="../requests/create_request.php" class="alert-link">Create one now</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
