<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$error = '';
$success = '';
$disaster = null;

// Get disaster ID
$id = (int)($_GET['id'] ?? 0);
if ($id == 0) {
    header("Location: view_disasters.php");
    exit();
}

// Fetch disaster
$query = "SELECT * FROM disasters WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: view_disasters.php?error=Disaster%20not%20found");
    exit();
}

$disaster = $result->fetch_assoc();

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = trim($_POST['type'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $latitude = $_POST['latitude'] ?? $disaster['latitude'];
    $longitude = $_POST['longitude'] ?? $disaster['longitude'];
    $severity = $_POST['severity'] ?? '';
    $affected_population = (int)($_POST['affected_population'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $date = $_POST['date'] ?? $disaster['date'];

    // Validation
    if (empty($type) || empty($location) || empty($severity)) {
        $error = 'Type, Location, and Severity are required!';
    } elseif ($affected_population < 0) {
        $error = 'Affected population cannot be negative!';
    } else {
        $query = "UPDATE disasters SET type=?, location=?, latitude=?, longitude=?, severity=?, affected_population=?, status=?, date=? WHERE id=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssddsissi", $type, $location, $latitude, $longitude, $severity, $affected_population, $status, $date, $id);
        
        if ($stmt->execute()) {
            // Show success and redirect after 2 seconds
            $success = 'Disaster updated successfully! Redirecting...';
            header("Refresh: 2; url=view_disasters.php?success=Disaster%20updated%20(ID:%20" . $id . ")");
            // Also refresh the data to show on current page
            $query = "SELECT * FROM disasters WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $disaster = $result->fetch_assoc();
        } else {
            $error = 'Error updating disaster: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Disaster - Disaster Relief System</title>
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
                    <h2><i class="fas fa-edit"></i> Edit Disaster #<?php echo $disaster['id']; ?></h2>
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

                    <!-- Edit Disaster Form -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Update Disaster Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="type" class="form-label">Disaster Type <span class="text-danger">*</span></label>
                                            <select class="form-select" id="type" name="type" required>
                                                <option value="">-- Select Type --</option>
                                                <option value="Flood" <?php echo $disaster['type'] == 'Flood' ? 'selected' : ''; ?>>🌊 Flood</option>
                                                <option value="Cyclone" <?php echo $disaster['type'] == 'Cyclone' ? 'selected' : ''; ?>>🌀 Cyclone</option>
                                                <option value="Earthquake" <?php echo $disaster['type'] == 'Earthquake' ? 'selected' : ''; ?>>📍 Earthquake</option>
                                                <option value="Wildfire" <?php echo $disaster['type'] == 'Wildfire' ? 'selected' : ''; ?>>🔥 Wildfire</option>
                                                <option value="Landslide" <?php echo $disaster['type'] == 'Landslide' ? 'selected' : ''; ?>>🏔️ Landslide</option>
                                                <option value="Tsunami" <?php echo $disaster['type'] == 'Tsunami' ? 'selected' : ''; ?>>🌊 Tsunami</option>
                                                <option value="Other" <?php echo $disaster['type'] == 'Other' ? 'selected' : ''; ?>>❓ Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="location" name="location" 
                                                   placeholder="e.g., Karnataka, Mumbai, Odisha" required
                                                   value="<?php echo htmlspecialchars($disaster['location']); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="latitude" class="form-label">Latitude</label>
                                            <input type="number" class="form-control" id="latitude" name="latitude" 
                                                   placeholder="e.g., 15.3173" step="0.0001"
                                                   value="<?php echo htmlspecialchars($disaster['latitude']); ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="longitude" class="form-label">Longitude</label>
                                            <input type="number" class="form-control" id="longitude" name="longitude" 
                                                   placeholder="e.g., 75.7139" step="0.0001"
                                                   value="<?php echo htmlspecialchars($disaster['longitude']); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="severity" class="form-label">Severity <span class="text-danger">*</span></label>
                                            <select class="form-select" id="severity" name="severity" required>
                                                <option value="">-- Select Severity --</option>
                                                <option value="Low" <?php echo $disaster['severity'] == 'Low' ? 'selected' : ''; ?>>Low</option>
                                                <option value="Medium" <?php echo $disaster['severity'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="High" <?php echo $disaster['severity'] == 'High' ? 'selected' : ''; ?>>High</option>
                                                <option value="Critical" <?php echo $disaster['severity'] == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="affected_population" class="form-label">Affected Population</label>
                                            <input type="number" class="form-control" id="affected_population" name="affected_population" 
                                                   placeholder="e.g., 50000" min="0"
                                                   value="<?php echo htmlspecialchars($disaster['affected_population']); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="active" <?php echo $disaster['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="resolved" <?php echo $disaster['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date" class="form-label">Date & Time</label>
                                            <input type="datetime-local" class="form-control" id="date" name="date" 
                                                   value="<?php echo date('Y-m-d\TH:i', strtotime($disaster['date'])); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Update Disaster
                                    </button>
                                    <a href="view_disasters.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Disaster Info Card -->
                    <div class="card mt-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Current Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Created:</strong> <?php echo date('M d, Y H:i', strtotime($disaster['created_at'])); ?></p>
                                    <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($disaster['updated_at'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Requests:</strong> 
                                        <?php 
                                        $req_count = $conn->query("SELECT COUNT(*) as count FROM requests WHERE disaster_id = {$disaster['id']}")->fetch_assoc()['count'];
                                        echo $req_count . " pending request(s)";
                                        ?>
                                    </p>
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
</body>
</html>
