<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$error = '';
$success = '';
$allocation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$allocation_id) {
    header("Location: view_allocations.php");
    exit();
}

// Get allocation details
$query = "SELECT a.*, r.resource_name, req.resource_type, d.type as disaster_type
          FROM allocations a 
          JOIN resources r ON a.resource_id = r.id
          JOIN requests req ON a.request_id = req.id
          LEFT JOIN disasters d ON req.disaster_id = d.id
          WHERE a.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $allocation_id);
$stmt->execute();
$result = $stmt->get_result();
$allocation = $result->fetch_assoc();

if (!$allocation) {
    header("Location: view_allocations.php");
    exit();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantity_allocated = isset($_POST['quantity_allocated']) ? (int)$_POST['quantity_allocated'] : 0;
    $delivery_status = isset($_POST['delivery_status']) ? trim($_POST['delivery_status']) : 'pending';
    $delivery_date = isset($_POST['delivery_date']) ? trim($_POST['delivery_date']) : date('Y-m-d');

    // Validation
    if ($quantity_allocated <= 0 || !$delivery_status || !$delivery_date) {
        $error = 'All fields are required and quantity must be positive!';
    } else {
        // Update allocation
        $fulfilled_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        $update_query = "UPDATE allocations SET quantity_allocated = ?, delivery_status = ?, fulfilled_by = ?, date = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("isisi", $quantity_allocated, $delivery_status, $fulfilled_by, $delivery_date, $allocation_id);
        
        if ($update_stmt->execute()) {
            // If delivery_status = 'delivered', update request status to 'delivered'
            if ($delivery_status == 'delivered') {
                $request_update = "UPDATE requests SET status = 'delivered' WHERE id = ?";
                $request_stmt = $conn->prepare($request_update);
                $request_stmt->bind_param("i", $allocation['request_id']);
                $request_stmt->execute();
            }
            
            $success = 'Allocation updated successfully!';
            header("Location: view_allocations.php?success=" . urlencode($success));
            exit();
        } else {
            $error = 'Error updating allocation: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Allocation - Disaster Relief System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .form-section {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 600;
            color: #1E3A8A;
            margin-bottom: 8px;
        }
        .form-control:focus {
            border-color: #1E3A8A;
            box-shadow: 0 0 0 0.2rem rgba(30, 58, 138, 0.25);
        }
        .btn-submit {
            background-color: #1E3A8A;
            color: white;
            font-weight: 600;
            padding: 10px 30px;
        }
        .btn-submit:hover {
            background-color: #0f2847;
            color: white;
        }
        .info-badge {
            display: inline-block;
            background: #f0f0f0;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.9rem;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .read-only-field {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px 12px;
            border-radius: 4px;
            font-size: 0.95rem;
            min-height: 38px;
            display: flex;
            align-items: center;
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
                    <h2><i class="fas fa-edit"></i> Edit Allocation</h2>
                    <div class="mb-3">
                        <span class="info-badge">Allocation ID: #<?php echo $allocation['id']; ?></span>
                        <span class="info-badge">Disaster: <?php echo htmlspecialchars($allocation['disaster_type']); ?></span>
                        <span class="info-badge">Resource: <?php echo htmlspecialchars($allocation['resource_name']); ?></span>
                    </div>
                    <hr>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="form-section">
                        <form method="POST">
                            <div class="row">
                                <!-- Resource Type (Read-only) -->
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-box"></i> Resource Type
                                    </label>
                                    <div class="read-only-field">
                                        <?php echo htmlspecialchars($allocation['resource_type']); ?>
                                    </div>
                                </div>

                                <!-- Delivery Status -->
                                <div class="col-md-6 mb-3">
                                    <label for="delivery_status" class="form-label">
                                        <i class="fas fa-truck"></i> Delivery Status *
                                    </label>
                                    <select class="form-control" id="delivery_status" name="delivery_status" required>
                                        <option value="pending" <?php echo $allocation['delivery_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="in_transit" <?php echo $allocation['delivery_status'] == 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                        <option value="delivered" <?php echo $allocation['delivery_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Quantity -->
                                <div class="col-md-6 mb-3">
                                    <label for="quantity_allocated" class="form-label">
                                        <i class="fas fa-cubes"></i> Quantity Allocated *
                                    </label>
                                    <input type="number" class="form-control" id="quantity_allocated" name="quantity_allocated" 
                                           value="<?php echo $allocation['quantity_allocated']; ?>" min="1" required>
                                </div>

                                <!-- Delivery Date -->
                                <div class="col-md-6 mb-3">
                                    <label for="delivery_date" class="form-label">
                                        <i class="fas fa-calendar"></i> Delivery Date *
                                    </label>
                                    <input type="date" class="form-control" id="delivery_date" name="delivery_date" 
                                           value="<?php echo date('Y-m-d', strtotime($allocation['date'])); ?>" required>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="mt-4">
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-save"></i> Update Allocation
                                </button>
                                <a href="view_allocations.php" class="btn btn-secondary ms-2">
                                    <i class="fas fa-arrow-left"></i> Cancel
                                </a>
                            </div>
                        </form>
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
