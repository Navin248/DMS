<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('admin');  // Only admin can add resources

$error = '';
$success = '';

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
        $query = "INSERT INTO resources (resource_name, quantity, warehouse_location) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sis", $resource_name, $quantity, $warehouse_location);
        
        if ($stmt->execute()) {
            $resource_id = $conn->insert_id;
            header("Location: view_resources.php?success=Resource%20added%20successfully%20(ID:%20" . $resource_id . ")");
            exit();
        } else {
            $error = 'Error adding resource: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Resource - Disaster Relief System</title>
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
                    <h2><i class="fas fa-plus-circle"></i> Add New Resource</h2>
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
                            <a href="view_resources.php" class="alert-link">View all resources →</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Add Resource Form -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-box"></i> Resource Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="resource_name" class="form-label">Resource Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="resource_name" name="resource_name" 
                                           placeholder="e.g., Food Packages, Water Bottles, Medical Kits" required
                                           value="<?php echo htmlspecialchars($_POST['resource_name'] ?? ''); ?>">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="quantity" class="form-label">Initial Quantity <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                                   placeholder="e.g., 500" min="0" required
                                                   value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="warehouse_location" class="form-label">Warehouse Location</label>
                                            <input type="text" class="form-control" id="warehouse_location" name="warehouse_location" 
                                                   placeholder="e.g., Central Warehouse, Storage A"
                                                   value="<?php echo htmlspecialchars($_POST['warehouse_location'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Add Resource
                                    </button>
                                    <a href="view_resources.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Quick Add Examples -->
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-lightbulb"></i> <strong>Common Resources:</strong><br>
                        • Food Packages • Water Bottles • Medical Kits • Blankets • Tents • First Aid Supplies • Generators • Fuel Containers
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

