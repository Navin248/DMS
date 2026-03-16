<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$error = '';
$success = '';
$resource = null;

// Get resource ID
$id = (int)($_GET['id'] ?? 0);
if ($id == 0) {
    header("Location: view_resources.php");
    exit();
}

// Fetch resource
$query = "SELECT * FROM resources WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: view_resources.php?error=Resource%20not%20found");
    exit();
}

$resource = $result->fetch_assoc();

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resource_name = trim($_POST['resource_name'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $warehouse_location = trim($_POST['warehouse_location'] ?? '');

    // Validation
    if (empty($resource_name)) {
        $error = 'Resource name is required!';
    } elseif ($quantity < 0) {
        $error = 'Quantity cannot be negative!';
    } else {
        $query = "UPDATE resources SET resource_name=?, quantity=?, warehouse_location=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sisi", $resource_name, $quantity, $warehouse_location, $id);
        
        if ($stmt->execute()) {
            $success = 'Resource updated successfully! Redirecting...';
            header("Refresh: 2; url=view_resources.php?success=Resource%20updated%20(ID:%20" . $id . ")");
            // Refresh the data to show on current page
            $query = "SELECT * FROM resources WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $resource = $result->fetch_assoc();
        } else {
            $error = 'Error updating resource: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Resource - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                    <h2><i class="fas fa-edit"></i> Edit Resource #<?php echo $resource['id']; ?></h2>
                    <hr>

                    <!-- Alert Messages -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Edit Resource Form -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-box"></i> Update Resource Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="resource_name" class="form-label">Resource Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="resource_name" name="resource_name" 
                                           placeholder="e.g., Food Packages, Water Bottles, Medical Kits" required
                                           value="<?php echo htmlspecialchars($resource['resource_name']); ?>">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                                   placeholder="e.g., 500" min="0" required
                                                   value="<?php echo htmlspecialchars($resource['quantity']); ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="warehouse_location" class="form-label">Warehouse Location</label>
                                            <input type="text" class="form-control" id="warehouse_location" name="warehouse_location" 
                                                   placeholder="e.g., Central Warehouse, Storage A"
                                                   value="<?php echo htmlspecialchars($resource['warehouse_location'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Update Resource
                                    </button>
                                    <a href="view_resources.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Resource Info Card -->
                    <div class="card mt-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Current Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($resource['created_at'])); ?></p>
                                    <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($resource['updated_at'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <strong>Stock Status:</strong> 
                                        <?php 
                                        $qty = $resource['quantity'];
                                        if ($qty < 50) {
                                            echo '<span class="badge bg-danger">Critical</span>';
                                        } elseif ($qty < 100) {
                                            echo '<span class="badge bg-warning">Low</span>';
                                        } else {
                                            echo '<span class="badge bg-success">Good</span>';
                                        }
                                        ?>
                                    </p>
                                    <p>
                                        <strong>Total Units:</strong> 
                                        <span class="badge bg-primary"><?php echo number_format($qty); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Update Card -->
                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Stock Adjustment Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li><strong>Adding Stock:</strong> If you receive new supplies, increase the quantity</li>
                                <li><strong>Using Stock:</strong> When resources are distributed, decrease the quantity</li>
                                <li><strong>Critical Alert:</strong> Less than 50 units triggers critical alert</li>
                                <li><strong>Low Alert:</strong> Less than 100 units triggers low stock warning</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
