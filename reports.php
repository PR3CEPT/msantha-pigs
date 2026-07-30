<?php
require_once 'db.php';
requireLogin();

// 1. Fetch pig population by stage
$stages = $pdo->query("SELECT stage, COUNT(*) as count FROM pigs WHERE status = 'active' GROUP BY stage")->fetchAll();

// 2. Fetch pig population by gender
$genders = $pdo->query("SELECT sex, COUNT(*) as count FROM pigs WHERE status = 'active' GROUP BY sex")->fetchAll();

// 3. Fetch breeding & pregnancy summary
$pregnantRecords = $pdo->query("SELECT b.*, p.tag_no, p.breed FROM breeding_records b JOIN pigs p ON b.pig_id = p.id WHERE b.status = 'pregnant' ORDER BY b.expected_farrowing ASC")->fetchAll();

// 4. Fetch sales total & detailed ledger
$totalSales = $pdo->query("SELECT SUM(amount) FROM sales")->fetchColumn() ?: 0;
$salesList = $pdo->query("SELECT * FROM sales ORDER BY date DESC LIMIT 10")->fetchAll();

// 5. Fetch mortality list
$mortalityList = $pdo->query("SELECT m.*, p.tag_no FROM mortality m JOIN pigs p ON m.pig_id = p.id ORDER BY m.date DESC LIMIT 10")->fetchAll();

include 'includes/header.php';
?>

<style>
@media print {
    .sidebar, .topbar, .btn, .mobile-toggle-btn, .sidebar-overlay {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        page-break-inside: avoid;
    }
}
</style>

<div class="dashboard-wrapper">
    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>Msantha Farm Performance & Analytical Reports</h2>
            <p>Comprehensive report on livestock inventory, breeding performance, mortality, and sales revenue.</p>
        </div>
        <button class="btn btn-outline" onclick="window.print()">🖨️ Print Full Farm Report</button>
    </div>

    <div class="dashboard-content" style="grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Population by Stage -->
        <div class="card">
            <h3>Population Breakdown by Life Stage</h3>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                        <th style="padding: 8px;">Stage</th>
                        <th style="padding: 8px;">Active Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($stages as $s): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px; text-transform: capitalize;"><strong><?php echo htmlspecialchars($s['stage']); ?></strong></td>
                            <td style="padding: 8px;"><?php echo $s['count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($stages) === 0): ?><tr><td colspan="2" style="padding: 8px;">No active pigs found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Population by Gender -->
        <div class="card">
            <h3>Population Breakdown by Sex / Gender</h3>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                        <th style="padding: 8px;">Sex</th>
                        <th style="padding: 8px;">Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($genders as $g): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px;"><strong><?php echo htmlspecialchars($g['sex']); ?></strong></td>
                            <td style="padding: 8px;"><?php echo $g['count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($genders) === 0): ?><tr><td colspan="2" style="padding: 8px;">No data.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Active Pregnancy & Breeding Tracker -->
    <div class="card" style="margin-top: 20px;">
        <h3>Active Pregnant Sows & Expected Farrowing Schedule</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 10px;">Sow Tag</th>
                    <th>Breed</th>
                    <th>Date of Mating</th>
                    <th>Sire Tag</th>
                    <th>Expected Farrowing Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pregnantRecords as $pr): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 10px;"><strong><a href="pig_view.php?id=<?php echo $pr['pig_id']; ?>"><?php echo htmlspecialchars($pr['tag_no']); ?></a></strong></td>
                        <td><?php echo htmlspecialchars($pr['breed']); ?></td>
                        <td><?php echo htmlspecialchars($pr['date_of_service']); ?></td>
                        <td><?php echo htmlspecialchars($pr['sire_no'] ?: 'N/A'); ?></td>
                        <td><strong style="color: var(--primary-color);"><?php echo htmlspecialchars($pr['expected_farrowing']); ?></strong></td>
                        <td><span class="badge" style="padding: 4px 8px; background: #FFF3E0; color: #E65100; border-radius: 4px;">Pregnant</span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($pregnantRecords) === 0): ?>
                    <tr><td colspan="6" style="padding: 15px; text-align: center; color: var(--text-muted);">No currently active pregnant sows recorded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Sales & Financial Summary -->
    <div class="card" style="margin-top: 20px;">
        <h3>Sales & Revenue Summary</h3>
        <div style="padding: 15px 20px; background: #E8F5E9; border-radius: 8px; margin: 15px 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="color: var(--primary-color); margin: 0;">Total Revenue Generated (All Time)</h4>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Includes live pig sales and carcass/meat sales</p>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: var(--primary-color); margin: 0;">MWK <?php echo number_format($totalSales, 2); ?></p>
        </div>

        <h4 style="margin-top: 15px; color: var(--primary-color);">Recent Sales Ledger</h4>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 8px;">Date</th>
                    <th>Type</th>
                    <th>Buyer Details</th>
                    <th>Amount (MWK)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($salesList as $sl): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px;"><?php echo htmlspecialchars($sl['date']); ?></td>
                        <td style="text-transform: capitalize;"><?php echo htmlspecialchars(str_replace('_', ' ', $sl['type'])); ?></td>
                        <td><?php echo htmlspecialchars($sl['buyer_info'] ?: 'Cash Customer'); ?></td>
                        <td><strong>MWK <?php echo number_format($sl['amount'], 2); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($salesList) === 0): ?>
                    <tr><td colspan="4" style="padding: 10px; color: var(--text-muted);">No sales records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Mortality Summary -->
    <div class="card" style="margin-top: 20px;">
        <h3>Mortality & Loss Audit Log</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 8px;">Date</th>
                    <th>Pig Tag</th>
                    <th>Reported Cause of Death</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($mortalityList as $ml): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px;"><?php echo htmlspecialchars($ml['date']); ?></td>
                        <td><strong><?php echo htmlspecialchars($ml['tag_no']); ?></strong></td>
                        <td style="color: #c62828;"><strong><?php echo htmlspecialchars($ml['cause'] ?: 'Unspecified'); ?></strong></td>
                        <td><?php echo htmlspecialchars($ml['remarks'] ?: '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($mortalityList) === 0): ?>
                    <tr><td colspan="4" style="padding: 10px; color: var(--text-muted);">No mortality records logged.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
