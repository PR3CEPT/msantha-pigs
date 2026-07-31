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
        $_SESSION['user_fullname'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Msantha Pigs Management System</title>
    <link rel="stylesheet" href="css/style.css">
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

        <!-- Modern Landing Footer on Login Page -->
        <footer class="modern-landing-footer" style="margin-top: 3rem;">
            <div class="modern-landing-footer-top">
                <div class="modern-landing-footer-brand">
                    <div class="brand-header">
                        <img src="images/logo.png" alt="MIGS Logo" class="modern-landing-logo" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCI+PGNpcmNsZSBjeD0iMjUiIGN5PSIyNSIgcj0iMjQiIGZpbGw9IiM0Q0FGNTAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZmlsbD0id2hpdGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtc2l6ZT0iMjAiPk08L3RleHQ+PC9zdmc+'">
                        <div>
                            <h3>Msantha Investments &amp; General Suppliers</h3>
                            <p class="brand-slogan">Your Trusted Partner in Livestock &amp; Poultry Production</p>
                        </div>
                    </div>
                    <p class="brand-address">📍 Mawira area along Chipamba Road, Liwonde, Machinga District, Malawi</p>
                </div>

                <div class="modern-landing-footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home Page</a></li>
                        <li><a href="login.php">System Login</a></li>
                        <li><a href="tel:+265888899620">Customer Support</a></li>
                    </ul>
                </div>

                <div class="modern-landing-footer-contact">
                    <h4>Contact Info</h4>
                    <div class="contact-pill">
                        <span class="icon">📞</span>
                        <div>
                            <span class="label">Phone Support</span>
                            <a href="tel:+265888899620" class="value">+265 888 899 620 / +265 999 899 620</a>
                        </div>
                    </div>
                    <div class="contact-pill">
                        <span class="icon">✉️</span>
                        <div>
                            <span class="label">Email Address</span>
                            <a href="mailto:icchipeta@gmail.com" class="value">icchipeta@gmail.com</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modern-landing-footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <strong>Msantha Investments and General Suppliers (MIGS)</strong>. All Rights Reserved.</p>
                <span class="badge">MIGS v2.4 System</span>
            </div>
        </footer>
    </div>
</body>
</html>
