<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>Menu</h2>
    </div>
    <ul class="sidebar-nav">
        <li><a href="dashboard.php" class="<?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">📊 Dashboard</a></li>
        <li><a href="pigs.php" class="<?php echo in_array($currentPage, ['pigs.php', 'pig_form.php', 'pig_view.php']) ? 'active' : ''; ?>">🐷 Pig Inventory</a></li>
        <li><a href="reports.php" class="<?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>">📈 Farm Reports</a></li>
        <li><a href="profile.php" class="<?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>">👤 My Profile</a></li>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <li><a href="users.php" class="<?php echo $currentPage == 'users.php' ? 'active' : ''; ?>">⚙️ User Management</a></li>
        <?php endif; ?>
    </ul>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('mobileToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (toggleBtn && sidebar && overlay) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }
    });
</script>
