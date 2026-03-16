<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = trim($_POST['type'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $severity = $_POST['severity'] ?? '';
    $affected_population = (int)($_POST['affected_population'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $date = $_POST['date'] ?? date('Y-m-d H:i');

    // Validation
    if (empty($type) || empty($location) || empty($severity)) {
        $error = 'Type, Location, and Severity are required!';
    } elseif ($affected_population < 0) {
        $error = 'Affected population cannot be negative!';
    } else {
        $query = "INSERT INTO disasters (type, location, latitude, longitude, severity, affected_population, status, date) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssddisss", $type, $location, $latitude, $longitude, $severity, $affected_population, $status, $date);
        
        if ($stmt->execute()) {
            $disaster_id = $conn->insert_id;
            header("Location: view_disasters.php?success=Disaster%20added%20successfully%20(ID:%20" . $disaster_id . ")");
            exit();
        } else {
            $error = 'Error adding disaster: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Disaster - Disaster Relief System</title>
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
                    <h2><i class="fas fa-plus-circle"></i> Add New Disaster</h2>
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
                            <a href="view_disasters.php" class="alert-link">View all disasters →</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Add Disaster Form -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Disaster Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="type" class="form-label">Disaster Type <span class="text-danger">*</span></label>
                                            <select class="form-select" id="type" name="type" required>
                                                <option value="">-- Select Type --</option>
                                                <option value="Flood">🌊 Flood</option>
                                                <option value="Cyclone">🌀 Cyclone</option>
                                                <option value="Earthquake">📍 Earthquake</option>
                                                <option value="Wildfire">🔥 Wildfire</option>
                                                <option value="Landslide">🏔️ Landslide</option>
                                                <option value="Tsunami">🌊 Tsunami</option>
                                                <option value="Other">❓ Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="location" name="location" 
                                                   placeholder="e.g., Karnataka, Mumbai, Odisha" required
                                                   value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="latitude" class="form-label">Latitude</label>
                                            <input type="number" class="form-control" id="latitude" name="latitude" 
                                                   placeholder="e.g., 15.3173" step="0.0001"
                                                   value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="longitude" class="form-label">Longitude</label>
                                            <input type="number" class="form-control" id="longitude" name="longitude" 
                                                   placeholder="e.g., 75.7139" step="0.0001"
                                                   value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="severity" class="form-label">Severity <span class="text-danger">*</span></label>
                                            <select class="form-select" id="severity" name="severity" required>
                                                <option value="">-- Select Severity --</option>
                                                <option value="Low">Low</option>
                                                <option value="Medium">Medium</option>
                                                <option value="High">High</option>
                                                <option value="Critical">Critical</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="affected_population" class="form-label">Affected Population</label>
                                            <input type="number" class="form-control" id="affected_population" name="affected_population" 
                                                   placeholder="e.g., 50000" min="0"
                                                   value="<?php echo htmlspecialchars($_POST['affected_population'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status">
                                                <option value="active">Active</option>
                                                <option value="resolved">Resolved</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date" class="form-label">Date & Time</label>
                                            <input type="datetime-local" class="form-control" id="date" name="date" 
                                                   value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d\TH:i')); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Add Disaster
                                    </button>
                                    <a href="view_disasters.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Help Text -->
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i> <strong>Tip:</strong> The location coordinates (latitude/longitude) can be used later for map visualization.
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
