<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$error = '';
$success = '';
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$request_id) {
    header("Location: view_requests.php");
    exit();
}

// Get request details
$query = "SELECT r.*, d.type as disaster_type, d.location as disaster_location 
          FROM requests r 
          LEFT JOIN disasters d ON r.disaster_id = d.id 
          WHERE r.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    header("Location: view_requests.php");
    exit();
}

// Get active disasters for dropdown
$disasters = $conn->query("SELECT id, type, location FROM disasters WHERE status = 'active' ORDER BY date DESC");

// Get available resource types (only those with quantity > 0)
$available_resources = $conn->query("SELECT DISTINCT resource_name FROM resources WHERE quantity > 0 ORDER BY resource_name");
$resource_types = [];
if ($available_resources && $available_resources->num_rows > 0) {
    while ($res = $available_resources->fetch_assoc()) {
        $resource_types[] = $res['resource_name'];
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $disaster_id = isset($_POST['disaster_id']) ? (int)$_POST['disaster_id'] : 0;
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $resource_type = isset($_POST['resource_type']) ? trim($_POST['resource_type']) : '';
    $custom_resource_type = isset($_POST['custom_resource_type']) ? trim($_POST['custom_resource_type']) : '';
    
    // If "Other" is selected, use the custom resource type
    if ($resource_type === 'OTHER') {
        $resource_type = $custom_resource_type;
    }
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $priority = isset($_POST['priority']) ? trim($_POST['priority']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';

    // Validation - disaster_id is optional, status is managed by approval system
    if (!$location || !$resource_type || $quantity <= 0 || !$priority) {
        $error = 'Location, resource type, quantity, and priority are required!';
    } elseif ($request['approval_status'] !== 'pending') {
        $error = 'Cannot edit requests that are already approved or rejected!';
    } else {
        // Update request - convert disaster_id 0 to NULL
        $disaster_id_val = ($disaster_id == 0) ? NULL : $disaster_id;
        $update_query = "UPDATE requests SET disaster_id = ?, location = ?, resource_type = ?, quantity = ?, priority = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("isssii", $disaster_id_val, $location, $resource_type, $quantity, $priority, $request_id);
        
        if ($update_stmt->execute()) {
            $success = 'Request updated successfully!';
            header("Location: view_requests.php?success=" . urlencode($success));
            exit();
        } else {
            $error = 'Error updating request: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Request - Disaster Relief System</title>
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row" style="min-height: 100vh;">
            <?php include '../includes/sidebar.php'; ?>
            
            <div class="col-md-9 p-4" style="background-color: #F3F4F6;">
                <?php include '../includes/header.php'; ?>
                
                <div class="mt-4">
                    <h2><i class="fas fa-edit"></i> Edit Relief Request</h2>
                    <div class="mb-3">
                        <span class="info-badge">Request ID: #<?php echo $request['id']; ?></span>
                        <span class="info-badge">Status: <?php echo $request['approval_status'] == 'pending' ? '<span class="badge bg-warning">Pending Approval</span>' : '<span class="badge bg-success">Approved</span>'; ?></span>
                        <?php if ($request['disaster_type']): ?>
                            <span class="info-badge">Incident: <?php echo htmlspecialchars($request['disaster_type']); ?></span>
                        <?php else: ?>
                            <span class="info-badge"><span class="badge bg-secondary">New Incident</span></span>
                        <?php endif; ?>
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
                                <!-- Disaster Selection (Optional) -->
                                <div class="col-md-12 mb-3">
                                    <label for="disaster_id" class="form-label">
                                        <i class="fas fa-exclamation-triangle"></i> Related Disaster/Incident (Optional)
                                    </label>
                                    <select class="form-control" id="disaster_id" name="disaster_id">
                                        <option value="0" <?php echo !$request['disaster_id'] ? 'selected' : ''; ?>>▪ New Incident (No disaster link)</option>
                                        <?php 
                                        $disasters->data_seek(0);
                                        if ($disasters && $disasters->num_rows > 0) {
                                            while ($dis = $disasters->fetch_assoc()) {
                                                $selected = $dis['id'] == $request['disaster_id'] ? 'selected' : '';
                                                echo "<option value='" . $dis['id'] . "' $selected>" . 
                                                     htmlspecialchars($dis['type'] . ' - ' . $dis['location']) . 
                                                     "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <small class="text-muted">Select a disaster or leave as 'New Incident' for unreported emergencies</small>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Location -->
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Affected Location
                                    </label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo htmlspecialchars($request['location']); ?>" required>
                                </div>

                                <!-- Priority -->
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">
                                        <i class="fas fa-flag"></i> Priority Level
                                    </label>
                                    <select class="form-control" id="priority" name="priority" required>
                                        <option value="Low" <?php echo $request['priority'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                                        <option value="Medium" <?php echo $request['priority'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                        <option value="High" <?php echo $request['priority'] == 'High' ? 'selected' : ''; ?>>High</option>
                                        <option value="Critical" <?php echo $request['priority'] == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Resource Type -->
                                <div class="col-md-6 mb-3">
                                    <label for="resource_type" class="form-label">
                                        <i class="fas fa-box"></i> Resource Type (Available Only)
                                    </label>
                                    <select class="form-control" id="resource_type" name="resource_type" required onchange="toggleCustomResource()">
                                        <option value="" disabled>-- Select a resource --</option>
                                        <?php 
                                        // Check if current resource is a custom one (not in the predefined list)
                                        $isCustomResource = !in_array($request['resource_type'], $resource_types);
                                        
                                        if (count($resource_types) > 0) {
                                            foreach ($resource_types as $type) {
                                                $selected = $type == $request['resource_type'] ? 'selected' : '';
                                                echo "<option value='" . htmlspecialchars($type) . "' $selected>" . htmlspecialchars($type) . "</option>";
                                            }
                                        } else {
                                            echo "<option disabled>No resources available</option>";
                                        }
                                        ?>
                                        <option value="OTHER" <?php echo $isCustomResource ? 'selected' : ''; ?> style="border-top: 1px solid #ccc; font-weight: bold; padding-top: 5px;">➕ Other (Specify Below)</option>
                                    </select>
                                    <small class="text-muted">Only showing resources that are currently in stock. Select "Other" to specify a custom resource type.</small>
                                </div>

                            <!-- Custom Resource Type (Hidden by default, shown if OTHER is selected) -->
                            <?php 
                            $isCustomResource = !in_array($request['resource_type'], $resource_types);
                            $displayStyle = $isCustomResource ? 'block' : 'none';
                            ?>
                            <div class="row" id="customResourceRow" style="display: <?php echo $displayStyle; ?>;">
                                <div class="col-md-6 mb-3">
                                    <label for="custom_resource_type" class="form-label">
                                        <i class="fas fa-keyboard"></i> Specify Resource Type *
                                    </label>
                                    <input type="text" class="form-control" id="custom_resource_type" name="custom_resource_type" 
                                           value="<?php echo $isCustomResource ? htmlspecialchars($request['resource_type']) : ''; ?>"
                                           placeholder="e.g., Medical Drones, Thermal Blankets, Water Purifiers, etc.">
                                    <small class="text-muted">Type the exact resource type. Administrator will approve based on availability.</small>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Quantity -->
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">
                                        <i class="fas fa-cubes"></i> Quantity Required
                                    </label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" 
                                           value="<?php echo $request['quantity']; ?>" min="1" required>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="mt-4">
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-save"></i> Update Request
                                </button>
                                <a href="view_requests.php" class="btn btn-secondary ms-2">
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
        // Toggle custom resource type input
        function toggleCustomResource() {
            const resourceType = document.getElementById('resource_type').value;
            const customResourceRow = document.getElementById('customResourceRow');
            const customResourceInput = document.getElementById('custom_resource_type');
            
            if (resourceType === 'OTHER') {
                customResourceRow.style.display = 'block';
                customResourceInput.required = true;
            } else {
                customResourceRow.style.display = 'none';
                customResourceInput.required = false;
                customResourceInput.value = '';
            }
        }
        
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
