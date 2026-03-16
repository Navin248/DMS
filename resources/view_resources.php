<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$error = '';
$success = '';
$low_stock_threshold = 100; // Alert if less than 100

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $query = "DELETE FROM resources WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success = 'Resource deleted successfully!';
    } else {
        $error = 'Error deleting resource: ' . $conn->error;
    }
}

// Check for success message from URL
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}

// Get all resources sorted by ID
$query = "SELECT * FROM resources ORDER BY id DESC";
$result = $conn->query($query);

// Get low stock resources
$low_stock_query = "SELECT * FROM resources WHERE quantity < $low_stock_threshold ORDER BY quantity ASC";
$low_stock_result = $conn->query($low_stock_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resources - Disaster Relief System</title>
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
        .action-buttons {
            white-space: nowrap;
        }
        .low-stock-alert {
            background-color: #fff3cd;
            border-left: 4px solid #ff6b6b;
            border-radius: 5px;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .quantity-badge {
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }
        .quantity-low {
            background-color: #ffe0e0;
            color: #c92a2a;
        }
        .quantity-medium {
            background-color: #fff3cd;
            color: #997404;
        }
        .quantity-good {
            background-color: #d3f9d8;
            color: #2b8a3e;
        }
        .summary-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .summary-card:hover {
            border-color: #1E3A8A;
            box-shadow: 0 4px 20px rgba(30, 58, 138, 0.15);
        }
        .alert {
            animation: slideInAlert 0.4s ease;
        }
        @keyframes slideInAlert {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row" style="min-height: 100vh;">
            <!-- Sidebar -->
            <?php include '../includes/sidebar.php'; ?>
            
            <!-- Main Content -->
            <div class="col-md-9 p-4" style="background-color: #F3F4F6;">
                <!-- Header -->
                <?php include '../includes/header.php'; ?>
                
                <div class="container mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-box"></i> Resources Inventory</h2>
                        <a href="add_resource.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Resource
                        </a>
                    </div>
                    <hr>

                    <!-- Alert Messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show auto-dismiss" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show auto-dismiss" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Low Stock Alerts -->
                    <?php if ($low_stock_result->num_rows > 0): ?>
                        <div class="alert alert-warning low-stock-alert" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Low Stock Alert!</strong> The following resources are running low:
                            <ul class="mb-0 mt-2">
                                <?php 
                                $low_stock_result->data_seek(0);
                                while ($low = $low_stock_result->fetch_assoc()): 
                                ?>
                                    <li><?php echo htmlspecialchars($low['resource_name']); ?> - <strong><?php echo $low['quantity']; ?> units</strong></li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Resources Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> Available Resources 
                                <span class="badge bg-primary float-end"><?php echo $result->num_rows; ?> items</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Resource Name</th>
                                                <th>Quantity</th>
                                                <th>Stock Status</th>
                                                <th>Warehouse Location</th>
                                                <th>Created Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $result->data_seek(0);
                                            while ($row = $result->fetch_assoc()): 
                                                $qty = $row['quantity'];
                                                if ($qty < 50) {
                                                    $status_class = 'quantity-low';
                                                    $status_text = 'Critical';
                                                } elseif ($qty < $low_stock_threshold) {
                                                    $status_class = 'quantity-medium';
                                                    $status_text = 'Low';
                                                } else {
                                                    $status_class = 'quantity-good';
                                                    $status_text = 'Good';
                                                }
                                            ?>
                                                <tr class="table-row-clickable" onclick="window.location.href='update_resource.php?id=<?php echo $row['id']; ?>'">
                                                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                                                    <td>
                                                        <i class="fas fa-boxes"></i>
                                                        <?php echo htmlspecialchars($row['resource_name']); ?>
                                                    </td>
                                                    <td>
                                                        <span class="quantity-badge <?php echo $status_class; ?>">
                                                            <?php echo number_format($row['quantity']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ($status_text == 'Critical') {
                                                            echo '<span class="badge bg-danger">Critical</span>';
                                                        } elseif ($status_text == 'Low') {
                                                            echo '<span class="badge bg-warning">Low</span>';
                                                        } else {
                                                            echo '<span class="badge bg-success">Good</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['warehouse_location'] ?? 'Not specified'); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                                    <td class="action-buttons" onclick="event.stopPropagation();">
                                                        <a href="update_resource.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this resource?');">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No resources found. <a href="add_resource.php">Add one →</a></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Summary Cards (Clickable) -->
                    <div class="row mt-4">
                        <div class="col-md-4 mb-3">
                            <div class="card summary-card" data-bs-toggle="modal" data-bs-target="#totalResourcesModal" style="cursor: pointer;">
                                <div class="card-body text-center p-4">
                                    <i class="fas fa-cube text-primary" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                    <h4 class="text-primary"><?php echo $result->num_rows; ?></h4>
                                    <p class="text-muted mb-2">Total Resources</p>
                                    <small class="text-secondary">Click to view details</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card summary-card" data-bs-toggle="modal" data-bs-target="#totalUnitsModal" style="cursor: pointer;">
                                <div class="card-body text-center p-4">
                                    <i class="fas fa-boxes text-success" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                    <h4 class="text-success">
                                        <?php 
                                        $total_qty = $conn->query("SELECT SUM(quantity) as total FROM resources")->fetch_assoc()['total'];
                                        echo number_format($total_qty ?? 0);
                                        ?>
                                    </h4>
                                    <p class="text-muted mb-2">Total Units</p>
                                    <small class="text-secondary">Click to view details</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card summary-card" data-bs-toggle="modal" data-bs-target="#lowStockModal" style="cursor: pointer;">
                                <div class="card-body text-center p-4">
                                    <i class="fas fa-exclamation-triangle text-danger" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                    <h4 class="text-danger"><?php echo $low_stock_result->num_rows; ?></h4>
                                    <p class="text-muted mb-2">Low Stock Items</p>
                                    <small class="text-secondary">Click to view details</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: Total Resources -->
                    <div class="modal fade" id="totalResourcesModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title"><i class="fas fa-cube"></i> Total Resources</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Total Number of Resource Types:</strong></p>
                                    <h3 class="text-primary"><?php echo $result->num_rows; ?></h3>
                                    <hr>
                                    <p><i class="fas fa-info-circle text-info"></i> <strong>Information:</strong></p>
                                    <ul class="mb-3">
                                        <li>This is the total count of different resource items in your inventory</li>
                                        <li>Each resource type is tracked separately</li>
                                        <li>Use the table above to manage individual resources</li>
                                    </ul>
                                    <p class="text-muted"><strong>Resources Currently in Inventory:</strong></p>
                                    <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                                        <?php 
                                        $result->data_seek(0);
                                        while ($row = $result->fetch_assoc()): 
                                        ?>
                                            <a href="#" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($row['resource_name']); ?></h6>
                                                    <span class="badge bg-primary">ID: <?php echo $row['id']; ?></span>
                                                </div>
                                                <p class="mb-1"><small>📍 <?php echo htmlspecialchars($row['warehouse_location'] ?? 'Not specified'); ?></small></p>
                                            </a>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: Total Units -->
                    <div class="modal fade" id="totalUnitsModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title"><i class="fas fa-boxes"></i> Total Units in Stock</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Total Units Across All Resources:</strong></p>
                                    <h3 class="text-success">
                                        <?php echo number_format($total_qty ?? 0); ?> units
                                    </h3>
                                    <hr>
                                    <p><i class="fas fa-info-circle text-info"></i> <strong>Information:</strong></p>
                                    <ul class="mb-3">
                                        <li>Sum of all quantities across all resources</li>
                                        <li>This represents the total inventory volume</li>
                                        <li>Monitor this metric to track overall stock levels</li>
                                    </ul>
                                    <p class="text-muted"><strong>Resource Breakdown by Quantity:</strong></p>
                                    <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                                        <?php 
                                        $result->data_seek(0);
                                        while ($row = $result->fetch_assoc()): 
                                            $qty = $row['quantity'];
                                            if ($qty < 50) {
                                                $badge_class = 'bg-danger';
                                            } elseif ($qty < 100) {
                                                $badge_class = 'bg-warning';
                                            } else {
                                                $badge_class = 'bg-success';
                                            }
                                        ?>
                                            <a href="#" class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($row['resource_name']); ?></h6>
                                                    <span class="badge <?php echo $badge_class; ?>"><?php echo number_format($qty); ?> units</span>
                                                </div>
                                            </a>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal: Low Stock Items -->
                    <div class="modal fade" id="lowStockModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Low Stock Items</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Number of Resources Below Threshold:</strong></p>
                                    <h3 class="text-danger"><?php echo $low_stock_result->num_rows; ?> items</h3>
                                    <hr>
                                    <p><i class="fas fa-info-circle text-info"></i> <strong>Information:</strong></p>
                                    <ul class="mb-3">
                                        <li>Low stock threshold: <strong><?php echo $low_stock_threshold; ?> units</strong></li>
                                        <li>Critical level: <strong>Below 50 units</strong></li>
                                        <li>Action required for items below threshold</li>
                                    </ul>
                                    <?php if ($low_stock_result->num_rows > 0): ?>
                                        <p class="text-muted"><strong>Low Stock Resources (Sorted by Quantity):</strong></p>
                                        <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                                            <?php 
                                            $low_stock_result->data_seek(0);
                                            while ($low = $low_stock_result->fetch_assoc()): 
                                                $qty = $low['quantity'];
                                                $status = ($qty < 50) ? '🔴 CRITICAL' : '🟡 LOW';
                                            ?>
                                                <a href="#" class="list-group-item list-group-item-action">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($low['resource_name']); ?></h6>
                                                        <span class="badge <?php echo ($qty < 50) ? 'bg-danger' : 'bg-warning'; ?>"><?php echo $status; ?></span>
                                                    </div>
                                                    <p class="mb-1"><small><?php echo number_format($qty); ?> units remaining</small></p>
                                                </a>
                                            <?php endwhile; ?>
                                        </div>
                                        <div class="alert alert-warning mt-3 mb-0">
                                            <i class="fas fa-lightbulb"></i> <strong>Tip:</strong> Consider restocking these items to maintain adequate supply levels.
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i> All resources are well stocked!
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-dismiss alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.auto-dismiss');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000); // 5 seconds
            });

            // Add hover effect to summary cards
            const summaryCards = document.querySelectorAll('.summary-card');
            summaryCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
                    this.style.transition = 'all 0.3s ease';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });
        });
    </script>
</body>
</html>

