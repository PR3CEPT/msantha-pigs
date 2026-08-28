<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? ($_SESSION['user_fullname'] ?? 'User');
    logActivity($pdo, 'logout', "User '$username' logged out of the system");
    try {
        $pdo->prepare("UPDATE users SET session_token = NULL WHERE id = ?")->execute([$userId]);
    } catch (Exception $e) {}
}

session_unset();
session_destroy();
header("Location: login.php");
exit();
?>

