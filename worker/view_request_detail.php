<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('user');

$user = get_user_info();
$worker_id = $_SESSION['user_id'];

// Validate request belongs to worker
$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$request = $conn->query("SELECT r.*, d.type as disaster_type, d.location as disaster_location, 
                        u.username as who_approved
                        FROM requests r
                        LEFT JOIN disasters d ON r.disaster_id = d.id
                        LEFT JOIN users u ON r.approved_by = u.id
                        WHERE r.id=$request_id AND r.user_id=$worker_id")->fetch_assoc();

$success = '';
$error = '';

if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}

if (isset($_GET['error'])) {
    $error = urldecode($_GET['error']);
}

if (!$request) {
    header("Location: my_requests.php");
    exit();
}

// Handle Coordinator Receipt Confirmation
if (isset($_GET['confirm_receive']) && isset($_GET['alloc_id'])) {
    $alloc_id = (int)$_GET['alloc_id'];

    // Only allow marking as received for allocations in transit for this request/user
    $check_alloc = $conn->prepare("SELECT a.id FROM allocations a JOIN requests r ON a.request_id = r.id WHERE a.id = ? AND a.request_id = ? AND a.delivery_status = 'in_transit' AND r.user_id = ?");
    $check_alloc->bind_param("iii", $alloc_id, $request_id, $worker_id);
    $check_alloc->execute();
    $check_alloc->store_result();

    if ($check_alloc->num_rows > 0) {
        $update_alloc = $conn->prepare("UPDATE allocations SET delivery_status = 'delivered', date = ? WHERE id = ?");
        $date_now = date('Y-m-d H:i:s');
        $update_alloc->bind_param("si", $date_now, $alloc_id);
        if ($update_alloc->execute()) {
            // Update request status if all allocations delivered
            $pending_allocs = $conn->prepare("SELECT COUNT(*) FROM allocations WHERE request_id = ? AND delivery_status != 'delivered'");
            $pending_allocs->bind_param("i", $request_id);
            $pending_allocs->execute();
            $pending_allocs->bind_result($remaining);
            $pending_allocs->fetch();
            $pending_allocs->close(); // Close the statement before running the next one to fix "Commands out of sync" error
            
            if ($remaining == 0) {
                $req_upd = $conn->prepare("UPDATE requests SET status = 'delivered' WHERE id = ?");
                $req_upd->bind_param("i", $request_id);
                $req_upd->execute();
                $req_upd->close();
            }
            $check_alloc->close();
            header("Location: view_request_detail.php?id=$request_id&success=" . urlencode('Marked as received successfully.'));
            exit();
        }
    }
}

// Get allocation info for this request - Handle BOTH standard and custom resources
$allocations = $conn->query("SELECT a.id, a.quantity_allocated, a.delivery_status, a.created_at, a.date,
                            COALESCE(r.resource_name, 'Custom Resource') as resource_name, a.fulfilled_by, u.username as admin_name
                            FROM allocations a
                            LEFT JOIN resources r ON a.resource_id = r.id
                            LEFT JOIN users u ON a.fulfilled_by = u.id
                            WHERE a.request_id=$request_id
                            ORDER BY a.created_at DESC");

// Compute real-time overall status based on allocations
$allocations_data = [];
$has_in_transit = false;
$has_delivered = false;
$all_delivered = true;
$has_allocations = false;

if ($allocations && $allocations->num_rows > 0) {
    $has_allocations = true;
    while ($alloc = $allocations->fetch_assoc()) {
        $allocations_data[] = $alloc;
        if ($alloc['delivery_status'] === 'in_transit') {
            $has_in_transit = true;
            $all_delivered = false;
        } elseif ($alloc['delivery_status'] === 'delivered') {
            $has_delivered = true;
        } else {
            $all_delivered = false;
        }
    }
} else {
    $all_delivered = false;
}

$overall_status = $request['status'];
if ($has_allocations) {
    if ($all_delivered) {
        $overall_status = 'delivered';
    } elseif ($has_in_transit || $has_delivered) {
        $overall_status = 'in_transit';
    } else {
        $overall_status = 'allocated';
    }
}

$status_colors = [
    'pending' => '#ffc107',
    'approved' => '#28a745',
    'allocated' => '#007bff',
    'in_transit' => '#17a2b8',
    'delivered' => '#28a745'
];

$approval_colors = [
    'pending' => '#ffc107',
    'approved' => '#28a745',
    'rejected' => '#dc3545'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .workflow-step {
            text-align: center;
            position: relative;
            flex: 1;
            padding: 20px;
        }
        .workflow-step.active .step-circle {
            background: #007bff;
            color: white;
        }
        .workflow-step.completed .step-circle {
            background: #28a745;
            color: white;
        }
        .step-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
            font-size: 1.5rem;
        }
        .workflow-container {
            display: flex;
            margin: 30px 0;
            position: relative;
        }
        .workflow-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 40px;
            right: -25px;
            width: 50px;
            height: 2px;
            background: #dee2e6;
        }
        .detail-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .allocation-card {
            border: 2px solid #007bff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            background: #f0f7ff;
        }
        .badge-lg {
            padding: 10px 15px;
            font-size: 1rem;
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
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-file-alt"></i> Request Details</h2>
                        <a href="my_requests.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to My Requests
                        </a>
                    </div>

                    <!-- Workflow Progress -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Workflow Progress</h5>
                        </div>
                        <div class="card-body">
                            <div class="workflow-container">
                                <div class="workflow-step <?php echo ($request['approval_status'] !== 'pending') ? 'completed' : 'active'; ?>">
                                    <div class="step-circle">1</div>
                                    <p><strong>Submitted</strong></p>
                                    <small class="text-muted"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></small>
                                </div>
                                <div class="workflow-step <?php echo ($request['approval_status'] === 'approved') ? 'completed' : (($request['approval_status'] === 'pending') ? '' : ($request['approval_status'] === 'rejected' ? '' : 'completed')); ?>">
                                    <div class="step-circle">2</div>
                                    <p><strong>Approval</strong></p>
                                    <?php if ($request['approval_status'] === 'pending'): ?>
                                        <small class="badge bg-warning">Pending</small>
                                    <?php elseif ($request['approval_status'] === 'approved'): ?>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($request['approval_date'])); ?></small>
                                    <?php elseif ($request['approval_status'] === 'rejected'): ?>
                                        <small class="badge bg-danger">Rejected</small>
                                    <?php endif; ?>
                                </div>
                                <div class="workflow-step <?php echo ($overall_status !== 'pending' && $request['approval_status'] === 'approved') ? 'completed' : ''; ?>">
                                    <div class="step-circle">3</div>
                                    <p><strong>Allocated</strong></p>
                                    <?php if ($overall_status !== 'pending' && $request['approval_status'] === 'approved'): ?>
                                        <small class="text-muted">Allocated</small>
                                    <?php else: ?>
                                        <small class="text-muted">Waiting...</small>
                                    <?php endif; ?>
                                </div>
                                <div class="workflow-step <?php echo in_array($overall_status, ['in_transit', 'delivered']) ? 'completed' : ''; ?>">
                                    <div class="step-circle">4</div>
                                    <p><strong>In Transit</strong></p>
                                    <small class="text-muted">Tracking...</small>
                                </div>
                                <div class="workflow-step <?php echo ($overall_status === 'delivered') ? 'completed' : ''; ?>">
                                    <div class="step-circle">5</div>
                                    <p><strong>Delivered</strong></p>
                                    <small class="text-muted">Complete</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Request Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Request Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="detail-info">
                                        <div class="info-row">
                                            <span>Request ID:</span>
                                            <strong>#<?php echo $request['id']; ?></strong>
                                        </div>
                                        <div class="info-row">
                                            <span>Resource Type:</span>
                                            <strong><?php echo htmlspecialchars($request['resource_type']); ?></strong>
                                        </div>
                                        <div class="info-row">
                                            <span>Quantity Requested:</span>
                                            <strong><?php echo $request['quantity']; ?> units</strong>
                                        </div>
                                        <div class="info-row">
                                            <span>Location:</span>
                                            <strong><?php echo htmlspecialchars($request['location']); ?></strong>
                                        </div>
                                        <div class="info-row">
                                            <span>Incident Type:</span>
                                            <strong><?php echo $request['disaster_type'] ? htmlspecialchars($request['disaster_type']) : '<span class="badge bg-secondary">New/Unreported Incident</span>'; ?></strong>
                                        </div>
                                        <div class="info-row">
                                            <span>Location:</span>
                                            <strong><?php echo htmlspecialchars($request['location'] ?? $request['disaster_location'] ?? 'Not specified'); ?></strong>
                                        </div>
                                        <div class="info-row">
                                            <span>Priority:</span>
                                            <span class="badge" style="background: <?php 
                                                echo ($request['priority'] === 'Critical' ? '#dc3545' : 
                                                     ($request['priority'] === 'High' ? '#fd7e14' : 
                                                     ($request['priority'] === 'Medium' ? '#ffc107' : '#28a745')));
                                            ?>">
                                                <?php echo $request['priority']; ?>
                                            </span>
                                        </div>
                                        <div class="info-row">
                                            <span>Created:</span>
                                            <strong><?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-check"></i> Status Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="detail-info">
                                        <div class="info-row">
                                            <span>Current Status:</span>
                                            <span class="badge badge-lg" style="background: <?php echo $status_colors[$overall_status] ?? '#6c757d'; ?>;">
                                                <?php echo ucfirst(str_replace('_', ' ', $overall_status)); ?>
                                            </span>
                                        </div>
                                        <div class="info-row">
                                            <span>Approval Status:</span>
                                            <span class="badge badge-lg" style="background: <?php echo $approval_colors[$request['approval_status']] ?? '#6c757d'; ?>;">
                                                <?php echo ucfirst($request['approval_status']); ?>
                                            </span>
                                        </div>
                                        <?php if ($request['approval_status'] !== 'pending'): ?>
                                            <div class="info-row">
                                                <span>Approved By:</span>
                                                <strong><?php echo htmlspecialchars($request['who_approved'] ?? 'Admin'); ?></strong>
                                            </div>
                                            <div class="info-row">
                                                <span>Approval Date:</span>
                                                <strong><?php echo date('M d, Y h:i A', strtotime($request['approval_date'])); ?></strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Allocations -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-cube"></i> Allocation & Delivery Tracking</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($has_allocations): ?>
                                <?php foreach ($allocations_data as $alloc): ?>
                                    <div class="allocation-card">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p><strong>Resource Allocated:</strong></p>
                                                <h5><?php echo htmlspecialchars($alloc['resource_name']); ?></h5>
                                                <p class="text-muted mb-0">Qty: <strong><?php echo $alloc['quantity_allocated']; ?> units</strong></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p><strong>Delivery Status:</strong></p>
                                                <span class="badge badge-lg" style="background: <?php 
                                                    echo ($alloc['delivery_status'] === 'delivered' ? '#28a745' : 
                                                         ($alloc['delivery_status'] === 'in_transit' ? '#17a2b8' : '#ffc107'));
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $alloc['delivery_status'])); ?>
                                                </span>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <p><strong>Allocated By:</strong></p>
                                                <p class="mb-0"><?php echo htmlspecialchars($alloc['admin_name'] ?? 'Admin'); ?></p>
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($alloc['created_at'])); ?></small>
                                                <?php if ($alloc['date']): ?>
                                                    <small class="d-block text-success">Date: <?php echo date('M d, Y', strtotime($alloc['date'])); ?></small>
                                                <?php endif; ?>
                                                <?php if ($alloc['delivery_status'] === 'in_transit'): ?>
                                                    <a class="btn btn-sm btn-success mt-2" href="view_request_detail.php?id=<?php echo $request_id; ?>&alloc_id=<?php echo $alloc['id']; ?>&confirm_receive=1" onclick="return confirm('Confirm you have received this allocation?');">
                                                        <i class="fas fa-check-circle"></i> Confirm Received
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i> 
                                    No allocations yet. 
                                    <?php if ($request['approval_status'] === 'approved'): ?>
                                        Admin is reviewing your request for allocation.
                                    <?php elseif ($request['approval_status'] === 'pending'): ?>
                                        Your request is pending admin approval.
                                    <?php else: ?>
                                        Your request was rejected.
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 d-flex gap-2">
                        <?php if ($request['approval_status'] === 'pending'): ?>
                            <a href="../requests/edit_request.php?id=<?php echo $request['id']; ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Edit Request
                            </a>
                        <?php endif; ?>
                        <a href="my_requests.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
