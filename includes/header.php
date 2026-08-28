<?php
// Run stage transition detection + system alerts at most once every 2 minutes per session
if (isset($_SESSION['user_id'])) {
    if (!isset($_SESSION['last_alert_check']) || (time() - $_SESSION['last_alert_check']) > 120) {
        checkStageTransitions($pdo, $PIG_STAGE_SQL);
        generateSystemAlerts($pdo);
        $_SESSION['last_alert_check'] = time();
    }

    // Fetch unread notification count & latest 8 for the dropdown
    $unreadCount   = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
    $notifDropdown = $pdo->query("SELECT * FROM notifications ORDER BY is_read ASC, created_at DESC LIMIT 8")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <title>Msantha Pigs Management System</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo file_exists(__DIR__ . '/../css/style.css') ? filemtime(__DIR__ . '/../css/style.css') : time(); ?>">
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
                navigator.serviceWorker.register('sw.js?v=<?php echo file_exists(__DIR__ . "/../sw.js") ? filemtime(__DIR__ . "/../sw.js") : time(); ?>').then((reg) => {
                    reg.update();
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
                    <img src="images/logo.png" alt="MIGS Logo" class="topbar-logo" width="38" height="38" style="width: 38px; height: 38px; min-width: 38px; max-width: 38px; min-height: 38px; max-height: 38px; border-radius: 50%; object-fit: cover; flex-shrink: 0; display: block;" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1MCIgaGVpZ2h0PSI1MCI+PGNpcmNsZSBjeD0iMjUiIGN5PSIyNSIgcj0iMjQiIGZpbGw9IiM0Q0FGNTAiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZmlsbD0id2hpdGUiIGRvbWluYW50LWJhc2VsaW5lPSJtaWRkbGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtc2l6ZT0iMjAiPk08L3RleHQ+PC9zdmc+'">
                    <div class="brand-info">
                        <h1 class="brand-title">Msantha Pigs Management System</h1>
                        <p class="brand-slogan">Your Trusted Partner in Livestock &amp; Poultry Production</p>
                    </div>
                </div>
                <div class="user-profile">
                    <?php if (isset($_SESSION['user_id'])): ?>

                        <!-- ===== NOTIFICATION BELL ===== -->
                        <div class="notif-bell-wrap" id="notifWrap">
                            <button class="notif-bell-btn" id="notifBellBtn" aria-label="Notifications" title="System Notifications">
                                🔔
                                <?php if ($unreadCount > 0): ?>
                                    <span class="notif-badge" id="notifBadge"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
                                <?php endif; ?>
                            </button>

                            <!-- Dropdown panel -->
                            <div class="notif-dropdown" id="notifDropdown">
                                <div class="notif-header">
                                    <span>🔔 System Notifications</span>
                                    <button class="notif-mark-all" id="notifMarkAll" title="Mark all as read">✓ Mark all read</button>
                                </div>
                                <div class="notif-list" id="notifList">
                                    <?php if (empty($notifDropdown)): ?>
                                        <div class="notif-empty">
                                            <span style="font-size:1.8rem;">🎉</span>
                                            <p>All clear! No new alerts.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($notifDropdown as $n): ?>
                                            <?php
                                                $typeClass = match($n['type']) {
                                                    'alert'   => 'notif-item-alert',
                                                    'warning' => 'notif-item-warning',
                                                    'success' => 'notif-item-success',
                                                    default   => 'notif-item-info',
                                                };
                                                $unreadClass = $n['is_read'] ? '' : 'notif-item-unread';
                                            ?>
                                            <div class="notif-item <?php echo $typeClass . ' ' . $unreadClass; ?>"
                                                 data-id="<?php echo $n['id']; ?>"
                                                 <?php if (!empty($n['pig_id'])): ?>
                                                     onclick="window.location='pig_view.php?id=<?php echo $n['pig_id']; ?>'"
                                                     style="cursor:pointer;"
                                                 <?php endif; ?>>
                                                <div class="notif-item-title"><?php echo htmlspecialchars(ltrim($n['title'], '? ')); ?></div>
                                                <div class="notif-item-msg"><?php echo htmlspecialchars(ltrim($n['message'], '? ')); ?></div>
                                                <div class="notif-item-time"><?php echo date('d M Y, H:i', strtotime($n['created_at'])); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="notif-footer">
                                    <a href="notifications.php">View all notifications →</a>
                                </div>
                            </div>
                        </div>
                        <!-- ===== END NOTIFICATION BELL ===== -->

                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_fullname']); ?> (<strong><?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?></strong>)</span>
                        <a href="profile.php" class="btn btn-outline" style="padding: 0.35rem 0.65rem; font-size: 1rem; line-height: 1; display: inline-flex; align-items: center; justify-content: center;" title="My Profile" aria-label="Profile">👤</a>
                        <a href="logout.php" class="btn btn-danger" style="padding: 0.35rem 0.75rem; font-size: 0.82rem; font-weight: 600;" title="Logout">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Login</a>
                    <?php endif; ?>
                </div>
            </header>
            <main class="content-area">

<script>
// Notification Bell Logic
(function() {
    const bell     = document.getElementById('notifBellBtn');
    const dropdown = document.getElementById('notifDropdown');
    const markAll  = document.getElementById('notifMarkAll');
    const badge    = document.getElementById('notifBadge');

    if (!bell || !dropdown) return;

    // Toggle dropdown
    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('notif-dropdown-open');
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!document.getElementById('notifWrap').contains(e.target)) {
            dropdown.classList.remove('notif-dropdown-open');
        }
    });

    // Mark all as read
    if (markAll) {
        markAll.addEventListener('click', function(e) {
            e.stopPropagation();
            fetch('notify_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_all'
            }).then(() => {
                if (badge) badge.remove();
                document.querySelectorAll('.notif-item-unread').forEach(el => el.classList.remove('notif-item-unread'));
                markAll.textContent = '✓ All read';
            });
        });
    }

    // Mark individual as read on click (if pig_id link not set)
    document.querySelectorAll('.notif-item').forEach(function(item) {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            if (!id) return;
            fetch('notify_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_one&id=' + id
            });
            this.classList.remove('notif-item-unread');
        });
    });
})();

// Real-Time Active Single-Session Concurrency Heartbeat
(function() {
    <?php if (isset($_SESSION['user_id'])): ?>
    let sessionCheckTimer = null;
    let isChecking = false;

    function verifyActiveSession() {
        if (isChecking) return;
        isChecking = true;
        fetch('session_check.php', {
            method: 'GET',
            cache: 'no-store',
            headers: { 'Cache-Control': 'no-cache', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            isChecking = false;
            if (data && data.valid === false) {
                if (sessionCheckTimer) clearInterval(sessionCheckTimer);
                alert('⚠️ Session Terminated: Your account was logged in from another device or browser.');
                window.location.href = data.redirect || 'login.php?error=concurrent_session';
            }
        })
        .catch(() => {
            isChecking = false;
        });
    }

    // Check every 10 seconds in the background
    sessionCheckTimer = setInterval(verifyActiveSession, 10000);

    // Also check immediately whenever user focuses window or returns to tab
    window.addEventListener('focus', verifyActiveSession);
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            verifyActiveSession();
        }
    });
    <?php endif; ?>
})();
</script>
