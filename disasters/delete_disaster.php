<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

check_login();
check_role('any');

$id = (int)($_GET['id'] ?? 0);

if ($id == 0) {
    header("Location: view_disasters.php");
    exit();
}

$query = "DELETE FROM disasters WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: view_disasters.php?success=Disaster%20deleted%20successfully");
} else {
    header("Location: view_disasters.php?error=Error%20deleting%20disaster");
}
exit();
?>
