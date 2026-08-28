<?php
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['valid' => false, 'reason' => 'logged_out', 'redirect' => 'login.php']);
    exit();
}

if (isset($pdo) && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT session_token FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $dbToken = $stmt->fetchColumn();

        $currentSessionToken = $_SESSION['session_token'] ?? '';

        if (empty($currentSessionToken) || empty($dbToken) || !hash_equals((string)$dbToken, (string)$currentSessionToken)) {
            // Invalidate session immediately
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            echo json_encode([
                'valid' => false, 
                'reason' => 'concurrent_session', 
                'redirect' => 'login.php?error=concurrent_session',
                'message' => 'Your account was logged in from another device or browser.'
            ]);
            exit();
        }
    } catch (Exception $e) {
        // Fail open on temporary DB network glitch
        echo json_encode(['valid' => true]);
        exit();
    }
}

echo json_encode(['valid' => true]);
