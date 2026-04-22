<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$error = '';
$success = '';

// Check if user is admin for approval actions
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
$current_user_id = $_SESSION['user_id'] ?? 1;

// Handle Approve Action
if (isset($_GET['approve_id']) && $is_admin) {
    $approve_id = (int)$_GET['approve_id'];
    $query = "UPDATE requests SET approval_status='approved', approved_by=?, approval_date=NOW() WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $current_user_id, $approve_id);
    if ($stmt->execute()) {
        $success = 'Request approved successfully!';
    } else {
        $error = 'Error approving request: ' . $conn->error;
    }
}

// Handle Reject Action
if (isset($_GET['reject_id']) && $is_admin) {
    $reject_id = (int)$_GET['reject_id'];
    $query = "UPDATE requests SET approval_status='rejected', approved_by=?, approval_date=NOW() WHERE id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $current_user_id, $reject_id);
    if ($stmt->execute()) {
        $success = 'Request rejected successfully!';
    } else {
        $error = 'Error rejecting request: ' . $conn->error;
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $query = "DELETE FROM requests WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success = 'Request deleted successfully!';
    } else {
        $error = 'Error deleting request: ' . $conn->error;
    }
}

// Check for success message from URL
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}

// Build WHERE clause for status filtering
$where = "WHERE 1=1";
if (isset($_GET['status']) && $_GET['status'] !== 'all') {
    $filter_status = $conn->real_escape_string($_GET['status']);
    if (in_array($filter_status, ['pending', 'approved', 'rejected'])) {
        $where .= " AND r.approval_status = '$filter_status'";
    } else {
        $where .= " AND r.status = '$filter_status'";
    }
}

// Get all requests with related disaster and user info
$query = "SELECT r.*, d.type as disaster_type, d.location as disaster_location, 
                 u.username as requester_name
          FROM requests r 
          LEFT JOIN disasters d ON r.disaster_id = d.id
          LEFT JOIN users u ON r.user_id = u.id
          $where
          ORDER BY r.priority='Critical' DESC, r.priority='High' DESC, r.created_at DESC";
$result = $conn->query($query);

// Get summary stats by approval_status
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
FROM requests";
$stats = $conn->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requests - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .table-row-clickable {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        .table-row-clickable:hover {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #997404;
        }
        .status-allocated {
            background-color: #cfe2ff;
            color: #084298;
        }
        .status-completed {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .approval-badge-pending {
            background-color: #ffc107;
            color: #000;
        }
        .approval-badge-approved {
            background-color: #28a745;
            color: white;
        }
        .approval-badge-rejected {
            background-color: #dc3545;
            color: white;
        }
        .priority-high {
            color: #dc3545;
            font-weight: 600;
        }
        .priority-medium {
            color: #ff9800;
            font-weight: 600;
        }
        .priority-low {
            color: #28a745;
            font-weight: 600;
        }
        .summary-card {
            border-radius: 8px;
            padding: 15px;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-color: #1E3A8A;
        }
        .summary-card h4 {
            color: #1E3A8A;
            margin: 10px 0 5px 0;
        }
        .summary-card p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-list-check"></i> Relief Requests</h2>
                        <a href="create_request.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> New Request
                        </a>
                    </div>

                    <!-- Alert Messages -->
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3" onclick="window.location.href='view_requests.php?status=all';" style="cursor:pointer;">
                            <div class="summary-card">
                                <h4><?php echo $stats['total'] ?? 0; ?></h4>
                                <p>Total Requests</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" onclick="window.location.href='view_requests.php?status=pending';" style="cursor:pointer;">
                            <div class="summary-card">
                                <h4><?php echo $stats['pending_count'] ?? 0; ?></h4>
                                <p>Pending Approval</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" onclick="window.location.href='view_requests.php?status=approved';" style="cursor:pointer;">
                            <div class="summary-card">
                                <h4><?php echo $stats['approved_count'] ?? 0; ?></h4>
                                <p>Approved</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" onclick="window.location.href='view_requests.php?status=rejected';" style="cursor:pointer;">
                            <div class="summary-card">
                                <h4><?php echo $stats['rejected_count'] ?? 0; ?></h4>
                                <p>Rejected</p>
                            </div>
                        </div>
                    </div>

                    <!-- Requests Table -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-table"></i> All Requests</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Disaster</th>
                                                <th>Resource</th>
                                                <th>Qty</th>
                                                <th>Priority</th>
                                                <th>Approval Status</th>
                                                <th>Request Status</th>
                                                <th>Requester</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr class="table-row-clickable" onclick="window.location.href='edit_request.php?id=<?php echo $row['id']; ?>';" style="cursor:pointer;">
                                                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                                                    <td>
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <?php 
                                                        if ($row['disaster_type']) {
                                                            echo htmlspecialchars($row['disaster_type'] . ' - ' . $row['disaster_location']);
                                                        } else {
                                                            echo '<span class="badge bg-secondary">New Incident</span><br>' . htmlspecialchars($row['location']);
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars(substr($row['resource_type'], 0, 15)); ?></td>
                                                    <td><span class="badge bg-info"><?php echo $row['quantity']; ?></span></td>
                                                    <td>
                                                        <span class="priority-<?php echo strtolower($row['priority']); ?>">
                                                            <?php echo substr($row['priority'], 0, 1); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge approval-badge-<?php echo $row['approval_status']; ?>">
                                                            <?php echo ucfirst($row['approval_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $row['status']; ?>">
                                                            <?php echo ucfirst($row['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['requester_name'] ?? 'Unknown'); ?></td>
                                                    <td onclick="event.stopPropagation();">
                                                        <?php if ($is_admin && $row['approval_status'] == 'pending'): ?>
                                                            <a href="?approve_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                            <a href="?reject_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Reject">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="edit_request.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this request?');" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fas fa-inbox" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No requests found. <a href="create_request.php">Create one →</a></p>
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
