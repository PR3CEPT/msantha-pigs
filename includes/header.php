<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Msantha Pigs Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- PWA & Mobile Icons -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2E7D32">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Msantha Pigs">
    <link rel="apple-touch-icon" href="images/icon-192.png">
    <link rel="icon" type="image/png" sizes="192x192" href="images/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="images/icon-512.png">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').then((reg) => {
                    console.log('PWA Service Worker registered:', reg.scope);
                }).catch((err) => {
                    console.log('PWA Service Worker registration failed:', err);
                });
            });
        }
    </script>
</head>
<body>
    <div class="layout">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php include 'includes/sidebar.php'; ?>
        <?php endif; ?>
        
        <div class="main-content">
            <header class="topbar">
                <div class="logo-container">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="mobile-toggle-btn" id="mobileToggle" aria-label="Toggle navigation">☰</button>
                    <?php endif; ?>
                    <img src="images/logo.png" alt="MIGS Logo" class="topbar-logo" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCI+PGNpcmNsZSBjeD0iMjUiIGN5PSIyNSIgcj0iMjQiIGZpbGw9IiM0Q0FGNTAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZmlsbD0id2hpdGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtc2l6ZT0iMjAiPk08L3RleHQ+PC9zdmc+'">
                    <div>
                        <h1 class="brand-title">Msantha Pigs Management System</h1>
                        <p class="brand-slogan">Your Trusted Partner in Livestock & Poultry Production</p>
                    </div>
                </div>
                <div class="user-profile">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_fullname']); ?> (<strong><?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?></strong>)</span>
                        <a href="profile.php" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Profile</a>
                        <a href="logout.php" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Login</a>
                    <?php endif; ?>
                </div>
            </header>
            <main class="content-area">
