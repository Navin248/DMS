<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$error = '';
$success = '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $query = "DELETE FROM allocations WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success = 'Allocation deleted successfully!';
    } else {
        $error = 'Error deleting allocation: ' . $conn->error;
    }
}

// Handle Status Update (admin quick actions)
if (isset($_GET['mark_status']) && isset($_GET['id'])) {
    $allocation_id = (int)$_GET['id'];
    $new_status = $_GET['mark_status'];
    $allowed = ['pending', 'in_transit', 'delivered'];

    if (in_array($new_status, $allowed)) {
        $date = date('Y-m-d H:i:s');
        $fulfilled_by = $_SESSION['user_id'];

        $update_query = "UPDATE allocations SET delivery_status = ?, fulfilled_by = ?, date = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sisi", $new_status, $fulfilled_by, $date, $allocation_id);

        if ($update_stmt->execute()) {
            if ($new_status === 'delivered') {
                // mark request delivered if all allocations are delivered
                $req_check = $conn->prepare("SELECT request_id FROM allocations WHERE id = ?");
                $req_check->bind_param("i", $allocation_id);
                $req_check->execute();
                $req_check->bind_result($req_id);
                $req_check->fetch();
                $req_check->close(); // CLOSE THIS STATEMENT
                
                if ($req_id) {
                    $still_pending = $conn->prepare("SELECT COUNT(*) as cnt FROM allocations WHERE request_id = ? AND delivery_status != 'delivered'");
                    $still_pending->bind_param("i", $req_id);
                    $still_pending->execute();
                    $still_pending->bind_result($remaining);
                    $still_pending->fetch();
                    $still_pending->close(); // CLOSE THIS STATEMENT
                    
                    if ($remaining == 0) {
                        $req_update = $conn->prepare("UPDATE requests SET status = 'delivered' WHERE id = ?");
                        $req_update->bind_param("i", $req_id);
                        $req_update->execute();
                        $req_update->close(); // CLOSE THIS STATEMENT
                    }
                }
            }
            $update_stmt->close(); // CLOSE UPDATE STATEMENT
            $success = 'Allocation status updated to ' . ucfirst(str_replace('_', ' ', $new_status)) . '!';
        } else {
            $error = 'Error updating allocation status: ' . $conn->error;
        }
    } else {
        $error = 'Invalid status specified.';
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
    $where .= " AND a.delivery_status = '$filter_status'";
}

// Get all allocations with related data
$query = "SELECT a.*, 
                 r.resource_name, 
                 req.location as request_location,
                 d.type as disaster_type
          FROM allocations a 
          JOIN resources r ON a.resource_id = r.id
          JOIN requests req ON a.request_id = req.id
          LEFT JOIN disasters d ON req.disaster_id = d.id
          $where
          ORDER BY a.date DESC";
$result = $conn->query($query);

// Get summary stats
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN delivery_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN delivery_status = 'in_transit' THEN 1 ELSE 0 END) as in_transit_count,
    SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered_count,
    SUM(quantity_allocated) as total_units_allocated
FROM allocations";
$stats = $conn->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocations - Disaster Relief System</title>
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
        .status-in_transit {
            background-color: #cfe2ff;
            color: #084298;
        }
        .status-delivered {
            background-color: #d1e7dd;
            color: #0f5132;
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
                        <h2><i class="fas fa-dolly"></i> Resource Allocations</h2>
                        <a href="allocate_resource.php" class="btn btn-success">
                            <i class="fas fa-plus"></i> New Allocation
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
                        <div class="col-md-3 mb-3" onclick="location.href='view_allocations.php';" style="cursor:pointer;">
                            <div class="summary-card">
                                <h4><?php echo $stats['total'] ?? 0; ?></h4>
                                <p>Total Allocations</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" onclick="location.href='view_allocations.php?status=pending';" style="cursor:pointer;">
                            <div class="summary-card">
                                <h4><?php echo $stats['pending_count'] ?? 0; ?></h4>
                                <p>Pending</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" onclick="location.href='view_allocations.php?status=in_transit';" style="cursor:pointer;">
                            <div class="summary-card">
                                <h4><?php echo $stats['in_transit_count'] ?? 0; ?></h4>
                                <p>In Transit</p>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3" onclick="location.href='view_allocations.php?status=delivered';" style="cursor:pointer;">
                            <div class="summary-card">
                                <h4><?php echo $stats['delivered_count'] ?? 0; ?></h4>
                                <p>Delivered</p>
                            </div>
                        </div>
                    </div>

                    <!-- Allocations Table -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-table"></i> All Allocations</h5>
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
                                                <th>Quantity</th>
                                                <th>Location</th>
                                                <th>Delivery Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr class="table-row-clickable" onclick="window.location.href='edit_allocation.php?id=<?php echo $row['id']; ?>';" style="cursor:pointer;">
                                                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                                                    <td>
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <?php echo htmlspecialchars($row['disaster_type']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['resource_name']); ?></td>
                                                    <td><span class="badge bg-info"><?php echo $row['quantity_allocated']; ?></span></td>
                                                    <td><?php echo htmlspecialchars($row['request_location']); ?></td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $row['delivery_status']; ?>">
                                                            <?php echo str_replace('_', ' ', ucfirst($row['delivery_status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                                    <td onclick="event.stopPropagation();">
                                                        <?php if ($row['delivery_status'] === 'pending'): ?>
                                                            <button class="btn btn-sm btn-info" onclick="markInTransit(<?php echo $row['id']; ?>)" title="Mark as In Transit">
                                                                <i class="fas fa-truck"></i>
                                                            </button>
                                                        <?php elseif ($row['delivery_status'] === 'in_transit'): ?>
                                                            <button class="btn btn-sm btn-success" onclick="markDelivered(<?php echo $row['id']; ?>)" title="Mark as Delivered">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <a href="edit_allocation.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this allocation?');">
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
                                    <p class="mt-2">No allocations found. <a href="allocate_resource.php">Create one →</a></p>
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

        function markDelivered(allocationId) {
            if (confirm('Mark this allocation as Delivered?')) {
                window.location.href = '?id=' + allocationId + '&mark_status=delivered';
            }
        }

        function markInTransit(allocationId) {
            if (confirm('Mark this allocation as In Transit?')) {
                window.location.href = '?id=' + allocationId + '&mark_status=in_transit';
            }
        }
    </script>
</body>
</html>
