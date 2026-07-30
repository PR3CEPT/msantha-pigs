<?php
require_once 'db.php';
requireLogin();

$sexFilter = $_GET['sex'] ?? null;
$stageFilter = $_GET['stage'] ?? null;
$statusFilter = $_GET['status'] ?? 'active';

$query = "SELECT *, TIMESTAMPDIFF(MONTH, dob, CURDATE()) as age_months FROM pigs WHERE 1=1";
$params = [];

if ($statusFilter && $statusFilter !== 'all') {
    $query .= " AND status = ?";
    $params[] = $statusFilter;
}

if ($sexFilter) {
    $query .= " AND sex = ?";
    $params[] = $sexFilter;
}

if ($stageFilter) {
    $query .= " AND stage = ?";
    $params[] = $stageFilter;
}

$query .= " ORDER BY id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$pigs = $stmt->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>Pig Inventory Directory</h2>
            <p>Track ear tags, life stages, growth age, and current status.</p>
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
                    <option value="adult" <?php echo $stageFilter === 'adult' ? 'selected' : ''; ?>>Adult</option>
                    <option value="weaner" <?php echo $stageFilter === 'weaner' ? 'selected' : ''; ?>>Weaner</option>
                    <option value="piglet" <?php echo $stageFilter === 'piglet' ? 'selected' : ''; ?>>Piglet</option>
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
            <div style="margin-top: 18px;">
                <a href="pigs.php" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Reset Filters</a>
            </div>
        </form>
    </div>

    <div class="card" style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 1rem;">Ear Tag No</th>
                    <th>Sex</th>
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
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 1rem;"><strong><?php echo htmlspecialchars($pig['tag_no']); ?></strong></td>
                        <td><?php echo htmlspecialchars($pig['sex']); ?></td>
                        <td><?php echo htmlspecialchars($pig['breed'] ?? 'N/A'); ?></td>
                        <td style="text-transform: capitalize; font-weight: 600;"><?php echo htmlspecialchars($pig['stage']); ?></td>
                        <td><?php echo htmlspecialchars($pig['age_months']); ?> mos</td>
                        <td style="font-size: 0.85rem; color: var(--text-muted);">
                            S: <?php echo htmlspecialchars($pig['sire'] ?: 'N/A'); ?><br>
                            D: <?php echo htmlspecialchars($pig['dam'] ?: 'N/A'); ?>
                        </td>
                        <td>
                            <?php 
                                $bg = '#E8F5E9'; $fg = '#2E7D32';
                                if ($pig['status'] === 'sold') { $bg = '#FFF3E0'; $fg = '#E65100'; }
                                else if ($pig['status'] === 'dead') { $bg = '#FFEBEE'; $fg = '#C62828'; }
                                else if ($pig['status'] === 'archived') { $bg = '#ECEFF1'; $fg = '#37474F'; }
                            ?>
                            <span class="badge" style="padding: 4px 8px; border-radius: 4px; background: <?php echo $bg; ?>; color: <?php echo $fg; ?>;">
                                <?php echo ucfirst(htmlspecialchars($pig['status'])); ?>
                            </span>
                        </td>
                        <td>
                            <a href="pig_view.php?id=<?php echo $pig['id']; ?>" class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">Manage / View &rarr;</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($pigs) === 0): ?>
                    <tr><td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">No pig records match the selected filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
