<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    $username = $_SESSION['username'] ?? ($_SESSION['user_fullname'] ?? 'User');
    logActivity($pdo, 'logout', "User '$username' logged out of the system");
}

session_unset();
session_destroy();
header("Location: login.php");
exit();
?>

