<?php
require_once 'db.php';
requireLogin();

// Fetch KPIs with defensive try/catch blocks
try { $totalPigs = (int)$pdo->query("SELECT COUNT(*) FROM pigs WHERE status = 'active'")->fetchColumn(); } catch (Exception $e) { $totalPigs = 0; }
try { $males = (int)$pdo->query("SELECT COUNT(*) FROM pigs WHERE sex = 'Male' AND status = 'active'")->fetchColumn(); } catch (Exception $e) { $males = 0; }
try { $females = (int)$pdo->query("SELECT COUNT(*) FROM pigs WHERE sex = 'Female' AND status = 'active'")->fetchColumn(); } catch (Exception $e) { $females = 0; }
try { $weaners = (int)$pdo->query("SELECT COUNT(*) FROM pigs WHERE stage IN ('weaner', 'piglet') AND status = 'active'")->fetchColumn(); } catch (Exception $e) { $weaners = 0; }
try { $pregnant = (int)$pdo->query("SELECT COUNT(*) FROM breeding_records WHERE status = 'pregnant'")->fetchColumn(); } catch (Exception $e) { $pregnant = 0; }

// Male Castration Breakdown
try { $intactBoars = (int)$pdo->query("SELECT COUNT(*) FROM pigs WHERE sex = 'Male' AND (castrated = 0 OR castrated IS NULL) AND status = 'active'")->fetchColumn(); } catch (Exception $e) { $intactBoars = 0; }
try { $castratedBarrows = (int)$pdo->query("SELECT COUNT(*) FROM pigs WHERE sex = 'Male' AND castrated = 1 AND status = 'active'")->fetchColumn(); } catch (Exception $e) { $castratedBarrows = 0; }

// Fetch Maternity Watch (Pregnant Sows & Expected Farrowing Countdowns)
try {
    $maternityWatch = $pdo->query("SELECT b.*, p.tag_no, p.breed, p.id as pig_id FROM breeding_records b JOIN pigs p ON b.pig_id = p.id WHERE b.status = 'pregnant' ORDER BY b.expected_farrowing ASC LIMIT 10")->fetchAll();
} catch (Exception $e) {
    $maternityWatch = [];
}

// Fetch Recent Sales & Activity
try { $recentSales = $pdo->query("SELECT * FROM sales ORDER BY date DESC LIMIT 3")->fetchAll(); } catch (Exception $e) { $recentSales = []; }
try { $recentVaccines = $pdo->query("SELECT v.*, p.tag_no FROM vaccination_records v JOIN pigs p ON v.pig_id = p.id ORDER BY v.date DESC LIMIT 3")->fetchAll(); } catch (Exception $e) { $recentVaccines = []; }

// Fetch Recent System Activity Logs
try { $recentSystemLogs = $pdo->query("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 5")->fetchAll(); } catch (Exception $e) { $recentSystemLogs = []; }

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
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 2px;">
                    🐗 <?php echo $intactBoars; ?> Boars | ✂️ <?php echo $castratedBarrows; ?> Castrated
                </div>
            </div>
        </a>
        <a href="pigs.php?sex=Female" class="kpi-card kpi-females">
            <div class="kpi-icon">♀️</div>
            <div class="kpi-details">
                <h3>Female Pigs</h3>
                <p class="kpi-value"><?php echo $females; ?></p>
            </div>
        </a>
        <a href="pigs.php?stage=weaner" class="kpi-card">
            <div class="kpi-icon">🐖</div>
            <div class="kpi-details">
                <h3>Weaners / Piglets</h3>
                <p class="kpi-value"><?php echo $weaners; ?></p>
            </div>
        </a>
        <a href="reports.php?category=breeding" class="kpi-card kpi-pregnant">
            <div class="kpi-icon">🍼</div>
            <div class="kpi-details">
                <h3>Pregnant Females</h3>
                <p class="kpi-value"><?php echo $pregnant; ?></p>
            </div>
        </a>
    </div>

    <div class="dashboard-content">
        <div class="card recent-activity">
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 15px;">
                <h3 style="margin: 0;">📋 Live System Audit Feed</h3>
                <a href="logs.php" style="font-size: 0.85rem; color: var(--primary-color); font-weight: 600; text-decoration: none;">View All Logs &rarr;</a>
            </div>

            <div class="table-wrapper" style="border: none; box-shadow: none;">
            <table class="data-table striped middle">
                <thead>
                    <tr>
                        <th style="width:90px;">Time</th>
                        <th style="width:130px;">User</th>
                        <th>Action Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSystemLogs as $sl): ?>
                        <tr>
                            <td style="white-space: nowrap; font-family: monospace; font-size: 0.82rem;">
                                <?php echo date('H:i:s', strtotime($sl['created_at'])); ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($sl['username']); ?></strong>
                                <span class="tbl-sub"><?php echo htmlspecialchars($sl['action'] ?? ''); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($sl['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($recentSystemLogs) === 0): ?>
                        <tr class="tbl-empty"><td colspan="3">No system activities logged yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <hr style="margin: 20px 0 15px; border: 0; border-top: 1px solid var(--border-color);">
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <h3 style="margin: 0; color: #E65100;">🤰 Maternity &amp; Farrowing Watch</h3>
                <a href="reports.php?category=breeding" style="font-size: 0.85rem; color: var(--primary-color); font-weight: 600; text-decoration: none;">Full Breeding Audit &rarr;</a>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;">Tracking pregnant sows, mating dates, estimated birth dates (+114 days), and countdowns.</p>

            <div class="table-wrapper" style="border: none; box-shadow: none; margin-bottom: 15px;">
            <table class="data-table striped middle">
                <thead>
                    <tr>
                        <th>Sow Tag</th>
                        <th>Mating Date</th>
                        <th>Est. Farrowing</th>
                        <th>Countdown</th>
                        <th style="width:90px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maternityWatch as $mw): ?>
                        <?php
                            $expectedDt = new DateTime($mw['expected_farrowing']);
                            $today = new DateTime('today');
                            $daysDiff = (int)$today->diff($expectedDt)->format('%r%a');
                            if ($daysDiff > 0) {
                                $cdBadge = '<span class="tbl-badge due">⌛ Due in ' . $daysDiff . 'd</span>';
                            } elseif ($daysDiff === 0) {
                                $cdBadge = '<span class="tbl-badge today">🚨 Due Today!</span>';
                            } else {
                                $absDays = abs($daysDiff);
                                $cdBadge = '<span class="tbl-badge alarm">⚠️ Overdue ' . $absDays . 'd</span>';
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><a href="pig_view.php?id=<?php echo $mw['pig_id']; ?>" style="color: var(--primary-color); text-decoration: none;"><?php echo htmlspecialchars($mw['tag_no']); ?></a></strong>
                                <span class="tbl-sub"><?php echo htmlspecialchars($mw['breed'] ?: 'N/A'); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($mw['date_of_service']); ?></td>
                            <td><strong><?php echo htmlspecialchars($mw['expected_farrowing']); ?></strong></td>
                            <td><?php echo $cdBadge; ?></td>
                            <td><a href="pig_view.php?id=<?php echo $mw['pig_id']; ?>" class="btn btn-outline" style="padding: 3px 9px; font-size: 0.78rem;">View &rarr;</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($maternityWatch) === 0): ?>
                        <tr class="tbl-empty"><td colspan="5">No active pregnant sows recorded at this time.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <hr style="margin: 20px 0 15px; border: 0; border-top: 1px solid var(--border-color);">

            <h3>Recent Health & Sales Activity</h3>
            
            <h4 style="color: var(--primary-color); margin-top: 10px; font-size: 0.95rem;">Latest Vaccinations / Health Logs</h4>
            <div class="table-wrapper" style="border: none; box-shadow: none; margin-top: 8px;">
            <table class="data-table striped middle">
                <thead>
                    <tr>
                        <th style="width:110px;">Date</th>
                        <th style="width:110px;">Pig Tag</th>
                        <th>Vaccine / Treatment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentVaccines as $rv): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rv['date']); ?></td>
                            <td><strong><?php echo htmlspecialchars($rv['tag_no']); ?></strong></td>
                            <td><span class="tbl-badge green">💉 <?php echo htmlspecialchars($rv['vaccine']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($recentVaccines) === 0): ?>
                        <tr class="tbl-empty"><td colspan="3">No recent health logs.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <h4 style="color: var(--primary-color); margin-top: 20px; font-size: 0.95rem;">💰 Recent Live Pig Sales</h4>
            <div class="table-wrapper" style="border: none; box-shadow: none; margin-top: 8px;">
            <table class="data-table striped middle">
                <thead>
                    <tr>
                        <th style="width:100px;">Date</th>
                        <th>Buyer</th>
                        <th>Amount (MWK)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentSales as $rs): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rs['date']); ?></td>
                            <td><?php echo htmlspecialchars($rs['buyer_info'] ?: 'Cash Buyer'); ?></td>
                            <td><span class="tbl-badge green" style="font-size:0.85rem;">MWK <?php echo number_format($rs['amount'], 2); ?></span></td>
                            <td>
                                <a href="transaction_view.php?id=<?php echo $rs['id']; ?>" class="btn btn-outline" style="padding: 2px 7px; font-size: 0.72rem; white-space: nowrap;">View &rarr;</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($recentSales) === 0): ?>
                        <tr class="tbl-empty"><td colspan="4">No sales recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="card quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <a href="pig_form.php" class="btn btn-primary">+ Register New Pig</a>
                <a href="pigs.php" class="btn btn-outline">🐷 Pig Inventory</a>
                <a href="reports.php" class="btn btn-outline">📈 View Farm Reports</a>
                <a href="profile.php" class="btn btn-outline">👤 My User Profile</a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="logs.php" class="btn btn-outline">📋 View Activity Logs</a>
                    <a href="users.php" class="btn btn-success">⚙️ Manage User Accounts</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
