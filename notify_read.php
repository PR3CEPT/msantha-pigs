<?php
require_once 'db.php';
requireLogin();
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? 'mark_all';
$id     = (int)($_POST['id'] ?? 0);

try {
    if ($action === 'mark_all') {
        $pdo->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
        echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
    } elseif ($action === 'mark_one' && $id > 0) {
        $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'count') {
        $count = $pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
        echo json_encode(['unread' => (int)$count]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
