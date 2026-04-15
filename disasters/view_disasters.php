<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('admin');  // Only admin can access

$error = '';
$success = '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $query = "DELETE FROM disasters WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success = 'Disaster deleted successfully!';
    } else {
        $error = 'Error deleting disaster: ' . $conn->error;
    }
}

// Check for success message from URL
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}

// Get all disasters sorted by ID (newest first)
$query = "SELECT * FROM disasters ORDER BY id DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disasters - Disaster Relief System</title>
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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4><i class="fas fa-map-marker-alt"></i> Disasters Management</h4>
                        <a href="add_disaster.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Disaster
                        </a>
                    </div>

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

                    <!-- Disasters Table -->
                    <div class="card">
                        <div class="card-body">
                            <?php if ($result->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Type</th>
                                                <th>Location</th>
                                                <th>Severity</th>
                                                <th>Affected Population</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $result->fetch_assoc()): ?>
                                                <tr class="table-row-clickable" onclick="window.location.href='edit_disaster.php?id=<?php echo $row['id']; ?>'">
                                                    <td><strong>#<?php echo $row['id']; ?></strong></td>
                                                    <td>
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <?php echo htmlspecialchars($row['type']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $severity = $row['severity'];
                                                        $badge_class = $severity == 'Critical' ? 'danger' : ($severity == 'High' ? 'warning' : 'info');
                                                        ?>
                                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                                            <?php echo $severity; ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo number_format($row['affected_population']); ?></td>
                                                    <td>
                                                        <?php 
                                                        $status = $row['status'];
                                                        $badge_class = $status == 'active' ? 'success' : 'secondary';
                                                        ?>
                                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                                            <?php echo ucfirst($status); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M d, Y H:i', strtotime($row['date'])); ?></td>
                                                    <td class="action-buttons" onclick="event.stopPropagation();">
                                                        <a href="edit_disaster.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this disaster?');">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No disasters found. <a href="add_disaster.php">Create one →</a></p>
                            <?php endif; ?>
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
