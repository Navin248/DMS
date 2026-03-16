<?php
session_start();
session_destroy();
header("Location: /DMS/login.php");
exit();
?>
