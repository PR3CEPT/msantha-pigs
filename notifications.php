<?php
require_once 'db.php';
requireLogin();

$typeFilter   = $_GET['type'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';

$where  = ['1=1'];
$params = [];

if ($typeFilter !== 'all') {
    $where[]  = 'type = ?';
    $params[] = $typeFilter;
}
if ($statusFilter === 'unread') {
    $where[]  = 'is_read = 0';
} elseif ($statusFilter === 'read') {
    $where[]  = 'is_read = 1';
}

$sql   = "SELECT * FROM notifications WHERE " . implode(' AND ', $where) . " ORDER BY is_read ASC, created_at DESC LIMIT 200";
$stmt  = $pdo->prepare($sql);
$stmt->execute($params);
$notifs = $stmt->fetchAll();

$totalUnread = (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();

include 'includes/header.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
            <h2>🔔 System Notifications</h2>
            <p>All system alerts: stage transitions, farrowing reminders, overdue sows, and livestock updates.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <?php if ($totalUnread > 0): ?>
                <button class="btn btn-primary" onclick="markAllRead()" style="display:flex; align-items:center; gap:6px;">
                    ✓ Mark All (<?php echo $totalUnread; ?>) Read
                </button>
            <?php endif; ?>
            <button class="btn btn-outline" onclick="clearReadNotifs()" style="display:flex; align-items:center; gap:6px;">
                🗑️ Clear Read Alerts
            </button>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-container" style="margin-bottom:20px;">
        <div class="kpi-card kpi-total">
            <div class="kpi-icon">🔔</div>
            <div class="kpi-details">
                <h3>Total Active Alerts</h3>
                <p class="kpi-value"><?php echo number_format(count($notifs)); ?></p>
            </div>
        </div>
        <div class="kpi-card" style="border-left-color:#E65100;">
            <div class="kpi-icon">🚨</div>
            <div class="kpi-details">
                <h3>Unread Alerts</h3>
                <p class="kpi-value" style="color:<?php echo $totalUnread > 0 ? '#C62828' : 'var(--text-main)'; ?>;">
                    <?php echo $totalUnread; ?>
                </p>
            </div>
        </div>
        <?php
            $alertCount   = $pdo->query("SELECT COUNT(*) FROM notifications WHERE type = 'alert' AND is_read = 0")->fetchColumn();
            $warningCount = $pdo->query("SELECT COUNT(*) FROM notifications WHERE type = 'warning' AND is_read = 0")->fetchColumn();
        ?>
        <div class="kpi-card kpi-pregnant">
            <div class="kpi-icon">⌛</div>
            <div class="kpi-details">
                <h3>Farrowing Warnings</h3>
                <p class="kpi-value"><?php echo $warningCount; ?></p>
            </div>
        </div>
        <div class="kpi-card" style="border-left-color:#C62828;">
            <div class="kpi-icon">🆘</div>
            <div class="kpi-details">
                <h3>Critical Alerts</h3>
                <p class="kpi-value" style="color:<?php echo $alertCount > 0 ? '#C62828' : 'inherit'; ?>;">
                    <?php echo $alertCount; ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card" style="margin-bottom:20px; padding:1rem 1.5rem;">
        <form method="GET" action="notifications.php" style="display:flex; gap:15px; flex-wrap:wrap; align-items:flex-end;">
            <div>
                <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:4px;">Alert Type:</label>
                <select name="type" class="form-control" onchange="this.form.submit()" style="padding:0.4rem 0.8rem; font-size:0.9rem; min-width:160px;">
                    <option value="all"     <?php echo $typeFilter==='all'     ?'selected':''; ?>>All Types</option>
                    <option value="alert"   <?php echo $typeFilter==='alert'   ?'selected':''; ?>>🚨 Critical Alerts</option>
                    <option value="warning" <?php echo $typeFilter==='warning' ?'selected':''; ?>>⌛ Warnings</option>
                    <option value="info"    <?php echo $typeFilter==='info'    ?'selected':''; ?>>ℹ️ Info (Stage Updates)</option>
                    <option value="success" <?php echo $typeFilter==='success' ?'selected':''; ?>>✅ Success</option>
                </select>
            </div>
            <div>
                <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:4px;">Read Status:</label>
                <select name="status" class="form-control" onchange="this.form.submit()" style="padding:0.4rem 0.8rem; font-size:0.9rem; min-width:140px;">
                    <option value="all"    <?php echo $statusFilter==='all'    ?'selected':''; ?>>All Notifications</option>
                    <option value="unread" <?php echo $statusFilter==='unread' ?'selected':''; ?>>Unread Only</option>
                    <option value="read"   <?php echo $statusFilter==='read'   ?'selected':''; ?>>Read Only</option>
                </select>
            </div>
            <div style="display:flex; gap:8px;">
                <a href="notifications.php" class="btn btn-outline" style="padding:0.45rem 1rem; font-size:0.85rem;">Reset</a>
            </div>
        </form>
    </div>

    <!-- Notifications List -->
    <?php if (empty($notifs)): ?>
        <div class="card" style="text-align:center; padding:3rem;">
            <div style="font-size:3rem; margin-bottom:1rem;">🎉</div>
            <h3>All Clear!</h3>
            <p style="color:var(--text-muted);">No notifications match your current filters.</p>
        </div>
    <?php else: ?>
        <div style="display:flex; flex-direction:column; gap:10px;">
            <?php foreach ($notifs as $n): ?>
                <?php
                    $borderColor = match($n['type']) {
                        'alert'   => '#C62828',
                        'warning' => '#E65100',
                        'success' => '#2E7D32',
                        default   => '#1565C0',
                    };
                    $bgColor = match($n['type']) {
                        'alert'   => '#FFEBEE',
                        'warning' => '#FFF3E0',
                        'success' => '#E8F5E9',
                        default   => '#E3F2FD',
                    };
                    $typeIcon = match($n['type']) {
                        'alert'   => '🚨',
                        'warning' => '⌛',
                        'success' => '✅',
                        default   => 'ℹ️',
                    };
                    $unreadDot = !$n['is_read'] ? '<span style="width:8px;height:8px;border-radius:50%;background:#C62828;display:inline-block;margin-right:6px;flex-shrink:0;"></span>' : '';
                ?>
                <div class="card" style="
                    border-left: 4px solid <?php echo $borderColor; ?>;
                    background: <?php echo $n['is_read'] ? '#fff' : $bgColor; ?>;
                    padding: 1rem 1.2rem;
                    display: flex;
                    justify-content: space-between;
                    align-items: flex-start;
                    gap: 15px;
                    opacity: <?php echo $n['is_read'] ? '0.75' : '1'; ?>;
                    transition: opacity 0.2s;
                " id="notif-<?php echo $n['id']; ?>">
                    <div style="flex:1;">
                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:5px;">
                            <?php echo $unreadDot; ?>
                            <span style="font-size:1rem;"><?php echo $typeIcon; ?></span>
                            <strong style="font-size:0.95rem; color:<?php echo $borderColor; ?>;"><?php echo htmlspecialchars(ltrim($n['title'], '? ')); ?></strong>
                        </div>
                        <p style="margin:0 0 6px; font-size:0.9rem; color:#333; line-height:1.5;"><?php echo htmlspecialchars(ltrim($n['message'], '? ')); ?></p>
                        <span style="font-size:0.78rem; color:var(--text-muted);">🕐 <?php echo date('d M Y, H:i', strtotime($n['created_at'])); ?></span>
                    </div>
                    <div style="display:flex; flex-direction:column; gap:6px; flex-shrink:0;">
                        <?php if (!empty($n['pig_id'])): ?>
                            <a href="pig_view.php?id=<?php echo $n['pig_id']; ?>" class="btn btn-outline" style="padding:4px 10px; font-size:0.78rem; white-space:nowrap;">View Pig →</a>
                        <?php endif; ?>
                        <?php if (!$n['is_read']): ?>
                            <button class="btn btn-outline" onclick="markOneRead(<?php echo $n['id']; ?>)" style="padding:4px 10px; font-size:0.78rem;">✓ Mark Read</button>
                        <?php endif; ?>
                        <button class="btn btn-outline" onclick="deleteOneNotif(<?php echo $n['id']; ?>)" style="padding:4px 10px; font-size:0.78rem; color:#C62828; border-color:#FFCDD2;" title="Dismiss alert">✕ Dismiss</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function markOneRead(id) {
    fetch('notify_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mark_one&id=' + id
    }).then(() => {
        const card = document.getElementById('notif-' + id);
        if (card) {
            card.style.opacity = '0.75';
            const btn = card.querySelector('[onclick^="markOneRead"]');
            if (btn) btn.remove();
            const dot = card.querySelector('span[style*="border-radius:50%"]');
            if (dot) dot.remove();
        }
    });
}

function deleteOneNotif(id) {
    fetch('notify_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=delete_one&id=' + id
    }).then(() => {
        const card = document.getElementById('notif-' + id);
        if (card) {
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.95)';
            setTimeout(() => card.remove(), 300);
        }
    });
}

function markAllRead() {
    fetch('notify_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=mark_all'
    }).then(() => location.reload());
}

function clearReadNotifs() {
    if (!confirm('Are you sure you want to permanently clear all read alerts?')) return;
    fetch('notify_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=clear_read'
    }).then(() => location.reload());
}
</script>

<?php include 'includes/footer.php'; ?>
