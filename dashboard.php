<?php
session_start();
require_once 'config/database.php';
require_once 'config/auth.php';

check_login();
check_role('admin');

$user = get_user_info();
$error = '';
$success = '';

// Get dashboard statistics
$active_disasters_count = $conn->query("SELECT COUNT(*) as count FROM disasters WHERE status = 'active'")->fetch_assoc()['count'];
$pending_requests_count = $conn->query("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'")->fetch_assoc()['count'];
$total_resources = $conn->query("SELECT SUM(quantity) as count FROM resources")->fetch_assoc()['count'] ?? 0;
$delivered_allocations_count = $conn->query("SELECT COUNT(*) as count FROM allocations WHERE delivery_status = 'delivered'")->fetch_assoc()['count'];

// Get detailed data for modals
$active_disasters = $conn->query("SELECT * FROM disasters WHERE status = 'active' ORDER BY date DESC");
$pending_requests = $conn->query("SELECT r.*, d.type as disaster_type FROM requests r JOIN disasters d ON r.disaster_id = d.id WHERE r.status = 'pending' ORDER BY r.created_at DESC");
$resource_breakdown = $conn->query("SELECT COUNT(*) as types, SUM(quantity) as total FROM resources");
$delivered_allocations = $conn->query("SELECT a.*, r.resource_name, d.type as disaster_type FROM allocations a JOIN resources r ON a.resource_id = r.id JOIN requests req ON a.request_id = req.id JOIN disasters d ON req.disaster_id = d.id WHERE a.delivery_status = 'delivered' ORDER BY a.date DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
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
            font-size: 0.95rem;
            margin: 10px 0 0 0;
        }
        .timeline {
            position: relative;
            padding: 10px 0;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 10px;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        .timeline .mb-3 {
            position: relative;
            margin-left: 50px;
        }
        .timeline .badge {
            position: absolute;
            left: -33px;
            top: 0;
        }
    </style></head>
<body>
    <div class="container-fluid">
        <div class="row" style="min-height: 100vh;">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 p-4" style="background-color: #F3F4F6;">
                <!-- Header -->
                <?php include 'includes/header.php'; ?>
                
                <!-- Alert Messages -->
                <?php if ($_GET['timeout'] ?? false): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-clock"></i> <strong>Session Expired!</strong> Your session timed out after 30 minutes of inactivity.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Stats -->
                <div class="mt-4">
                    <h2 class="mb-4"><i class="fas fa-chart-line"></i> Dashboard Overview</h2>
                    
                    <div class="row">
                        <!-- Active Disasters -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card" data-bs-toggle="modal" data-bs-target="#activedisastersModal">
                                <i class="fas fa-map-marker-alt text-warning"></i>
                                <h3><?php echo $active_disasters_count; ?></h3>
                                <p>Active Disasters</p>
                            </div>
                        </div>

                        <!-- Pending Requests -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card" data-bs-toggle="modal" data-bs-target="#pendingrequestsModal">
                                <i class="fas fa-list text-info"></i>
                                <h3><?php echo $pending_requests_count; ?></h3>
                                <p>Pending Requests</p>
                            </div>
                        </div>

                        <!-- Available Resources -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card" data-bs-toggle="modal" data-bs-target="#totalresourcesModal">
                                <i class="fas fa-box text-success"></i>
                                <h3><?php echo number_format($total_resources); ?></h3>
                                <p>Total Resources</p>
                            </div>
                        </div>

                        <!-- Delivered Allocations -->
                        <div class="col-md-3 mb-4">
                            <div class="dashboard-card" data-bs-toggle="modal" data-bs-target="#deliveredallocationsModal">
                                <i class="fas fa-check-circle text-success"></i>
                                <h3><?php echo $delivered_allocations_count; ?></h3>
                                <p>Delivered</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-5">
                        <h4>Quick Actions</h4>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="disasters/add_disaster.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i> Add Disaster
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="resources/add_resource.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i> Add Resource
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="requests/create_request.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i> Create Request
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="allocations/allocate_resource.php" class="btn btn-primary w-100">
                                    <i class="fas fa-plus"></i> Allocate Resource
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- System Status Section (Phase 7) -->
                    <div class="mt-5">
                        <h4><i class="fas fa-heartbeat"></i> System Status</h4>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card border-success">
                                    <div class="card-body">
                                        <h5 class="text-success"><i class="fas fa-check-circle"></i> System Health</h5>
                                        <p class="mb-1">Database: <span class="badge bg-success">Connected</span></p>
                                        <p class="mb-1">Server: <span class="badge bg-success">Running</span></p>
                                        <p class="mb-0">API: <span class="badge bg-success">Active</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card border-warning">
                                    <div class="card-body">
                                        <h5 class="text-warning"><i class="fas fa-exclamation-triangle"></i> Alerts</h5>
                                        <p class="mb-1">Critical: <span class="badge bg-danger">1</span></p>
                                        <p class="mb-1">Warnings: <span class="badge bg-warning">3</span></p>
                                        <p class="mb-0">Info: <span class="badge bg-info">5</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card border-info">
                                    <div class="card-body">
                                        <h5 class="text-info"><i class="fas fa-chart-line"></i> Performance</h5>
                                        <p class="mb-1">Load: <span class="badge bg-info">35%</span></p>
                                        <p class="mb-1">Memory: <span class="badge bg-info">42%</span></p>
                                        <p class="mb-0">Response: <span class="badge bg-info">125ms</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity Timeline (Phase 7) -->
                    <div class="mt-5">
                        <h4><i class="fas fa-history"></i> Recent Activity Timeline</h4>
                        <div class="card">
                            <div class="card-body">
                                <div class="timeline">
                                    <?php
                                    $activity_query = "SELECT 'disaster' as type, d.type as title, d.date as activity_time, d.location as detail
                                                       FROM disasters d ORDER BY d.date DESC LIMIT 5
                                                       UNION ALL
                                                       SELECT 'request', r.resource_type, r.created_at, r.location
                                                       FROM requests r ORDER BY r.created_at DESC LIMIT 5
                                                       UNION ALL
                                                       SELECT 'allocation', CONCAT(res.resource_name, ' allocated'), a.date, req.location
                                                       FROM allocations a
                                                       JOIN resources res ON a.resource_id = res.id
                                                       JOIN requests req ON a.request_id = req.id
                                                       ORDER BY a.date DESC LIMIT 5
                                                       ORDER BY activity_time DESC LIMIT 10";
                                    $activity_result = $conn->query($activity_query);
                                    if ($activity_result && $activity_result->num_rows > 0) {
                                        while ($activity = $activity_result->fetch_assoc()) {
                                            $icon = $activity['type'] == 'disaster' ? 'fa-exclamation-triangle' : ($activity['type'] == 'request' ? 'fa-list-check' : 'fa-dolly');
                                            $color = $activity['type'] == 'disaster' ? 'danger' : ($activity['type'] == 'request' ? 'warning' : 'success');
                                    ?>
                                        <div class="mb-3 pb-3 border-bottom">
                                            <div class="d-flex">
                                                <div class="me-3">
                                                    <span class="badge bg-<?php echo $color; ?> rounded-circle p-2">
                                                        <i class="fas <?php echo $icon; ?>"></i>
                                                    </span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['title']); ?></h6>
                                                    <p class="mb-1 text-muted small"><?php echo htmlspecialchars($activity['detail']); ?></p>
                                                    <small class="text-secondary"><?php echo date('M d, Y H:i', strtotime($activity['activity_time'])); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php
                                        }
                                    } else {
                                        echo '<p class="text-muted text-center">No activities yet</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export & Reports (Phase 7) -->
                    <div class="mt-5 mb-4">
                        <h4><i class="fas fa-download"></i> Reports & Export</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <button onclick="exportToCSV('disasters')" class="btn btn-info w-100">
                                    <i class="fas fa-file-csv"></i> Export Disasters
                                </button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button onclick="exportToCSV('resources')" class="btn btn-info w-100">
                                    <i class="fas fa-file-csv"></i> Export Resources
                                </button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button onclick="exportToCSV('requests')" class="btn btn-info w-100">
                                    <i class="fas fa-file-csv"></i> Export Requests
                                </button>
                            </div>
                            <div class="col-md-6 mb-3">
                                <button onclick="exportToCSV('allocations')" class="btn btn-info w-100">
                                    <i class="fas fa-file-csv"></i> Export Allocations
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Active Disasters -->
    <div class="modal fade" id="activedisastersModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-map-marker-alt"></i> Active Disasters</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($active_disasters && $active_disasters->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Disaster Type</th>
                                        <th>Location</th>
                                        <th>Severity</th>
                                        <th>Date Reported</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($disaster = $active_disasters->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($disaster['type']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($disaster['location']); ?></td>
                                            <td><span class="badge bg-danger"><?php echo $disaster['severity']; ?></span></td>
                                            <td><?php echo date('M d, Y', strtotime($disaster['date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center"><i class="fas fa-smile-wink"></i> No active disasters at the moment!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Pending Requests -->
    <div class="modal fade" id="pendingrequestsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-list"></i> Pending Requests</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if ($pending_requests && $pending_requests->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Disaster Type</th>
                                        <th>Location</th>
                                        <th>Resource Type</th>
                                        <th>Quantity</th>
                                        <th>Priority</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($request = $pending_requests->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($request['disaster_type']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($request['location']); ?></td>
                                            <td><?php echo htmlspecialchars($request['resource_type']); ?></td>
                                            <td><span class="badge bg-info"><?php echo $request['quantity']; ?></span></td>
                                            <td><span class="badge bg-warning"><?php echo $request['priority']; ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center"><i class="fas fa-check"></i> No pending requests!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Total Resources -->
    <div class="modal fade" id="totalresourcesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-box"></i> Total Resources Overview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php 
                    $breakdown = $resource_breakdown->fetch_assoc();
                    $resources = $conn->query("SELECT * FROM resources ORDER BY quantity DESC");
                    ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo $breakdown['types'] ?? 0; ?></h3>
                                    <p class="text-muted">Resource Types</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <h3 class="text-success"><?php echo number_format($breakdown['total'] ?? 0); ?></h3>
                                    <p class="text-muted">Total Units</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6 class="mb-3">Resource Breakdown:</h6>
                    <?php if ($resources && $resources->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Resource Name</th>
                                        <th>Quantity</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($res = $resources->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($res['resource_name']); ?></td>
                                            <td>
                                                <span class="badge bg-success"><?php echo number_format($res['quantity']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($res['warehouse_location'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Delivered Allocations -->
    <div class="modal fade" id="deliveredallocationsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle"></i> Delivered Allocations</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php 
                    $delivered = $conn->query("SELECT a.*, r.resource_name, d.type as disaster_type FROM allocations a JOIN resources r ON a.resource_id = r.id JOIN disasters d ON a.disaster_id = d.id WHERE a.delivery_status = 'delivered' ORDER BY a.date DESC LIMIT 20");
                    if ($delivered && $delivered->num_rows > 0): 
                    ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Disaster Type</th>
                                        <th>Resource</th>
                                        <th>Quantity</th>
                                        <th>Delivery Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($alloc = $delivered->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($alloc['disaster_type']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($alloc['resource_name']); ?></td>
                                            <td><span class="badge bg-success"><?php echo $alloc['quantity_allocated']; ?></span></td>
                                            <td><?php echo date('M d, Y', strtotime($alloc['date'])); ?></td>
                                            <td><span class="badge bg-success">Delivered</span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center"><i class="fas fa-box-open"></i> No delivered allocations yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>    <script>
        // Export to CSV functionality (Phase 7)
        function exportToCSV(type) {
            let url = '';
            
            switch(type) {
                case 'disasters':
                    url = 'disasters/view_disasters.php?export=1';
                    break;
                case 'resources':
                    url = 'resources/view_resources.php?export=1';
                    break;
                case 'requests':
                    url = 'requests/view_requests.php?export=1';
                    break;
                case 'allocations':
                    url = 'allocations/view_allocations.php?export=1';
                    break;
            }
            
            if (url) {
                window.open(url, '_blank');
                showNotification('Exporting ' + type + '...', 'info');
            }
        }

        function showNotification(message, type) {
            const alertDivs = document.querySelectorAll('.alert');
            const alertContainer = document.body;
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.top = '20px';
            alertDiv.style.right = '20px';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 3000);
        }
    </script></body>
</html>
