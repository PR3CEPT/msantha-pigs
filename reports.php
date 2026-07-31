<?php
require_once 'db.php';
requireLogin();

// Filter parameters
$startDate   = $_GET['start_date'] ?? '';
$endDate     = $_GET['end_date'] ?? '';
$category    = $_GET['category'] ?? 'all';
$sexFilter   = $_GET['sex'] ?? 'all';
$stageFilter = $_GET['stage'] ?? 'all';

// Customization Toggles (Default all enabled if not specified)
$isCustomized = isset($_GET['customized']);
$incMetrics   = $isCustomized ? isset($_GET['inc_metrics']) : true;
$incInventory = $isCustomized ? isset($_GET['inc_inventory']) : true;
$incBreeding  = $isCustomized ? isset($_GET['inc_breeding']) : true;
$incSales     = $isCustomized ? isset($_GET['inc_sales']) : true;
$incHealth    = $isCustomized ? isset($_GET['inc_health']) : true;
$incMortality = $isCustomized ? isset($_GET['inc_mortality']) : true;

// Build Pig Inventory Queries
$pigWhere = ["1=1"];
$pigParams = [];
if ($sexFilter !== 'all') { $pigWhere[] = "sex = ?"; $pigParams[] = $sexFilter; }
if ($stageFilter !== 'all') { $pigWhere[] = "stage = ?"; $pigParams[] = $stageFilter; }

$stages = $pdo->prepare("SELECT stage, COUNT(*) as count FROM pigs WHERE status = 'active' AND " . implode(' AND ', $pigWhere) . " GROUP BY stage");
$stages->execute($pigParams);
$stagesList = $stages->fetchAll();

$genders = $pdo->prepare("SELECT sex, COUNT(*) as count FROM pigs WHERE status = 'active' AND " . implode(' AND ', $pigWhere) . " GROUP BY sex");
$genders->execute($pigParams);
$gendersList = $genders->fetchAll();

// Breeding Query
$breedWhere = ["b.status = 'pregnant'"];
$breedParams = [];
if (!empty($startDate)) { $breedWhere[] = "b.date_of_service >= ?"; $breedParams[] = $startDate; }
if (!empty($endDate)) { $breedWhere[] = "b.date_of_service <= ?"; $breedParams[] = $endDate; }
if ($sexFilter !== 'all') { $breedWhere[] = "p.sex = ?"; $breedParams[] = $sexFilter; }

$pregnantStmt = $pdo->prepare("SELECT b.*, p.tag_no, p.breed FROM breeding_records b JOIN pigs p ON b.pig_id = p.id WHERE " . implode(' AND ', $breedWhere) . " ORDER BY b.expected_farrowing ASC");
$pregnantStmt->execute($breedParams);
$pregnantRecords = $pregnantStmt->fetchAll();

// Sales Query
$salesWhere = ["1=1"];
$salesParams = [];
if (!empty($startDate)) { $salesWhere[] = "date >= ?"; $salesParams[] = $startDate; }
if (!empty($endDate)) { $salesWhere[] = "date <= ?"; $salesParams[] = $endDate; }

$totalSalesStmt = $pdo->prepare("SELECT SUM(amount) FROM sales WHERE " . implode(' AND ', $salesWhere));
$totalSalesStmt->execute($salesParams);
$totalSales = $totalSalesStmt->fetchColumn() ?: 0;

$salesListStmt = $pdo->prepare("SELECT * FROM sales WHERE " . implode(' AND ', $salesWhere) . " ORDER BY date DESC LIMIT 50");
$salesListStmt->execute($salesParams);
$salesList = $salesListStmt->fetchAll();

// Vaccination Query
$vacWhere = ["1=1"];
$vacParams = [];
if (!empty($startDate)) { $vacWhere[] = "v.date >= ?"; $vacParams[] = $startDate; }
if (!empty($endDate)) { $vacWhere[] = "v.date <= ?"; $vacParams[] = $endDate; }

$vaccinesListStmt = $pdo->prepare("SELECT v.*, p.tag_no FROM vaccination_records v JOIN pigs p ON v.pig_id = p.id WHERE " . implode(' AND ', $vacWhere) . " ORDER BY v.date DESC LIMIT 50");
$vaccinesListStmt->execute($vacParams);
$recentVaccines = $vaccinesListStmt->fetchAll();

// Mortality Query
$mortWhere = ["1=1"];
$mortParams = [];
if (!empty($startDate)) { $mortWhere[] = "m.date >= ?"; $mortParams[] = $startDate; }
if (!empty($endDate)) { $mortWhere[] = "m.date <= ?"; $mortParams[] = $endDate; }

$mortalityStmt = $pdo->prepare("SELECT m.*, p.tag_no FROM mortality m JOIN pigs p ON m.pig_id = p.id WHERE " . implode(' AND ', $mortWhere) . " ORDER BY m.date DESC LIMIT 50");
$mortalityStmt->execute($mortParams);
$mortalityList = $mortalityStmt->fetchAll();

// Total pigs count for filtered view
$totalPigsCount = 0;
foreach ($stagesList as $s) { $totalPigsCount += $s['count']; }

include 'includes/header.php';
?>

<style>
@media print {
    .sidebar, .topbar, .btn, .mobile-toggle-btn, .sidebar-overlay, .report-filter-card {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    body {
        background: #fff !important;
    }
    .print-official-header {
        display: flex !important;
    }
    .card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
        margin-bottom: 15px !important;
    }
}
.print-official-header {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 15px;
    margin-bottom: 20px;
    border-bottom: 3px double #2E7D32;
}
.filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}
.filter-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
    flex: 1;
    min-width: 160px;
}
.filter-group label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--text-main);
}
.filter-group input, .filter-group select {
    padding: 8px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.9rem;
}
.toggle-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f0f4f1;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    cursor: pointer;
    border: 1px solid #c8e6c9;
}
.toggle-chip input[type="checkbox"] {
    accent-color: var(--primary-color);
}
</style>

<div class="dashboard-wrapper">
    
    <!-- Official Print Header (Visible only when printed or exported) -->
    <div class="print-official-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="images/logo.png" alt="MIGS Logo" style="height: 70px; width: 70px; border-radius: 50%;">
            <div>
                <h2 style="margin: 0; color: #2E7D32; font-size: 1.4rem;">Msantha Investments and General Suppliers (MIGS)</h2>
                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #555;">P.O. Box 250, Liwonde, Machinga, Malawi | Tel: +265 888899620 / +265 999899620</p>
                <p style="margin: 2px 0 0; font-size: 0.85rem; color: #555;">Email: icchipeta@gmail.com | Official Farm Performance & Audit Report</p>
            </div>
        </div>
        <div style="text-align: right; font-size: 0.85rem; color: #444;">
            <p style="margin: 0;"><strong>Date Generated:</strong> <?php echo date('d M Y, H:i'); ?></p>
            <p style="margin: 2px 0 0;"><strong>Generated By:</strong> <?php echo htmlspecialchars($_SESSION['user_fullname'] ?? 'System User'); ?></p>
            <p style="margin: 2px 0 0;"><strong>Scope:</strong> <?php echo ucfirst(htmlspecialchars($category)); ?> Report</p>
        </div>
    </div>

    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>Customizable Real Report Generator</h2>
            <p>Generate, customize, filter, and export real farm inventory, revenue, health, and breeding data.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button class="btn btn-outline" onclick="window.print()">🖨️ Print / Save PDF</button>
            <a href="export_csv.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">📊 Download Real CSV Report</a>
        </div>
    </div>

    <!-- Filter & Customization Panel -->
    <div class="card report-filter-card" style="margin-bottom: 25px; border-top: 4px solid var(--primary-color);">
        <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: var(--primary-color);">⚙️ Report Customization & Filter Controls</h3>
        <form method="GET" action="reports.php" id="reportFilterForm">
            <input type="hidden" name="customized" value="1">
            
            <div class="filter-row" style="margin-bottom: 15px;">
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                </div>
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                </div>
                <div class="filter-group">
                    <label>Report Category</label>
                    <select name="category">
                        <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Combined Reports</option>
                        <option value="inventory" <?php echo $category === 'inventory' ? 'selected' : ''; ?>>Pig Population & Inventory</option>
                        <option value="breeding" <?php echo $category === 'breeding' ? 'selected' : ''; ?>>Breeding & Pregnancy Audit</option>
                        <option value="sales" <?php echo $category === 'sales' ? 'selected' : ''; ?>>Sales & Revenue Ledger</option>
                        <option value="vaccination" <?php echo $category === 'vaccination' ? 'selected' : ''; ?>>Health & Vaccination Log</option>
                        <option value="mortality" <?php echo $category === 'mortality' ? 'selected' : ''; ?>>Mortality & Loss Audit</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Gender Filter</label>
                    <select name="sex">
                        <option value="all" <?php echo $sexFilter === 'all' ? 'selected' : ''; ?>>All Sexes</option>
                        <option value="Male" <?php echo $sexFilter === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $sexFilter === 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Life Stage</label>
                    <select name="stage">
                        <option value="all" <?php echo $stageFilter === 'all' ? 'selected' : ''; ?>>All Life Stages</option>
                        <option value="piglet" <?php echo $stageFilter === 'piglet' ? 'selected' : ''; ?>>Piglet</option>
                        <option value="weaner" <?php echo $stageFilter === 'weaner' ? 'selected' : ''; ?>>Weaner</option>
                        <option value="grower" <?php echo $stageFilter === 'grower' ? 'selected' : ''; ?>>Grower</option>
                        <option value="finisher" <?php echo $stageFilter === 'finisher' ? 'selected' : ''; ?>>Finisher</option>
                        <option value="sow" <?php echo $stageFilter === 'sow' ? 'selected' : ''; ?>>Sow</option>
                        <option value="boar" <?php echo $stageFilter === 'boar' ? 'selected' : ''; ?>>Boar</option>
                        <option value="adult" <?php echo $stageFilter === 'adult' ? 'selected' : ''; ?>>Adult</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px; color: var(--text-main);">Choose Sections to Include in Report:</label>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <label class="toggle-chip">
                        <input type="checkbox" name="inc_metrics" value="1" <?php echo $incMetrics ? 'checked' : ''; ?>> 📊 Summary KPIs
                    </label>
                    <label class="toggle-chip">
                        <input type="checkbox" name="inc_inventory" value="1" <?php echo $incInventory ? 'checked' : ''; ?>> 🐷 Population Breakdown
                    </label>
                    <label class="toggle-chip">
                        <input type="checkbox" name="inc_breeding" value="1" <?php echo $incBreeding ? 'checked' : ''; ?>> 🍼 Breeding & Farrowing
                    </label>
                    <label class="toggle-chip">
                        <input type="checkbox" name="inc_sales" value="1" <?php echo $incSales ? 'checked' : ''; ?>> 💰 Sales & Revenue
                    </label>
                    <label class="toggle-chip">
                        <input type="checkbox" name="inc_health" value="1" <?php echo $incHealth ? 'checked' : ''; ?>> 💉 Health & Vaccines
                    </label>
                    <label class="toggle-chip">
                        <input type="checkbox" name="inc_mortality" value="1" <?php echo $incMortality ? 'checked' : ''; ?>> ⚠️ Mortality Audit
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="reports.php" class="btn btn-outline" style="font-size: 0.9rem;">Reset Filters</a>
                <button type="submit" class="btn btn-primary" style="font-size: 0.9rem;">🔍 Generate Custom Report</button>
            </div>
        </form>
    </div>

    <!-- Active Filters Summary Banner -->
    <div style="background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 8px; padding: 10px 15px; margin-bottom: 20px; font-size: 0.9rem; color: #1b5e20; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <strong>Active Report Scope:</strong>
            <?php 
                $activeFilters = [];
                if ($startDate) $activeFilters[] = "From: " . htmlspecialchars($startDate);
                if ($endDate) $activeFilters[] = "To: " . htmlspecialchars($endDate);
                if ($sexFilter !== 'all') $activeFilters[] = "Sex: " . htmlspecialchars($sexFilter);
                if ($stageFilter !== 'all') $activeFilters[] = "Stage: " . htmlspecialchars($stageFilter);
                if ($category !== 'all') $activeFilters[] = "Category: " . ucfirst(htmlspecialchars($category));
                echo count($activeFilters) > 0 ? implode(' | ', $activeFilters) : 'Full All-Time Farm Records';
            ?>
        </div>
        <div>
            <strong>Generated Date:</strong> <?php echo date('Y-m-d H:i'); ?>
        </div>
    </div>

    <!-- 1. KPI Summary Cards -->
    <?php if ($incMetrics && ($category === 'all' || $category === 'inventory')): ?>
    <div class="kpi-container" style="margin-bottom: 20px;">
        <div class="kpi-card kpi-total">
            <div class="kpi-icon">🐖</div>
            <div class="kpi-details">
                <h3>Total Pigs Filtered</h3>
                <p class="kpi-value"><?php echo $totalPigsCount; ?></p>
            </div>
        </div>
        <div class="kpi-card kpi-pregnant">
            <div class="kpi-icon">🍼</div>
            <div class="kpi-details">
                <h3>Active Pregnant Sows</h3>
                <p class="kpi-value"><?php echo count($pregnantRecords); ?></p>
            </div>
        </div>
        <div class="kpi-card kpi-males">
            <div class="kpi-icon">💰</div>
            <div class="kpi-details">
                <h3>Revenue in Scope</h3>
                <p class="kpi-value">MWK <?php echo number_format($totalSales, 0); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. Population Breakdown Section -->
    <?php if ($incInventory && ($category === 'all' || $category === 'inventory')): ?>
    <div class="dashboard-content" style="grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
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
                    <?php foreach($stagesList as $s): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px; text-transform: capitalize;"><strong><?php echo htmlspecialchars($s['stage']); ?></strong></td>
                            <td style="padding: 8px;"><?php echo $s['count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($stagesList) === 0): ?><tr><td colspan="2" style="padding: 8px;">No active pigs found matching filter.</td></tr><?php endif; ?>
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
                    <?php foreach($gendersList as $g): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 8px;"><strong><?php echo htmlspecialchars($g['sex']); ?></strong></td>
                            <td style="padding: 8px;"><?php echo $g['count']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($gendersList) === 0): ?><tr><td colspan="2" style="padding: 8px;">No data matching filter.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. Active Pregnancy & Breeding Tracker -->
    <?php if ($incBreeding && ($category === 'all' || $category === 'breeding')): ?>
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="color: var(--primary-color);">Active Pregnant Sows & Expected Farrowing Schedule</h3>
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
                    <tr><td colspan="6" style="padding: 15px; text-align: center; color: var(--text-muted);">No currently active pregnant sows matching criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- 4. Sales & Financial Summary -->
    <?php if ($incSales && ($category === 'all' || $category === 'sales')): ?>
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="color: var(--primary-color);">Sales & Revenue Ledger</h3>
        <div style="padding: 15px 20px; background: #E8F5E9; border-radius: 8px; margin: 15px 0; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h4 style="color: var(--primary-color); margin: 0;">Total Revenue Generated (Filtered Period)</h4>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Sum of all recorded live pig and meat sales</p>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: var(--primary-color); margin: 0;">MWK <?php echo number_format($totalSales, 2); ?></p>
        </div>

        <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 8px;">Date</th>
                    <th>Type</th>
                    <th>Ref / Pig</th>
                    <th>Buyer Details</th>
                    <th>Amount (MWK)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($salesList as $sl): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px;"><?php echo htmlspecialchars($sl['date']); ?></td>
                        <td style="text-transform: capitalize;"><?php echo htmlspecialchars(str_replace('_', ' ', $sl['type'])); ?></td>
                        <td><?php echo htmlspecialchars($sl['reference_id'] ?: '-'); ?></td>
                        <td><?php echo htmlspecialchars($sl['buyer_info'] ?: 'Cash Customer'); ?></td>
                        <td><strong>MWK <?php echo number_format($sl['amount'], 2); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($salesList) === 0): ?>
                    <tr><td colspan="5" style="padding: 10px; color: var(--text-muted);">No sales records found matching filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- 5. Health & Vaccination Section -->
    <?php if ($incHealth && ($category === 'all' || $category === 'vaccination')): ?>
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="color: var(--primary-color);">Health & Vaccination Audit Logs</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 8px;">Date</th>
                    <th>Pig Tag</th>
                    <th>Vaccine / Treatment</th>
                    <th>Dose / Route</th>
                    <th>Administered By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentVaccines as $rv): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px;"><?php echo htmlspecialchars($rv['date']); ?></td>
                        <td><strong><?php echo htmlspecialchars($rv['tag_no']); ?></strong></td>
                        <td><?php echo htmlspecialchars($rv['vaccine']); ?></td>
                        <td><?php echo htmlspecialchars(($rv['dose'] ? $rv['dose'] . ' ' : '') . ($rv['route'] ?: '')); ?></td>
                        <td><?php echo htmlspecialchars($rv['administered_by'] ?: 'Staff'); ?></td>
                        <td><?php echo htmlspecialchars($rv['remarks'] ?: '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($recentVaccines) === 0): ?>
                    <tr><td colspan="6" style="padding: 10px; color: var(--text-muted);">No health records found matching filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- 6. Mortality Section -->
    <?php if ($incMortality && ($category === 'all' || $category === 'mortality')): ?>
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="color: var(--primary-color);">Mortality & Loss Audit Log</h3>
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
                    <tr><td colspan="4" style="padding: 10px; color: var(--text-muted);">No mortality records logged matching filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
