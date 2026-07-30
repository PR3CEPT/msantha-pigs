<?php
require_once 'db.php';
requireLogin();

// Fetch KPIs
$totalPigs = $pdo->query("SELECT COUNT(*) FROM pigs WHERE status = 'active'")->fetchColumn();
$males = $pdo->query("SELECT COUNT(*) FROM pigs WHERE sex = 'Male' AND status = 'active'")->fetchColumn();
$females = $pdo->query("SELECT COUNT(*) FROM pigs WHERE sex = 'Female' AND status = 'active'")->fetchColumn();
$weaners = $pdo->query("SELECT COUNT(*) FROM pigs WHERE stage IN ('weaner', 'piglet') AND status = 'active'")->fetchColumn();
$pregnant = $pdo->query("SELECT COUNT(*) FROM breeding_records WHERE status = 'pregnant'")->fetchColumn();

// Fetch Recent Sales & Activity
$recentSales = $pdo->query("SELECT * FROM sales ORDER BY date DESC LIMIT 3")->fetchAll();
$recentVaccines = $pdo->query("SELECT v.*, p.tag_no FROM vaccination_records v JOIN pigs p ON v.pig_id = p.id ORDER BY v.date DESC LIMIT 3")->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-header">
        <h2><?php echo $_SESSION['user_role'] === 'admin' ? 'Admin' : 'Clerk'; ?> Dashboard</h2>
        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user_fullname']); ?></strong> (<?php echo ucfirst(htmlspecialchars($_SESSION['user_role'])); ?>)</p>
    </div>

    <div class="kpi-container">
        <a href="pigs.php" class="kpi-card kpi-total">
            <div class="kpi-icon">🐷</div>
            <div class="kpi-details">
                <h3>Active Pigs</h3>
                <p class="kpi-value"><?php echo $totalPigs; ?></p>
            </div>
        </a>
        <a href="pigs.php?sex=Male" class="kpi-card kpi-males">
            <div class="kpi-icon">♂️</div>
            <div class="kpi-details">
                <h3>Male Pigs</h3>
                <p class="kpi-value"><?php echo $males; ?></p>
            </div>
        </a>
        <a href="pigs.php?sex=Female" class="kpi-card kpi-females">
            <div class="kpi-icon">♀️</div>
            <div class="kpi-details">
                <h3>Female Pigs</h3>
                <p class="kpi-value"><?php echo $females; ?></p>
            </div>
        </a>
        <a href="pigs.php" class="kpi-card">
            <div class="kpi-icon">🐖</div>
            <div class="kpi-details">
                <h3>Weaners / Piglets</h3>
                <p class="kpi-value"><?php echo $weaners; ?></p>
            </div>
        </a>
        <a href="reports.php" class="kpi-card kpi-pregnant">
            <div class="kpi-icon">🍼</div>
            <div class="kpi-details">
                <h3>Pregnant Females</h3>
                <p class="kpi-value"><?php echo $pregnant; ?></p>
            </div>
        </a>
    </div>

    <div class="dashboard-content">
        <div class="card recent-activity">
            <h3>Recent Health & Sales Activity</h3>
            
            <h4 style="color: var(--primary-color); margin-top: 10px; font-size: 0.95rem;">Latest Vaccinations / Health Logs</h4>
            <table style="width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 1px solid #ccc; text-align: left;">
                        <th style="padding: 6px;">Date</th>
                        <th>Pig Tag</th>
                        <th>Vaccine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentVaccines as $rv): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 6px;"><?php echo htmlspecialchars($rv['date']); ?></td>
                            <td><strong><?php echo htmlspecialchars($rv['tag_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($rv['vaccine']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($recentVaccines) === 0): ?>
                        <tr><td colspan="3" style="padding: 10px; color: var(--text-muted);">No recent health logs.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h4 style="color: var(--primary-color); margin-top: 20px; font-size: 0.95rem;">Recent Live Pig Sales</h4>
            <table style="width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 1px solid #ccc; text-align: left;">
                        <th style="padding: 6px;">Date</th>
                        <th>Buyer</th>
                        <th>Amount (MWK)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSales as $rs): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 6px;"><?php echo htmlspecialchars($rs['date']); ?></td>
                            <td><?php echo htmlspecialchars($rs['buyer_info'] ?: 'Cash Buyer'); ?></td>
                            <td><strong>MWK <?php echo number_format($rs['amount'], 2); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($recentSales) === 0): ?>
                        <tr><td colspan="3" style="padding: 10px; color: var(--text-muted);">No sales recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <a href="pig_form.php" class="btn btn-primary">+ Register New Pig</a>
                <a href="reports.php" class="btn btn-outline">📈 View Farm Reports</a>
                <a href="profile.php" class="btn btn-outline">👤 My User Profile</a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="users.php" class="btn btn-success">⚙️ Manage User Accounts</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
