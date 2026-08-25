<?php
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_fullname'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        
        logActivity($pdo, 'login', "User '{$user['username']}' logged into system successfully", $user['id'], $user['username'], $user['role']);
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
        logActivity($pdo, 'login_failed', "Failed login attempt for username '" . htmlspecialchars($username) . "'", null, $username, 'guest');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>Login - Msantha Pigs Management System</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo file_exists(__DIR__ . '/css/style.css') ? filemtime(__DIR__ . '/css/style.css') : time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="text-center mb-2">
                <img src="images/logo.png" alt="MIGS Logo" width="90" style="border-radius: 50%; box-shadow: 0 4px 8px rgba(0,0,0,0.15);">
                <h3 style="color: var(--primary-color); font-size: 1.1rem; margin-top: 10px; font-weight: 700;">Msantha Investments</h3>
                <p style="color: var(--text-muted); font-size: 0.85rem;">Pigs & Livestock Management System</p>
            </div>
            
            <h2 style="font-size: 1.5rem; color: #111827; margin: 1.5rem 0 1rem; border-bottom: 2px solid var(--bg-color); padding-bottom: 0.5rem;">System Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username (e.g. admin)" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block" style="padding: 0.9rem; font-size: 1.05rem; font-weight: 700; margin-top: 1rem;">Login to Dashboard &rarr;</button>
            </form>

            <div class="text-center mt-2" style="margin-top: 1.5rem;">
                <a href="index.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">&larr; Back to Home</a>
            </div>
        </div>


    </div>
</body>
</html>
