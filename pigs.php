<?php
require_once 'db.php';
requireLogin();

$sexFilter       = $_GET['sex'] ?? null;
$stageFilter     = $_GET['stage'] ?? null;
$statusFilter    = $_GET['status'] ?? 'active';
$castratedFilter = $_GET['castrated'] ?? null;

// Standard SELECT * — stage is overridden in PHP via computePigStage()
$query  = "SELECT *, TIMESTAMPDIFF(MONTH, dob, CURDATE()) as age_months FROM pigs WHERE 1=1";
$params = [];

if ($statusFilter && $statusFilter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}
if ($sexFilter) {
    $query .= " AND sex = ?";
    $params[] = $sexFilter;
}
if ($castratedFilter !== null && $castratedFilter !== '') {
    $query .= " AND castrated = ?";
    $params[] = (int)$castratedFilter;
}
$query .= " ORDER BY id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pigs = $stmt->fetchAll();

// Override stored stage with live age-calculated stage,
// then apply stage filter in PHP (avoids duplicate column name in SQL)
foreach ($pigs as &$pig) {
    $pig['stage'] = computePigStage($pig['dob']);
}
unset($pig);

// Filter by stage in PHP if requested
if ($stageFilter) {
    $pigs = array_values(array_filter($pigs, fn($p) => $p['stage'] === $stageFilter));
}


include 'includes/header.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>Pig Inventory Directory</h2>
            <p>Track ear tags, life stages, male castration status, growth age, and current status.</p>
        </div>
        <a href="pig_form.php" class="btn btn-primary">+ Register New Pig</a>
    </div>

    <!-- Filter Toolbar -->
    <div class="card" style="margin-bottom: 20px; padding: 1rem 1.5rem;">
        <form method="GET" action="pigs.php" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <div>
                <label style="font-size: 0.85rem; margin-bottom: 2px;">Filter Status:</label>
                <select name="status" class="form-control" onchange="this.form.submit()" style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active Inventory</option>
                    <option value="sold" <?php echo $statusFilter === 'sold' ? 'selected' : ''; ?>>Sold</option>
                    <option value="dead" <?php echo $statusFilter === 'dead' ? 'selected' : ''; ?>>Deceased / Mortality</option>
                    <option value="archived" <?php echo $statusFilter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                </select>
            </div>
            <div>
                <label style="font-size: 0.85rem; margin-bottom: 2px;">Filter Stage:</label>
                <select name="stage" class="form-control" onchange="this.form.submit()" style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                    <option value="">All Stages</option>
                    <option value="piglet"   <?php echo $stageFilter === 'piglet'   ? 'selected' : ''; ?>>🐽 Piglet (0-4 weeks)</option>
                    <option value="weaner"   <?php echo $stageFilter === 'weaner'   ? 'selected' : ''; ?>>🐖 Weaner (4-12 weeks)</option>
                    <option value="grower"   <?php echo $stageFilter === 'grower'   ? 'selected' : ''; ?>>📈 Grower (3-5 months)</option>
                    <option value="finisher" <?php echo $stageFilter === 'finisher' ? 'selected' : ''; ?>>🏁 Finisher (5-7 months)</option>
                    <option value="adult"    <?php echo $stageFilter === 'adult'    ? 'selected' : ''; ?>>🐗 Adult / Breeder (7+ months)</option>
                </select>
            </div>
            <div>
                <label style="font-size: 0.85rem; margin-bottom: 2px;">Filter Sex:</label>
                <select name="sex" class="form-control" onchange="this.form.submit()" style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                    <option value="">All Sexes</option>
                    <option value="Male" <?php echo $sexFilter === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $sexFilter === 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
            <div>
                <label style="font-size: 0.85rem; margin-bottom: 2px;">Male Type:</label>
                <select name="castrated" class="form-control" onchange="this.form.submit()" style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                    <option value="">All Males</option>
                    <option value="0" <?php echo $castratedFilter === '0' ? 'selected' : ''; ?>>🐗 Intact Boars</option>
                    <option value="1" <?php echo $castratedFilter === '1' ? 'selected' : ''; ?>>✂️ Castrated Barrows</option>
                </select>
            </div>
            <div style="margin-top: 18px;">
                <a href="pigs.php" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Reset Filters</a>
            </div>
        </form>
    </div>

    <div class="table-wrapper" style="border: none; box-shadow: none;">
        <table class="data-table striped">
            <thead>
                <tr>
                    <th>Ear Tag No</th>
                    <th>Sex &amp; Type</th>
                    <th>Breed</th>
                    <th>Stage</th>
                    <th>Age</th>
                    <th>Sire / Dam</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pigs as $pig): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($pig['tag_no']); ?></strong>
                            <?php if (($pig['source'] ?? '') === 'External Purchase'): ?>
                                <span class="tbl-sub"><span class="tbl-badge blue">External</span></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($pig['sex']); ?></strong>
                            <?php if ($pig['sex'] === 'Male'): ?>
                                <span class="tbl-sub">
                                <?php if (!empty($pig['castrated'])): ?>
                                    <span class="tbl-badge green">✂️ Castrated</span>
                                <?php else: ?>
                                    <span class="tbl-badge orange">🐗 Boar</span>
                                <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($pig['breed'] ?? 'N/A'); ?></td>
                        <td style="text-transform: capitalize; font-weight: 600;"><?php echo htmlspecialchars($pig['stage']); ?></td>
                        <td><?php echo htmlspecialchars($pig['age_months']); ?> mos</td>
                        <td>
                            <span class="tbl-sub">S: <?php echo htmlspecialchars($pig['sire'] ?: 'N/A'); ?></span>
                            <span class="tbl-sub">D: <?php echo htmlspecialchars($pig['dam'] ?: 'N/A'); ?></span>
                        </td>
                        <td>
                            <?php 
                                $sBadge = 'green';
                                if ($pig['status'] === 'sold') $sBadge = 'orange';
                                else if ($pig['status'] === 'dead') $sBadge = 'red';
                                else if ($pig['status'] === 'archived') $sBadge = 'grey';
                            ?>
                            <span class="tbl-badge <?php echo $sBadge; ?>"><?php echo ucfirst(htmlspecialchars($pig['status'])); ?></span>
                        </td>
                        <td>
                            <a href="pig_view.php?id=<?php echo $pig['id']; ?>" class="btn btn-outline" style="padding: 0.35rem 0.8rem; font-size: 0.8rem;">Manage &rarr;</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($pigs) === 0): ?>
                    <tr class="tbl-empty"><td colspan="8">No pig records match the selected filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
