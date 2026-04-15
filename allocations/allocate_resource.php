<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$error = '';
$success = '';

// Get pending requests for dropdown (only show approved requests that haven't been fully allocated)
// This now includes BOTH standard resources (from resources table) AND custom resources (user-specified)
$requests = $conn->query("SELECT DISTINCT r.id, r.resource_type, r.quantity, r.priority, r.location, d.type as disaster_type, r.user_id, u.username
                          FROM requests r 
                          LEFT JOIN disasters d ON r.disaster_id = d.id
                          LEFT JOIN users u ON r.user_id = u.id
                          WHERE r.approval_status = 'approved' AND r.status IN ('pending', 'allocated')
                          ORDER BY 
                            CASE WHEN r.priority = 'Critical' THEN 1 
                                 WHEN r.priority = 'High' THEN 2 
                                 WHEN r.priority = 'Medium' THEN 3 
                                 ELSE 4 END,
                            r.created_at ASC");

// Get available resources (inventory)
$resources = $conn->query("SELECT id, resource_name, quantity FROM resources WHERE quantity > 0 ORDER BY resource_name");

// Get list of custom resource types already requested (so admin can see patterns)
$custom_resources = $conn->query("SELECT DISTINCT r.resource_type FROM requests r WHERE r.resource_type NOT IN (SELECT resource_name FROM resources) AND r.approval_status = 'approved' AND r.status IN ('pending', 'allocated') ORDER BY r.resource_type");

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $resource_id = isset($_POST['resource_id']) ? (int)$_POST['resource_id'] : 0;
    $quantity_allocated = isset($_POST['quantity_allocated']) ? (int)$_POST['quantity_allocated'] : 0;
    $delivery_status = isset($_POST['delivery_status']) ? trim($_POST['delivery_status']) : 'pending';
    $delivery_date = isset($_POST['delivery_date']) ? trim($_POST['delivery_date']) : date('Y-m-d');

    // Validation
    if (!$request_id || !$resource_id || $quantity_allocated <= 0) {
        $error = 'All fields are required and quantity must be positive!';
    } else {
        // Check if resource has enough quantity
        $resource_check = $conn->query("SELECT quantity FROM resources WHERE id = $resource_id");
        $res_data = $resource_check->fetch_assoc();
        
        if (!$res_data || $res_data['quantity'] < $quantity_allocated) {
            $error = 'Not enough resources available! Available: ' . ($res_data['quantity'] ?? 0);
        } else {
            // Insert allocation
            $fulfilled_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
            $query = "INSERT INTO allocations (request_id, resource_id, quantity_allocated, delivery_status, fulfilled_by, date) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iiisis", $request_id, $resource_id, $quantity_allocated, $delivery_status, $fulfilled_by, $delivery_date);
            
            if ($stmt->execute()) {
                // Update resource quantity
                $update_query = "UPDATE resources SET quantity = quantity - ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ii", $quantity_allocated, $resource_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Update request status to 'allocated'
                $request_update = "UPDATE requests SET status = 'allocated' WHERE id = ?";
                $request_stmt = $conn->prepare($request_update);
                $request_stmt->bind_param("i", $request_id);
                $request_stmt->execute();
                $request_stmt->close();
                
                $stmt->close();
                $success = 'Allocation created successfully!';
                header("Location: view_allocations.php?success=" . urlencode($success));
                exit();
            } else {
                $error = 'Error creating allocation: ' . $conn->error;
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Allocate Resource - Disaster Relief System</title>
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
        .info-card {
            background: #e7f3ff;
            border-left: 4px solid #1E3A8A;
            padding: 15px;
            border-radius: 5px;
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
                    <h2><i class="fas fa-dolly"></i> Allocate Resource</h2>
                    <hr>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="info-card">
                        <i class="fas fa-info-circle"></i> <strong>Resource Allocation Form</strong>
                        <p class="mt-2 mb-0">Allocate resources from inventory to pending relief requests.</p>
                    </div>

                    <div class="form-section">
                        <form method="POST">
                            <div class="row">
                                <!-- Request Selection -->
                                <div class="col-md-6 mb-3">
                                    <label for="request_id" class="form-label">
                                        <i class="fas fa-list-check"></i> Pending Request *
                                    </label>
                                    <select class="form-control" id="request_id" name="request_id" required>
                                        <option value="" disabled selected>-- Choose a request --</option>
                                        <?php 
                                        if ($requests && $requests->num_rows > 0) {
                                            while ($req = $requests->fetch_assoc()) {
                                                $label = $req['disaster_type'] ? $req['disaster_type'] : '[New Incident]';
                                                echo "<option value='" . $req['id'] . "' data-location='" . htmlspecialchars($req['location']) . "' data-requester='" . htmlspecialchars($req['username']) . "'>" . 
                                                     htmlspecialchars($label) . ' - ' . htmlspecialchars($req['location']) . ' - ' . htmlspecialchars($req['resource_type']) . 
                                                     " (" . $req['quantity'] . " units)" .
                                                     "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Resource Selection -->
                                <div class="col-md-6 mb-3">
                                    <label for="resource_id" class="form-label">
                                        <i class="fas fa-box"></i> Available Resource *
                                    </label>
                                    <select class="form-control" id="resource_id" name="resource_id" required>
                                        <option value="" disabled selected>-- Choose a resource --</option>
                                        <optgroup label="📦 Inventory Resources">
                                        <?php 
                                        if ($resources && $resources->num_rows > 0) {
                                            while ($res = $resources->fetch_assoc()) {
                                                echo "<option value='" . $res['id'] . "'>" . 
                                                     htmlspecialchars($res['resource_name']) . 
                                                     " (Available: " . $res['quantity'] . ")" .
                                                     "</option>";
                                            }
                                        }
                                        ?>
                                        </optgroup>
                                        <?php 
                                        // Show custom resources requested by users
                                        if ($custom_resources && $custom_resources->num_rows > 0) {
                                            echo "<optgroup label=\"📝 Custom Resources Requested\">";
                                            $custom_resources->data_seek(0);
                                            while ($custom = $custom_resources->fetch_assoc()) {
                                                echo "<option value=\"0\" disabled style=\"color: #666; font-style: italic;\">" . 
                                                     htmlspecialchars($custom['resource_type']) . " (Not in inventory)" .
                                                     "</option>";
                                            }
                                            echo "</optgroup>";
                                        }
                                        ?>
                                    </select>
                                    <small class=\"text-muted\">📝 Note: Custom resources shown below are requested by users. Admin must add them to inventory before allocation.</small>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Quantity -->
                                <div class="col-md-6 mb-3">
                                    <label for="quantity_allocated" class="form-label">
                                        <i class="fas fa-cubes"></i> Quantity to Allocate *
                                    </label>
                                    <input type="number" class="form-control" id="quantity_allocated" name="quantity_allocated" 
                                           min="1" placeholder="Enter quantity" required>
                                </div>

                                <!-- Delivery Status -->
                                <div class="col-md-6 mb-3">
                                    <label for="delivery_status" class="form-label">
                                        <i class="fas fa-truck"></i> Delivery Status *
                                    </label>
                                    <select class="form-control" id="delivery_status" name="delivery_status" required>
                                        <option value="pending">Pending</option>
                                        <option value="in_transit">In Transit</option>
                                        <option value="delivered">Delivered</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Delivery Date -->
                                <div class="col-md-6 mb-3">
                                    <label for="delivery_date" class="form-label">
                                        <i class="fas fa-calendar"></i> Delivery Date *
                                    </label>
                                    <input type="date" class="form-control" id="delivery_date" name="delivery_date" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="mt-4">
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-save"></i> Allocate Resource
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
                    </div>
                    <a href="view_allocations.php" class="btn btn-primary">← Back to Allocations</a>
                </div>
            </div>
        </div>
    </div>
    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
