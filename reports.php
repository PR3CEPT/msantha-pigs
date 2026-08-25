<?php
require_once 'db.php';
requireLogin();

// Filter parameters
$startDate   = $_GET['start_date'] ?? '';
$endDate     = $_GET['end_date'] ?? '';
$category    = $_GET['category'] ?? 'all';
$sexFilter   = $_GET['sex'] ?? 'all';
$stageFilter = $_GET['stage'] ?? 'all';
$sourceFilter= $_GET['source'] ?? 'all';

// Include all sections based on selected category
$incMetrics   = true;
$incInventory = true;
$incExternal  = true;
$incBreeding  = true;
$incSales     = true;
$incHealth    = true;
$incMortality = true;

// Build Pig Inventory Queries
$pigWhere = ["1=1"];
$pigParams = [];
if ($sexFilter !== 'all') { $pigWhere[] = "sex = ?"; $pigParams[] = $sexFilter; }
if ($stageFilter !== 'all') { $pigWhere[] = "stage = ?"; $pigParams[] = $stageFilter; }
if ($sourceFilter !== 'all') { $pigWhere[] = "source = ?"; $pigParams[] = $sourceFilter; }

// Auto-stage population breakdown using $PIG_STAGE_SQL
$stages = $pdo->prepare("SELECT ($PIG_STAGE_SQL) as stage, COUNT(*) as count FROM pigs WHERE status = 'active' AND " . implode(' AND ', $pigWhere) . " GROUP BY ($PIG_STAGE_SQL) ORDER BY FIELD(($PIG_STAGE_SQL),'piglet','weaner','grower','finisher','adult')");
$stages->execute($pigParams);
$stagesList = $stages->fetchAll();

$genders = $pdo->prepare("SELECT sex, COUNT(*) as count FROM pigs WHERE status = 'active' AND " . implode(' AND ', $pigWhere) . " GROUP BY sex");
$genders->execute($pigParams);
$gendersList = $genders->fetchAll();

// External Pigs Query (Purchased Livestock)
$extWhere = ["source = 'External Purchase'"];
$extParams = [];
if (!empty($startDate)) { $extWhere[] = "dob >= ?"; $extParams[] = $startDate; }
if (!empty($endDate)) { $extWhere[] = "dob <= ?"; $extParams[] = $endDate; }
if ($sexFilter !== 'all') { $extWhere[] = "sex = ?"; $extParams[] = $sexFilter; }
if ($stageFilter !== 'all') { $extWhere[] = "stage = ?"; $extParams[] = $stageFilter; }

$externalPigsStmt = $pdo->prepare("SELECT *, TIMESTAMPDIFF(MONTH, dob, CURDATE()) as age_months FROM pigs WHERE " . implode(' AND ', $extWhere) . " ORDER BY id DESC LIMIT 100");
$externalPigsStmt->execute($extParams);
$externalPigsList = $externalPigsStmt->fetchAll();

// Breeding Query
// Breeding Query (All breeding, pregnancy, and weaning records in scope)
$breedWhere = ["1=1"];
$breedParams = [];
if (!empty($startDate)) { $breedWhere[] = "b.date_of_service >= ?"; $breedParams[] = $startDate; }
if (!empty($endDate)) { $breedWhere[] = "b.date_of_service <= ?"; $breedParams[] = $endDate; }
if ($sexFilter !== 'all') { $breedWhere[] = "p.sex = ?"; $breedParams[] = $sexFilter; }
if ($sourceFilter !== 'all') { $breedWhere[] = "p.source = ?"; $breedParams[] = $sourceFilter; }

$pregnantStmt = $pdo->prepare("SELECT b.*, p.tag_no, p.breed FROM breeding_records b JOIN pigs p ON b.pig_id = p.id WHERE " . implode(' AND ', $breedWhere) . " ORDER BY b.expected_farrowing ASC");
$pregnantStmt->execute($breedParams);
$pregnantRecords = $pregnantStmt->fetchAll();

// Male Castration Breakdown Query
$maleCastratedStmt = $pdo->prepare("SELECT castrated, COUNT(*) as count FROM pigs WHERE sex = 'Male' AND status = 'active' AND " . implode(' AND ', $pigWhere) . " GROUP BY castrated");
$maleCastratedStmt->execute($pigParams);
$maleCastratedList = $maleCastratedStmt->fetchAll();

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
if ($sourceFilter !== 'all') { $vacWhere[] = "p.source = ?"; $vacParams[] = $sourceFilter; }

$vaccinesListStmt = $pdo->prepare("SELECT v.*, p.tag_no FROM vaccination_records v JOIN pigs p ON v.pig_id = p.id WHERE " . implode(' AND ', $vacWhere) . " ORDER BY v.date DESC LIMIT 50");
$vaccinesListStmt->execute($vacParams);
$recentVaccines = $vaccinesListStmt->fetchAll();

// Mortality Query
$mortWhere = ["1=1"];
$mortParams = [];
if (!empty($startDate)) { $mortWhere[] = "m.date >= ?"; $mortParams[] = $startDate; }
if (!empty($endDate)) { $mortWhere[] = "m.date <= ?"; $mortParams[] = $endDate; }
if ($sourceFilter !== 'all') { $mortWhere[] = "p.source = ?"; $mortParams[] = $sourceFilter; }

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
    .print-official-header, .print-approval-footer {
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
.print-approval-footer {
    display: none;
    justify-content: space-between;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #ccc;
    font-size: 0.85rem;
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
                        <option value="inventory" <?php echo $category === 'inventory' ? 'selected' : ''; ?>>Pig Population &amp; Inventory</option>
                        <option value="external" <?php echo $category === 'external' ? 'selected' : ''; ?>>External / Purchased Pigs</option>
                        <option value="breeding" <?php echo $category === 'breeding' ? 'selected' : ''; ?>>Breeding &amp; Pregnancy Audit</option>
                        <option value="sales" <?php echo $category === 'sales' ? 'selected' : ''; ?>>Sales &amp; Revenue Ledger</option>
                        <option value="vaccination" <?php echo $category === 'vaccination' ? 'selected' : ''; ?>>Health &amp; Vaccination Log</option>
                        <option value="mortality" <?php echo $category === 'mortality' ? 'selected' : ''; ?>>Mortality &amp; Loss Audit</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Source / Origin</label>
                    <select name="source">
                        <option value="all" <?php echo $sourceFilter === 'all' ? 'selected' : ''; ?>>All Origins</option>
                        <option value="Born on Farm" <?php echo $sourceFilter === 'Born on Farm' ? 'selected' : ''; ?>>Born on Farm</option>
                        <option value="External Purchase" <?php echo $sourceFilter === 'External Purchase' ? 'selected' : ''; ?>>External Purchase / Bought</option>
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

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="reports.php" class="btn btn-outline" style="font-size: 0.9rem;">Reset Filters</a>
                <button type="submit" class="btn btn-primary" style="font-size: 0.9rem;">🔍 Generate Report</button>
            </div>
        </form>
    </div>

    <!-- Active Filters Summary Banner -->
    <div class="active-filters-bar" style="background: #e8f5e9; border: 1px solid #c8e6c9; border-radius: 8px; padding: 10px 15px; margin-bottom: 20px; font-size: 0.9rem; color: #1b5e20; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
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
    <div class="dashboard-content reports-inventory-grid" style="margin-bottom: 20px;">
        <!-- Population by Stage -->
        <div class="card">
            <h3>📊 Population by Life Stage</h3>
            <div class="table-wrapper" style="border:none; box-shadow:none; margin-top:12px;">
            <table class="data-table striped middle">
                <thead>
                    <tr>
                        <th>Stage</th>
                        <th>Active Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($stagesList as $s): ?>
                        <tr>
                            <td style="text-transform: capitalize;"><strong><?php echo htmlspecialchars($s['stage']); ?></strong></td>
                            <td><span class="tbl-badge blue"><?php echo $s['count']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($stagesList) === 0): ?>
                        <tr class="tbl-empty"><td colspan="2">No active pigs found matching filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Population by Gender & Castration -->
        <div class="card">
            <h3>♂️♀️ Sex &amp; Male Castration Breakdown</h3>
            <div class="table-wrapper" style="border:none; box-shadow:none; margin-top:12px;">
            <table class="data-table striped middle">
                <thead>
                    <tr>
                        <th>Category / Gender</th>
                        <th>Active Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($gendersList as $g): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($g['sex']); ?>s</strong></td>
                            <td><span class="tbl-badge <?php echo $g['sex'] === 'Male' ? 'blue' : 'purple'; ?>"><?php echo $g['count']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach($maleCastratedList as $mc): ?>
                        <tr style="background: #fafafa;">
                            <td style="padding-left: 24px; font-size: 0.88rem;">
                                <?php echo !empty($mc['castrated']) ? '✂️ Castrated Males (Barrows)' : '🐗 Intact Males (Boars)'; ?>
                            </td>
                            <td><span class="tbl-badge <?php echo !empty($mc['castrated']) ? 'green' : 'orange'; ?>"><?php echo $mc['count']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(count($gendersList) === 0): ?>
                        <tr class="tbl-empty"><td colspan="2">No data matching filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2.5 External / Purchased Pigs Section -->
    <?php if ($incExternal && ($category === 'all' || $category === 'inventory' || $category === 'external')): ?>
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="color: var(--primary-color);">🛒 External / Purchased Pigs Acquisition Audit</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">Audit ledger of pigs acquired from external sources/suppliers.</p>
        <div class="table-wrapper">
        <table class="data-table striped">
            <thead>
                <tr>
                    <th>Ear Tag No</th>
                    <th>Sex</th>
                    <th>Stage</th>
                    <th>Breed</th>
                    <th>Age</th>
                    <th>Sire / Dam</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($externalPigsList as $ext): ?>
                    <tr>
                        <td>
                            <strong><a href="pig_view.php?id=<?php echo $ext['id']; ?>" style="color:var(--primary-color);text-decoration:none;"><?php echo htmlspecialchars($ext['tag_no']); ?></a></strong>
                            <span class="tbl-badge blue" style="font-size:0.7rem; margin-left:5px;">External</span>
                        </td>
                        <td><?php echo htmlspecialchars($ext['sex']); ?></td>
                        <td style="text-transform: capitalize; font-weight: 600;"><?php echo htmlspecialchars($ext['stage']); ?></td>
                        <td><?php echo htmlspecialchars($ext['breed'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($ext['age_months']); ?> mos</td>
                        <td>
                            <span class="tbl-sub">S: <?php echo htmlspecialchars($ext['sire'] ?: 'N/A'); ?></span>
                            <span class="tbl-sub">D: <?php echo htmlspecialchars($ext['dam'] ?: 'N/A'); ?></span>
                        </td>
                        <td><span class="tbl-badge green"><?php echo ucfirst(htmlspecialchars($ext['status'])); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($externalPigsList) === 0): ?>
                    <tr class="tbl-empty"><td colspan="7">No externally purchased pigs found matching criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. Active Pregnancy, Gestation & Breeding Tracker -->
    <?php if ($incBreeding && ($category === 'all' || $category === 'breeding')): ?>
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="color: var(--primary-color);">🍼 Breeding, Gestation &amp; Farrowing Performance Audit</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">Tracks mating dates, estimated birth dates (+114 days), countdowns, farrowing outcomes, and weaner performance.</p>
        <div class="table-wrapper">
        <table class="data-table striped">
            <thead>
                <tr>
                    <th>Sow Tag</th>
                    <th>Mating Date</th>
                    <th>Sire</th>
                    <th>Est. Farrowing</th>
                    <th>Farrowing Outcome</th>
                    <th>Weaning</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pregnantRecords as $pr): ?>
                    <?php
                        $expectedDt = new DateTime($pr['expected_farrowing']);
                        $today = new DateTime('today');
                        $daysDiff = (int)$today->diff($expectedDt)->format('%r%a');
                        $cdBadge = '';
                        if ($pr['status'] === 'pregnant') {
                            if ($daysDiff > 0) {
                                $cdBadge = '<span class="tbl-badge due">⌛ Due in ' . $daysDiff . 'd</span>';
                            } elseif ($daysDiff === 0) {
                                $cdBadge = '<span class="tbl-badge today">🚨 Due Today!</span>';
                            } else {
                                $absDays = abs($daysDiff);
                                $cdBadge = '<span class="tbl-badge alarm">⚠️ Overdue ' . $absDays . 'd</span>';
                            }
                        }
                        $sBadgeClass = match($pr['status']) {
                            'pregnant' => 'orange', 'farrowed' => 'blue', 'weaned' => 'green', default => 'grey'
                        };
                        $wRate = null;
                        if (!empty($pr['born_alive']) && $pr['born_alive'] > 0 && $pr['weaned_count'] !== null) {
                            $wRate = round(($pr['weaned_count'] / $pr['born_alive']) * 100, 1);
                        }
                    ?>
                    <tr>
                        <td>
                            <strong><a href="pig_view.php?id=<?php echo $pr['pig_id']; ?>" style="color:var(--primary-color);text-decoration:none;"><?php echo htmlspecialchars($pr['tag_no']); ?></a></strong>
                            <span class="tbl-sub"><?php echo htmlspecialchars($pr['breed']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($pr['date_of_service']); ?></td>
                        <td><strong><?php echo htmlspecialchars($pr['sire_no'] ?: 'N/A'); ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($pr['expected_farrowing']); ?></strong></td>
                        <td>
                            <?php if ($pr['total_born'] !== null): ?>
                                <strong><?php echo htmlspecialchars($pr['total_born']); ?> born</strong><br>
                                <span class="stat-pill alive">✅ <?php echo htmlspecialchars($pr['born_alive']); ?> alive</span>
                                <span class="stat-pill dead">💀 <?php echo htmlspecialchars($pr['stillborn']); ?> still</span>
                            <?php else: ?>
                                <span style="color:var(--text-muted); font-style:italic;">Pending birth</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($pr['weaned_count'] !== null): ?>
                                <strong><?php echo htmlspecialchars($pr['weaned_count']); ?> weaners</strong>
                                <?php if ($wRate !== null): ?>
                                    <span class="stat-pill blue"><?php echo $wRate; ?>%</span>
                                <?php endif; ?>
                                <span class="tbl-sub">Avg: <?php echo htmlspecialchars($pr['avg_weaning_wt'] ?: 'N/A'); ?> kg</span>
                            <?php else: ?>
                                <span style="color:var(--text-muted); font-style:italic;">Not weaned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="tbl-badge <?php echo $sBadgeClass; ?>"><?php echo ucfirst(htmlspecialchars($pr['status'])); ?></span>
                            <?php if ($cdBadge): ?><div style="margin-top:4px;"><?php echo $cdBadge; ?></div><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($pregnantRecords) === 0): ?>
                    <tr class="tbl-empty"><td colspan="7">No breeding or gestation records matching criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 4. Sales & Financial Summary -->
    <?php if ($incSales && ($category === 'all' || $category === 'sales')): ?>
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="color: var(--primary-color);">💰 Sales &amp; Revenue Ledger</h3>
        <div class="report-revenue-banner" style="padding: 15px 20px; background: #E8F5E9; border-radius: 8px; margin: 15px 0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h4 style="color: var(--primary-color); margin: 0;">Total Revenue Generated (Filtered Period)</h4>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Sum of all recorded live pig and meat sales</p>
            </div>
            <p style="font-size: 2rem; font-weight: bold; color: var(--primary-color); margin: 0;">MWK <?php echo number_format($totalSales, 2); ?></p>
        </div>
        <div class="table-wrapper">
        <table class="data-table striped middle">
            <thead>
                <tr>
                    <th style="width:110px;">Date</th>
                    <th>Type</th>
                    <th>Ref / Pig</th>
                    <th>Buyer Details</th>
                    <th>Amount (MWK)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($salesList as $sl): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sl['date']); ?></td>
                        <td style="text-transform: capitalize;"><?php echo htmlspecialchars(str_replace('_', ' ', $sl['type'])); ?></td>
                        <td><?php echo htmlspecialchars($sl['reference_id'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($sl['buyer_info'] ?: 'Cash Customer'); ?></td>
                        <td><span class="tbl-badge green">MWK <?php echo number_format($sl['amount'], 2); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($salesList) === 0): ?>
                    <tr class="tbl-empty"><td colspan="5">No sales records found matching filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 5. Health & Vaccination Section -->
    <?php if ($incHealth && ($category === 'all' || $category === 'vaccination')): ?>
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="color: var(--primary-color);">💉 Health &amp; Vaccination Audit Logs</h3>
        <div class="table-wrapper" style="margin-top:12px;">
        <table class="data-table striped middle">
            <thead>
                <tr>
                    <th style="width:110px;">Date</th>
                    <th style="width:100px;">Pig Tag</th>
                    <th>Vaccine / Treatment</th>
                    <th>Dose / Route</th>
                    <th>Administered By</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recentVaccines as $rv): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rv['date']); ?></td>
                        <td><strong><?php echo htmlspecialchars($rv['tag_no']); ?></strong></td>
                        <td><?php echo htmlspecialchars($rv['vaccine']); ?></td>
                        <td><span class="tbl-badge green"><?php echo htmlspecialchars(($rv['dose'] ? $rv['dose'] . ' ' : '') . ($rv['route'] ?: '')); ?></span></td>
                        <td><?php echo htmlspecialchars($rv['administered_by'] ?: 'Staff'); ?></td>
                        <td><?php echo htmlspecialchars($rv['remarks'] ?: '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($recentVaccines) === 0): ?>
                    <tr class="tbl-empty"><td colspan="6">No health records found matching filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 6. Mortality Section -->
    <?php if ($incMortality && ($category === 'all' || $category === 'mortality')): ?>
    <div class="card" style="margin-bottom: 20px;">
        <h3 style="color: var(--primary-color);">💀 Mortality &amp; Loss Audit Log</h3>
        <div class="table-wrapper" style="margin-top:12px;">
        <table class="data-table striped middle">
            <thead>
                <tr>
                    <th style="width:110px;">Date</th>
                    <th style="width:110px;">Pig Tag</th>
                    <th>Reported Cause of Death</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($mortalityList as $ml): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ml['date']); ?></td>
                        <td><strong><?php echo htmlspecialchars($ml['tag_no']); ?></strong></td>
                        <td><span class="tbl-badge red"><?php echo htmlspecialchars($ml['cause'] ?: 'Unspecified'); ?></span></td>
                        <td><?php echo htmlspecialchars($ml['remarks'] ?: '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($mortalityList) === 0): ?>
                    <tr class="tbl-empty"><td colspan="4">No mortality records logged matching filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Official Print Approval Signature Block -->
    <div class="print-approval-footer">
        <div>
            <p><strong>Report Prepared / Audited By:</strong> ___________________________</p>
            <p>Name &amp; Designation: <?php echo htmlspecialchars($_SESSION['user_fullname'] ?? 'Staff'); ?></p>
        </div>
        <div style="text-align: right;">
            <p><strong>Farm Manager Verification &amp; Stamp:</strong> ___________________________</p>
            <p>Date: ____ / ____ / 20___</p>
        </div>
    </div>

</div>

<?php include 'includes/footer.php'; ?>
