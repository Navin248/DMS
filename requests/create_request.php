<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$error = '';
$success = '';

// Get current user's home location and role
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$user_query = "SELECT location, role FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_location = $user_data['location'] ?? 'Not Set';
$user_role = $user_data['role'] ?? 'user';

// Get ALL active disasters (coordinator can link any disaster from anywhere)
$disasters = $conn->query("SELECT id, type, location, severity FROM disasters WHERE status = 'active' ORDER BY date DESC");

// Get ONLY available resources with quantity > 0
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
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $priority = isset($_POST['priority']) ? trim($_POST['priority']) : '';
    
    // If "Other" is selected, use the custom resource type
    if ($resource_type === 'OTHER') {
        $resource_type = $custom_resource_type;
    }

    // Validation
    if (!$location || !$resource_type || $quantity <= 0 || !$priority) {
        $error = 'Location, resource type, quantity, and priority are required!';
    } else {
        // If disaster not selected, set it to NULL (new/unknown incident)
        $disaster_id_val = ($disaster_id == 0) ? NULL : $disaster_id;
        
        if ($disaster_id_val === NULL) {
            // Create request without disaster
            $query = "INSERT INTO requests (user_id, location, resource_type, quantity, priority, status, approval_status) 
                      VALUES (?, ?, ?, ?, ?, 'pending', 'pending')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("issss", $user_id, $location, $resource_type, $quantity, $priority);
        } else {
            // Link to existing disaster
            $query = "INSERT INTO requests (disaster_id, user_id, location, resource_type, quantity, priority, status, approval_status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'pending', 'pending')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iissss", $disaster_id_val, $user_id, $location, $resource_type, $quantity, $priority);
        }
        
        if ($stmt->execute()) {
            $success = 'Request created successfully!';
            header("Location: view_requests.php?success=" . urlencode($success));
            exit();
        } else {
            $error = 'Error creating request: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Request - Disaster Relief System</title>
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
                    <h2><i class="fas fa-plus-circle"></i> Create Relief Request</h2>
                    <hr>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="info-card">
                        <i class="fas fa-info-circle"></i> <strong>Relief Request Form</strong>
                        <p class="mt-2 mb-0">Create relief requests for any location based on urgent demands.</p>
                        <p class="mb-0 mt-2"><strong>📦 Available Resources:</strong> Only resources in Admin's inventory are shown below. Request what's available or Admin may suggest alternatives.</p>
                    </div>

                    <div class="form-section">
                        <form method="POST">
                            <div class="row">
                                <!-- Affected Location -->
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">
                                        <i class="fas fa-map-marker-alt"></i> Affected Location / Area *
                                    </label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           placeholder="e.g., Odisha, Kathmandu Valley, District Hospital" required>
                                    <small class="text-muted">Enter location where resources are needed</small>
                                </div>

                                <!-- Priority -->
                                <div class="col-md-6 mb-3">
                                    <label for="priority" class="form-label">
                                        <i class="fas fa-flag"></i> Priority Level *
                                    </label>
                                    <select class="form-control" id="priority" name="priority" required>
                                        <option value="" disabled selected>-- Choose priority --</option>
                                        <option value="Low">Low</option>
                                        <option value="Medium">Medium</option>
                                        <option value="High">High</option>
                                        <option value="Critical">Critical</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Active Disasters (Optional Link) -->
                                <div class="col-md-6 mb-3">
                                    <label for="disaster_id" class="form-label">
                                        <i class="fas fa-exclamation-triangle"></i> Link to Existing Incident (Optional)
                                    </label>
                                    <select class="form-control" id="disaster_id" name="disaster_id">
                                        <option value="0">-- None / New Unreported Incident --</option>
                                        <?php 
                                        if ($disasters && $disasters->num_rows > 0) {
                                            while ($dis = $disasters->fetch_assoc()) {
                                                $severity_badge = '';
                                                if ($dis['severity'] == 'Critical') $severity_badge = '🔴 Critical';
                                                elseif ($dis['severity'] == 'High') $severity_badge = '🟠 High';
                                                elseif ($dis['severity'] == 'Medium') $severity_badge = '🟡 Medium';
                                                else $severity_badge = '🟢 Low';
                                                
                                                echo "<option value='" . $dis['id'] . "'>" . 
                                                     htmlspecialchars($dis['type'] . ' - ' . $dis['location']) . " [" . $severity_badge ."]".
                                                     "</option>";
                                            }
                                        } else {
                                            echo "<option value='0' disabled>-- No active incidents registered --</option>";
                                        }
                                        ?>
                                    </select>
                                    <small class="text-muted">Link to known disaster or create new request</small>
                                </div>

                                <!-- Resource Type -->
                                <div class="col-md-6 mb-3">
                                    <label for="resource_type" class="form-label">
                                        <i class="fas fa-box"></i> Resource Type Needed *
                                    </label>
                                    <select class="form-control" id="resource_type" name="resource_type" required onchange="toggleCustomResource()">
                                        <option value="" disabled selected>-- Select available resource --</option>
                                        <?php 
                                        if (!empty($resource_types)) {
                                            foreach ($resource_types as $type): 
                                        ?>
                                            <option value="<?php echo htmlspecialchars($type); ?>">
                                                <?php echo htmlspecialchars($type); ?>
                                            </option>
                                        <?php 
                                            endforeach;
                                        } else {
                                            echo "<option value='' disabled>-- No resources available --</option>";
                                        }
                                        ?>
                                        <option value="OTHER" style="border-top: 1px solid #ccc; font-weight: bold; padding-top: 5px;">➕ Other (Specify Below)</option>
                                    </select>
                                    <small class="text-muted">Only shows resources currently in inventory. Select "Other" to specify a custom resource type.</small>
                                </div>
                            </div>

                            <!-- Custom Resource Type (Hidden by default) -->
                            <div class="row" id="customResourceRow" style="display: none;">
                                <div class="col-md-6 mb-3">
                                    <label for="custom_resource_type" class="form-label">
                                        <i class="fas fa-keyboard"></i> Please Specify Resource Type *
                                    </label>
                                    <input type="text" class="form-control" id="custom_resource_type" name="custom_resource_type" 
                                           placeholder="e.g., Medical Drones, Thermal Blankets, Water Purifiers, etc.">
                                    <small class="text-muted">Type the exact resource type you need. Administrator will approve based on availability.</small>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Quantity -->
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">
                                        <i class="fas fa-cubes"></i> Quantity Required *
                                    </label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" 
                                           min="1" placeholder="Enter quantity" required>
                                </div>
                            </div>

                            <!-- Info Box -->
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-phone"></i> <strong>How It Works:</strong> 
                                <ul class="mb-0 mt-2">
                                    <li>Resources are managed by Admin - only available inventory is shown</li>
                                    <li>You can respond to emergency calls from ANY location</li>
                                    <li>Link to a known incident if available, or create new request for unreported emergencies</li>
                                    <li>Admin will approve and allocate requested resources</li>
                                </ul>
                            </div>

                            <!-- Buttons -->
                            <div class="mt-4">
                                <button type="submit" class="btn btn-submit">
                                    <i class="fas fa-save"></i> Create Request
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
