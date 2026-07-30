<?php
session_start();

$host = 'localhost';
$dbname = 'msantha_pigs';
$user = 'root';
$pass = ''; // Default XAMPP password is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Auto-seed admin and clerk users if they don't exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
        $clerkHash = password_hash('clerk123', PASSWORD_DEFAULT);
        
        $insertStmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, phone) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute(['admin', $adminHash, 'admin', 'System Admin', '0000000000']);
        $insertStmt->execute(['clerk', $clerkHash, 'clerk', 'Farm Clerk', '0000000000']);
    }

} catch(PDOException $e) {
    die("Database Connection failed: " . $e->getMessage() . "<br>Please ensure XAMPP MySQL is running and you have imported setup.sql.");
}

// Authentication Check function
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        die("Forbidden: Admins only.");
    }
}
?>
