<?php
require_once 'db.php';
requireLogin();

$pigId = $_GET['id'] ?? null;
if (!$pigId) {
    header("Location: pigs.php");
    exit();
}

$msg = null;
$error = null;

// Fetch pig data
$stmt = $pdo->prepare("SELECT *, 
    TIMESTAMPDIFF(MONTH, dob, CURDATE()) as age_months, 
    DATEDIFF(CURDATE(), dob) as age_days_calc
    FROM pigs WHERE id = ?");
$stmt->execute([$pigId]);
$pig = $stmt->fetch();

if (!$pig) {
    echo '<div style="padding:2rem; color:red;">Pig not found.</div>';
    include 'includes/footer.php';
    exit();
}

// Override stored stage with live age-calculated stage
$pig['stage'] = computePigStage($pig['dob']);

// 1. Handle Status Update with PDO Transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE pigs SET status = ? WHERE id = ?");
        $stmt->execute([$status, $pigId]);

        if ($status === 'dead') {
            $cause = $_POST['cause'] ?? 'Unspecified';
            $remarks = $_POST['remarks'] ?? 'Status updated to dead';
            $pdo->prepare("INSERT INTO mortality (pig_id, date, cause, remarks) VALUES (?, ?, ?, ?)")
                ->execute([$pigId, $date, $cause, $remarks]);
            logActivity($pdo, 'pig_status_changed', "Updated pig #{$pig['tag_no']} status to Deceased (Cause: $cause)");
        } else if ($status === 'sold') {
            $price = !empty($_POST['price']) ? (float)$_POST['price'] : 0;
            $buyer = trim($_POST['buyer'] ?? 'Cash Customer');
            $remarks = trim($_POST['remarks'] ?? 'Live pig sale');
            $pdo->prepare("INSERT INTO sales (type, reference_id, date, amount, buyer_info, remarks) VALUES ('live_pig', ?, ?, ?, ?, ?)")
                ->execute([$pig['tag_no'], $date, $price, $buyer ?: 'Cash Customer', $remarks]);
            logActivity($pdo, 'pig_status_changed', "Updated pig #{$pig['tag_no']} status to Sold (Live Pig) (Buyer: $buyer, Amount: MWK " . number_format($price, 2) . ")");
        } else if ($status === 'sold_meat') {
            $meatWeight = !empty($_POST['meat_weight']) ? (float)$_POST['meat_weight'] : null;
            $price = !empty($_POST['meat_price']) ? (float)$_POST['meat_price'] : 0;
            $buyer = trim($_POST['meat_buyer'] ?? 'Cash Customer');
            $remarks = trim($_POST['meat_remarks'] ?? 'Pork / Meat sale');
            $pdo->prepare("INSERT INTO sales (type, reference_id, weight, date, amount, buyer_info, remarks) VALUES ('meat_sale', ?, ?, ?, ?, ?, ?)")
                ->execute([$pig['tag_no'], $meatWeight, $date, $price, $buyer ?: 'Cash Customer', $remarks]);
            $weightInfo = $meatWeight ? " (Weight: {$meatWeight} kg)" : "";
            logActivity($pdo, 'pig_status_changed', "Updated pig #{$pig['tag_no']} status to Sold for Meat$weightInfo (Buyer: $buyer, Amount: MWK " . number_format($price, 2) . ")");
        } else {
            logActivity($pdo, 'pig_status_changed', "Updated pig #{$pig['tag_no']} status to " . ucfirst(str_replace('_', ' ', $status)));
        }

        $pdo->commit();
        $msg = "Pig status updated successfully!";
        // Refresh pig info
        $stmt->execute([$pigId]);
        $pig = $stmt->fetch();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Failed to update status: " . $e->getMessage();
    }
}

// 2. Handle Add Growth Record with PDO Transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_growth'])) {
    $date = $_POST['growth_date'] ?? date('Y-m-d');
    $weight = $_POST['weight'] ?? null;
    $remarks = $_POST['growth_remarks'] ?? '';
    
    // Auto-calculate age in days at date of measurement
    $dobDate = new DateTime($pig['dob']);
    $measDate = new DateTime($date);
    $ageDays = $dobDate->diff($measDate)->days;

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO growth_records (pig_id, date, weight, age_days, remarks) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$pigId, $date, $weight, $ageDays, $remarks]);
        logActivity($pdo, 'growth_added', "Logged weight record of {$weight} kg for pig #{$pig['tag_no']} (Age: {$ageDays} days)");
        $pdo->commit();
        $msg = "Weight record logged successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Failed to save weight record: " . $e->getMessage();
    }
}

// 3. Handle Add Vaccination / Health Record with PDO Transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_vaccination'])) {
    $date = $_POST['vac_date'] ?? date('Y-m-d');
    $vaccine = $_POST['vaccine'] ?? '';
    $dose = $_POST['dose'] ?? '';
    $route = $_POST['route'] ?? 'Intramuscular';
    $administered_by = $_POST['administered_by'] ?? $_SESSION['user_fullname'];
    $remarks = $_POST['vac_remarks'] ?? '';

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO vaccination_records (pig_id, date, vaccine, dose, route, administered_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$pigId, $date, $vaccine, $dose, $route, $administered_by, $remarks]);
        logActivity($pdo, 'vaccination_added', "Logged health/vaccine '{$vaccine}' (Dose: {$dose}, Route: {$route}) for pig #{$pig['tag_no']}");
        $pdo->commit();
        $msg = "Health/Vaccination record logged successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Failed to save vaccination record: " . $e->getMessage();
    }
}

// 4. Handle Add Breeding Record with PDO Transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_breeding'])) {
    $date_of_service = $_POST['date_of_service'] ?? date('Y-m-d');
    $sire_no = $_POST['sire_no'] ?? '';
    $expected_farrowing = $_POST['expected_farrowing'] ?? '';
    $actual_farrowing = !empty($_POST['actual_farrowing']) ? $_POST['actual_farrowing'] : null;
    $total_born = $_POST['total_born'] !== '' ? (int)$_POST['total_born'] : null;
    $born_alive = $_POST['born_alive'] !== '' ? (int)$_POST['born_alive'] : null;
    $stillborn = $_POST['stillborn'] !== '' ? (int)$_POST['stillborn'] : null;
    $avg_weaning_wt = $_POST['avg_weaning_wt'] !== '' ? (float)$_POST['avg_weaning_wt'] : null;
    $breeding_status = $_POST['breeding_status'] ?? 'pregnant';

    // Auto-calculate expected farrowing (114 days after service) if left blank
    if (empty($expected_farrowing) && !empty($date_of_service)) {
        $expectedDate = new DateTime($date_of_service);
        $expectedDate->modify('+114 days');
        $expected_farrowing = $expectedDate->format('Y-m-d');
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO breeding_records (pig_id, date_of_service, sire_no, expected_farrowing, actual_farrowing, total_born, born_alive, stillborn, avg_weaning_wt, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$pigId, $date_of_service, $sire_no, $expected_farrowing, $actual_farrowing, $total_born, $born_alive, $stillborn, $avg_weaning_wt, $breeding_status]);
        logActivity($pdo, 'breeding_added', "Logged breeding service for sow #{$pig['tag_no']} (Sire: {$sire_no}, Expected Farrowing: {$expected_farrowing})");
        $pdo->commit();
        $msg = "Breeding record logged successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Failed to save breeding record: " . $e->getMessage();
    }
}

// 4b. Handle Record Farrowing Outcome
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_farrowing'])) {
    $breedingId = $_POST['breeding_id'] ?? null;
    $actual_farrowing = $_POST['actual_farrowing'] ?? date('Y-m-d');
    $born_alive = (int)($_POST['born_alive'] ?? 0);
    $stillborn = (int)($_POST['stillborn'] ?? 0);
    $total_born = $born_alive + $stillborn;

    if ($breedingId) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE breeding_records SET actual_farrowing = ?, total_born = ?, born_alive = ?, stillborn = ?, status = 'farrowed' WHERE id = ? AND pig_id = ?");
            $stmt->execute([$actual_farrowing, $total_born, $born_alive, $stillborn, $breedingId, $pigId]);
            logActivity($pdo, 'farrowing_recorded', "Recorded farrowing outcome for sow #{$pig['tag_no']} ($born_alive alive, $stillborn stillborn, Total: $total_born)");
            $pdo->commit();
            $msg = "Farrowing outcome recorded successfully!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to record farrowing: " . $e->getMessage();
        }
    }
}

// 4c. Handle Log Weaning Event
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_weaning'])) {
    $breedingId = $_POST['breeding_id'] ?? null;
    $weaning_date = $_POST['weaning_date'] ?? date('Y-m-d');
    $weaned_count = (int)($_POST['weaned_count'] ?? 0);
    $avg_weaning_wt = !empty($_POST['avg_weaning_wt']) ? (float)$_POST['avg_weaning_wt'] : null;

    if ($breedingId) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE breeding_records SET weaning_date = ?, weaned_count = ?, avg_weaning_wt = ?, status = 'weaned' WHERE id = ? AND pig_id = ?");
            $stmt->execute([$weaning_date, $weaned_count, $avg_weaning_wt, $breedingId, $pigId]);
            logActivity($pdo, 'weaning_recorded', "Logged weaning event for sow #{$pig['tag_no']} ($weaned_count piglets weaned, avg weight {$avg_weaning_wt} kg)");
            $pdo->commit();
            $msg = "Weaning event logged successfully!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to log weaning event: " . $e->getMessage();
        }
    }
}

// 5. Handle Edit Pig Details & Update Ear Tag with PDO Transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_pig_details'])) {
    $newTag = trim($_POST['tag_no'] ?? '');
    $sex = $_POST['sex'] ?? 'Male';
    $breed = trim($_POST['breed'] ?? '');
    $dob = $_POST['dob'] ?? date('Y-m-d');
    $sire = trim($_POST['sire'] ?? '');
    $dam = trim($_POST['dam'] ?? '');
    $stage = $_POST['stage'] ?? 'adult';
    $source = $_POST['source'] ?? 'Born on Farm';
    $castrated = ($sex === 'Male' && isset($_POST['castrated']) && $_POST['castrated'] === '1') ? 1 : 0;
    $castration_date = ($castrated === 1 && !empty($_POST['castration_date'])) ? $_POST['castration_date'] : null;

    // VALIDATION: Castration date cannot be before Date of Birth
    if ($castrated === 1 && !empty($castration_date)) {
        if ($castration_date < $dob) {
            $error = "Validation Error: Castration date (" . htmlspecialchars($castration_date) . ") cannot be earlier than Date of Birth (" . htmlspecialchars($dob) . ").";
        }
    }

    $purchasePrice = ($source === 'External Purchase' && !empty($_POST['purchase_price'])) ? (float)$_POST['purchase_price'] : null;
    $vendor = ($source === 'External Purchase') ? trim($_POST['vendor'] ?? '') : null;

    if (empty($newTag)) {
        $error = "Ear Tag No cannot be empty.";
    } else if (!$error) {
        // Check uniqueness excluding current pig
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM pigs WHERE tag_no = ? AND id != ?");
        $checkStmt->execute([$newTag, $pigId]);
        if ($checkStmt->fetchColumn() > 0) {
            $error = "Ear Tag No '" . htmlspecialchars($newTag) . "' is already assigned to another pig.";
        } else {
            try {
                $pdo->beginTransaction();
                $oldTag = $pig['tag_no'];

                // Update pig details
                $updateStmt = $pdo->prepare("UPDATE pigs SET tag_no = ?, sex = ?, breed = ?, dob = ?, sire = ?, dam = ?, stage = ?, source = ?, castrated = ?, castration_date = ?, purchase_price = ?, vendor = ? WHERE id = ?");
                $updateStmt->execute([$newTag, $sex, $breed, $dob, $sire, $dam, $stage, $source, $castrated, $castration_date, $purchasePrice, $vendor, $pigId]);

                // If tag changed, update references in sire, dam, breeding_records, and sales
                if ($oldTag !== $newTag) {
                    $pdo->prepare("UPDATE pigs SET sire = ? WHERE sire = ?")->execute([$newTag, $oldTag]);
                    $pdo->prepare("UPDATE pigs SET dam = ? WHERE dam = ?")->execute([$newTag, $oldTag]);
                    $pdo->prepare("UPDATE breeding_records SET sire_no = ? WHERE sire_no = ?")->execute([$newTag, $oldTag]);
                    $pdo->prepare("UPDATE sales SET reference_id = ? WHERE reference_id = ?")->execute([$newTag, $oldTag]);
                }

                // If external purchase with a recorded purchase price, sync sales table entry
                if ($source === 'External Purchase' && $purchasePrice !== null && $purchasePrice > 0) {
                    $chkSale = $pdo->prepare("SELECT id FROM sales WHERE type = 'purchase' AND (reference_id = ? OR reference_id = ?)");
                    $chkSale->execute([$oldTag, $newTag]);
                    $existingSaleId = $chkSale->fetchColumn();
                    if ($existingSaleId) {
                        $pdo->prepare("UPDATE sales SET reference_id = ?, amount = ?, buyer_info = ? WHERE id = ?")
                            ->execute([$newTag, $purchasePrice, $vendor ?: 'External Supplier', $existingSaleId]);
                    } else {
                        $pdo->prepare("INSERT INTO sales (type, reference_id, date, amount, buyer_info, remarks) VALUES ('purchase', ?, ?, ?, ?, ?)")
                            ->execute([$newTag, $dob, $purchasePrice, $vendor ?: 'External Supplier', 'External pig acquisition / bought cost']);
                    }
                }

                $tagNote = ($oldTag !== $newTag) ? " (renamed from #{$oldTag})" : "";
                logActivity($pdo, 'pig_updated', "Updated pig profile for tag #{$newTag}{$tagNote} (Stage: " . ucfirst($stage) . ", Breed: $breed)");

                $pdo->commit();
                $msg = "Pig details & Ear Tag updated successfully!";
                
                // Refresh pig info
                $stmt->execute([$pigId]);
                $pig = $stmt->fetch();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Failed to update pig details: " . $e->getMessage();
            }
        }
    }
}

// Fetch Dams for edit modal
$dams = $pdo->query("SELECT tag_no FROM pigs WHERE sex = 'Female' AND status = 'active' AND id != " . (int)$pigId)->fetchAll();

// Fetch Records
$growth = $pdo->prepare("SELECT * FROM growth_records WHERE pig_id = ? ORDER BY date DESC");
$growth->execute([$pigId]);
$growth_records = $growth->fetchAll();

$health = $pdo->prepare("SELECT * FROM vaccination_records WHERE pig_id = ? ORDER BY date DESC");
$health->execute([$pigId]);
$health_records = $health->fetchAll();

$breeding_records = [];
if (strtolower($pig['sex']) === 'female') {
    $breeding = $pdo->prepare("SELECT * FROM breeding_records WHERE pig_id = ? ORDER BY date_of_service DESC");
    $breeding->execute([$pigId]);
    $breeding_records = $breeding->fetchAll();
}

include 'includes/header.php';
?>

<div class="dashboard-wrapper">

    <div class="dashboard-header pig-view-header">
        <div>
            <h2>Pig Details: <?php echo htmlspecialchars($pig['tag_no']); ?></h2>
            <p>Stage: <span style="text-transform: capitalize; font-weight: 600;"><?php echo htmlspecialchars($pig['stage']); ?></span> | Status: <span style="font-weight: 600; color: var(--primary-color);"><?php echo htmlspecialchars($pig['status']); ?></span></p>
        </div>
        <div class="pig-view-actions">
            <button class="btn btn-warning" onclick="openModal('editPigModal')">✏️ Edit Details / Update Tag</button>
            <button class="btn btn-primary" onclick="openModal('growthModal')">+ Log Weight</button>
            <button class="btn btn-outline" onclick="openModal('vacModal')">+ Log Health</button>
            <?php if (strtolower($pig['sex']) === 'female'): ?>
                <button class="btn btn-success" onclick="openModal('breedingModal')">+ Log Breeding</button>
            <?php endif; ?>
            <a href="pigs.php" class="btn btn-outline">&larr; Back to List</a>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="dashboard-content">
        <!-- Pig Card -->
        <div class="card" style="margin-bottom: 20px;">
            <div style="margin-bottom: 15px; border-bottom: 2px solid var(--border-color); padding-bottom: 8px;">
                <h3 style="margin: 0; color: var(--primary-color);">📋 Pig Card Details</h3>
            </div>

            <div class="table-wrapper" style="border: none; box-shadow: none;">
                <table class="data-table pig-card-table" style="width: 100%; font-size: 0.92rem; border-collapse: collapse;">
                    <tbody>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; width: 35%; background: #fafafa;">Ear Tag Number</td>
                            <td style="padding: 10px 12px;">
                                <span style="font-size: 1.15rem; font-weight: 800; color: var(--primary-color);"><?php echo htmlspecialchars($pig['tag_no']); ?></span>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Source / Origin</td>
                            <td style="padding: 10px 12px;">
                                <span class="badge" style="background: #E3F2FD; color: #1565C0; font-weight: 600; padding: 3px 8px; border-radius: 4px;"><?php echo htmlspecialchars($pig['source'] ?? 'Born on Farm'); ?></span>
                            </td>
                        </tr>
                        <?php if (($pig['source'] ?? '') === 'External Purchase'): ?>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Bought Amount / Cost</td>
                            <td style="padding: 10px 12px;">
                                <?php if (!empty($pig['purchase_price'])): ?>
                                    <strong style="color: #1565C0; font-size: 1rem;">MWK <?php echo number_format($pig['purchase_price'], 2); ?></strong>
                                    <?php if (!empty($pig['vendor'])): ?>
                                        <span style="font-size: 0.85rem; color: var(--text-muted); margin-left: 6px;">(Supplier: <?php echo htmlspecialchars($pig['vendor']); ?>)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic;">Not recorded</span>
                                    <button class="btn btn-outline" style="padding: 2px 8px; font-size: 0.72rem; margin-left: 6px;" onclick="openModal('editPigModal')">+ Record Cost</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Sex / Gender</td>
                            <td style="padding: 10px 12px; font-weight: 600;"><?php echo htmlspecialchars($pig['sex']); ?></td>
                        </tr>
                        <?php if ($pig['sex'] === 'Male'): ?>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Male Castration Status</td>
                            <td style="padding: 10px 12px;">
                                <?php if (!empty($pig['castrated'])): ?>
                                    <span class="badge" style="background: #E8F5E9; color: #1B5E20; font-weight: 600; padding: 3px 8px; border-radius: 4px;">✂️ Castrated Male (Barrow)</span>
                                    <?php if (!empty($pig['castration_date'])): ?>
                                        <span style="font-size: 0.85rem; color: var(--text-muted); margin-left: 6px;">(Castrated on <?php echo htmlspecialchars($pig['castration_date']); ?>)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge" style="background: #FFF3E0; color: #E65100; font-weight: 600; padding: 3px 8px; border-radius: 4px;">🐗 Intact Male (Boar)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Breed</td>
                            <td style="padding: 10px 12px;"><?php echo htmlspecialchars($pig['breed'] ?: 'N/A'); ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Date of Birth</td>
                            <td style="padding: 10px 12px;"><?php echo htmlspecialchars($pig['dob']); ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Current Age</td>
                            <td style="padding: 10px 12px;">
                                <strong><?php echo htmlspecialchars($pig['age_months']); ?> months</strong> (<?php echo htmlspecialchars($pig['age_days_calc']); ?> days)
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Life Stage</td>
                            <td style="padding: 10px 12px; text-transform: capitalize; font-weight: 700; color: var(--primary-color);">
                                <?php echo STAGE_LABELS[$pig['stage']] ?? ucfirst(htmlspecialchars($pig['stage'])); ?>
                            </td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Sire Tag (Father)</td>
                            <td style="padding: 10px 12px;"><?php echo htmlspecialchars($pig['sire'] ?: 'Unknown'); ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Dam Tag (Mother)</td>
                            <td style="padding: 10px 12px;"><?php echo htmlspecialchars($pig['dam'] ?: 'Unknown'); ?></td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px 12px; font-weight: 700; background: #fafafa;">Current Status</td>
                            <td style="padding: 10px 12px;">
                                <?php
                                    $statusLabel = match($pig['status']) {
                                        'sold'      => '🐖 Sold (Live Pig)',
                                        'sold_meat' => '🥩 Sold for Meat / Pork',
                                        'dead'      => '💀 Deceased',
                                        'archived'  => '📦 Archived',
                                        default     => 'Active'
                                    };
                                    $badgeBg = match($pig['status']) {
                                        'sold'      => '#E3F2FD',
                                        'sold_meat' => '#FFF3E0',
                                        'dead'      => '#FFEBEE',
                                        'archived'  => '#F5F5F5',
                                        default     => '#E8F5E9'
                                    };
                                    $badgeClr = match($pig['status']) {
                                        'sold'      => '#1565C0',
                                        'sold_meat' => '#E65100',
                                        'dead'      => '#C62828',
                                        'archived'  => '#616161',
                                        default     => 'var(--primary-color)'
                                    };
                                ?>
                                <span class="badge" style="background: <?php echo $badgeBg; ?>; color: <?php echo $badgeClr; ?>; font-weight: 700; text-transform: uppercase; padding: 3px 8px; border-radius: 4px;">
                                    <?php echo $statusLabel; ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php if ($pig['status'] === 'active'): ?>
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border-color);">
            <h3>Update Status &amp; Record Revenue</h3>
            <form action="pig_view.php?id=<?php echo $pigId; ?>" method="POST" style="margin-top: 10px;">
                <input type="hidden" name="update_status" value="1">
                <div class="form-group">
                    <label for="statusSelect">New Status</label>
                    <select name="status" class="form-control" id="statusSelect" required>
                        <option value="">-- Select New Status --</option>
                        <option value="sold">🐖 Sold (Live Pig)</option>
                        <option value="sold_meat">🥩 Sold for Meat / Pork</option>
                        <option value="dead">💀 Deceased / Dead</option>
                        <option value="archived">📦 Archived</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transaction Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <!-- Live Pig Sale Fields -->
                <div id="soldFields" style="display: none; background: #E8F5E9; border: 1px solid #C8E6C9; padding: 12px 14px; border-radius: 8px; margin-bottom: 1rem;">
                    <label style="font-weight: 700; color: #2E7D32;">🐖 Live Pig Sale Details</label>
                    <div class="form-group" style="margin-top: 8px; margin-bottom: 8px;">
                        <label>Live Pig Sale Price / Total Revenue (MWK)</label>
                        <input type="number" step="0.01" name="price" id="live_sale_price" class="form-control" placeholder="e.g. 150000" style="font-weight: 700;">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Buyer Information / Contact</label>
                        <input type="text" name="buyer" class="form-control" placeholder="e.g. John Banda (+265...)">
                    </div>
                </div>
                <!-- Meat / Slaughter Sale Fields -->
                <div id="meatFields" style="display: none; background: #FFF8E1; border: 1px solid #FFE082; padding: 12px 14px; border-radius: 8px; margin-bottom: 1rem;">
                    <label style="font-weight: 700; color: #F57F17;">🥩 Meat / Pork Sale Details</label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin-top: 8px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Meat/Carcass Weight (kg)</label>
                            <input type="number" step="0.1" name="meat_weight" id="meat_weight" class="form-control" placeholder="e.g. 68.5" oninput="calcMeatTotal()">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Price per kg (MWK) <small>(Optional)</small></label>
                            <input type="number" step="0.01" id="meat_price_per_kg" class="form-control" placeholder="e.g. 3500" oninput="calcMeatTotal()">
                        </div>
                    </div>
                    <div class="form-group" style="margin-top: 8px; margin-bottom: 8px;">
                        <label style="font-weight: 700;">Total Meat Sale Revenue (MWK)</label>
                        <input type="number" step="0.01" name="meat_price" id="meat_price" class="form-control" placeholder="e.g. 239750" style="font-weight: 700;">
                    </div>
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label>Buyer / Customer / Butchery Info</label>
                        <input type="text" name="meat_buyer" class="form-control" placeholder="e.g. Liwonde Butchery / Customer name">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label>Meat Sale Notes</label>
                        <input type="text" name="meat_remarks" class="form-control" placeholder="e.g. Dressed carcass weight sold to local market">
                    </div>
                </div>
                <!-- Dead Fields -->
                <div id="deadFields" style="display: none; background: #FFEBEE; border: 1px solid #FFCDD2; padding: 12px 14px; border-radius: 8px; margin-bottom: 1rem;">
                    <label style="font-weight: 700; color: #C62828;">💀 Mortality Record</label>
                    <div class="form-group" style="margin-top: 8px; margin-bottom: 0;">
                        <label>Cause of Death</label>
                        <input type="text" name="cause" class="form-control" placeholder="e.g. Swine Fever / Disease / Injury">
                    </div>
                </div>
                <div class="form-group">
                    <label>Remarks / Notes</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Additional notes...">
                </div>
                <button type="submit" class="btn btn-primary">Update Status &amp; Save</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Right Side Tabs / History Cards -->
        <div>
            <!-- Growth Records Card -->
            <div class="card" style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3>⚖️ Weight &amp; Growth Records</h3>
                    <button class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="openModal('growthModal')">+ Log Weight</button>
                </div>
                <div class="table-wrapper">
                <table class="data-table striped middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Weight (kg)</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($growth_records as $gr): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($gr['date']); ?></td>
                                <td><span class="tbl-badge blue">⚖️ <?php echo htmlspecialchars($gr['weight']); ?> kg</span></td>
                                <td><?php echo htmlspecialchars($gr['remarks'] ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(count($growth_records) === 0): ?>
                            <tr class="tbl-empty"><td colspan="3">No weight records logged yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Health & Vaccination Card -->
            <div class="card" style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3>💉 Health &amp; Vaccination Records</h3>
                    <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="openModal('vacModal')">+ Log Health</button>
                </div>
                <div class="table-wrapper">
                <table class="data-table striped middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vaccine / Treatment</th>
                            <th>Dose &amp; Route</th>
                            <th>Administered By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($health_records as $h): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($h['date']); ?></td>
                                <td><strong><?php echo htmlspecialchars($h['vaccine']); ?></strong></td>
                                <td><span class="tbl-badge green"><?php echo htmlspecialchars($h['dose']); ?></span> <span class="tbl-sub"><?php echo htmlspecialchars($h['route'] ?: 'IM'); ?></span></td>
                                <td><?php echo htmlspecialchars($h['administered_by'] ?: 'Clerk'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(count($health_records) === 0): ?>
                            <tr class="tbl-empty"><td colspan="4">No health records logged yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <!-- Breeding Records Card (For Females) -->
            <?php if (strtolower($pig['sex']) === 'female'): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h3 style="margin-bottom: 4px;">🍼 Breeding, Gestation &amp; Weaning History</h3>
                        <p style="font-size: 0.83rem; color: var(--text-muted); margin: 0;">Mating dates · Estimated birth dates (+114 days) · Farrowing outcomes · Weaner performance</p>
                    </div>
                    <button class="btn btn-success" style="padding: 0.45rem 1rem; font-size: 0.82rem; white-space: nowrap;" onclick="openModal('breedingModal')">+ Record Mating Service</button>
                </div>

                <?php if (count($breeding_records) === 0): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--text-muted); background: #fafafa; border-radius: 8px; border: 1px dashed var(--border-color);">
                        No breeding or mating records logged for this sow yet.
                    </div>
                <?php else: ?>
                <div class="breed-card-list">
                    <?php foreach($breeding_records as $b): ?>
                        <?php
                            $expectedDt = new DateTime($b['expected_farrowing']);
                            $today = new DateTime('today');
                            $daysDiff = (int)$today->diff($expectedDt)->format('%r%a');

                            $cardClass = 'breed-card ' . htmlspecialchars($b['status']);
                            $countdownHtml = '';
                            if ($b['status'] === 'pregnant') {
                                if ($daysDiff > 0) {
                                    $cardClass = 'breed-card pregnant';
                                    $countdownHtml = '<span class="tbl-badge due">⌛ Due in ' . $daysDiff . ' day' . ($daysDiff > 1 ? 's' : '') . '</span>';
                                } elseif ($daysDiff === 0) {
                                    $cardClass = 'breed-card pregnant';
                                    $countdownHtml = '<span class="tbl-badge today">🚨 Due Today!</span>';
                                } else {
                                    $cardClass = 'breed-card overdue';
                                    $absDays = abs($daysDiff);
                                    $countdownHtml = '<span class="tbl-badge alarm">⚠️ Overdue by ' . $absDays . ' day' . ($absDays > 1 ? 's' : '') . '</span>';
                                }
                            }

                            // Status badge colour
                            $sBadgeClass = match($b['status']) {
                                'pregnant' => 'orange',
                                'farrowed' => 'blue',
                                'weaned'   => 'green',
                                default    => 'grey',
                            };

                            // Weaning survival rate
                            $weanRate = null;
                            if (!empty($b['born_alive']) && $b['born_alive'] > 0 && $b['weaned_count'] !== null) {
                                $weanRate = round(($b['weaned_count'] / $b['born_alive']) * 100, 1);
                            }
                        ?>
                        <div class="<?php echo $cardClass; ?>">

                            <!-- Col 1: Mating & Gestation -->
                            <div class="breed-field">
                                <span class="breed-field-label">Mating Date</span>
                                <span class="breed-field-value"><?php echo htmlspecialchars($b['date_of_service']); ?></span>
                                <span class="breed-field-meta">Sire: <strong><?php echo htmlspecialchars($b['sire_no'] ?: 'N/A'); ?></strong></span>
                                <div style="margin-top: 6px;">
                                    <span class="breed-field-label">Est. Farrowing (+114 days)</span>
                                    <span class="breed-field-value" style="color: var(--primary-color);"><?php echo htmlspecialchars($b['expected_farrowing']); ?></span>
                                    <?php if ($b['actual_farrowing']): ?>
                                        <span class="breed-field-meta">Actual: <?php echo htmlspecialchars($b['actual_farrowing']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Col 2: Farrowing Outcome -->
                            <div class="breed-field">
                                <span class="breed-field-label">Farrowing Outcome</span>
                                <?php if ($b['total_born'] !== null): ?>
                                    <span class="breed-field-value"><?php echo htmlspecialchars($b['total_born']); ?> piglets born</span>
                                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 4px;">
                                        <span class="stat-pill alive">✅ <?php echo htmlspecialchars($b['born_alive']); ?> alive</span>
                                        <span class="stat-pill dead">💀 <?php echo htmlspecialchars($b['stillborn']); ?> stillborn</span>
                                    </div>
                                <?php else: ?>
                                    <span class="breed-field-value" style="color: var(--text-muted); font-weight: 400; font-style: italic;">Pending birth</span>
                                <?php endif; ?>
                            </div>

                            <!-- Col 3: Weaning Performance -->
                            <div class="breed-field">
                                <span class="breed-field-label">Weaning Performance</span>
                                <?php if ($b['weaned_count'] !== null): ?>
                                    <span class="breed-field-value"><?php echo htmlspecialchars($b['weaned_count']); ?> weaners</span>
                                    <?php if ($weanRate !== null): ?>
                                        <span class="stat-pill blue" style="margin-top: 4px;">📊 <?php echo $weanRate; ?>% survival</span>
                                    <?php endif; ?>
                                    <span class="breed-field-meta" style="margin-top: 4px;">
                                        Avg wt: <?php echo htmlspecialchars($b['avg_weaning_wt'] ?: 'N/A'); ?> kg
                                        <?php if ($b['weaning_date']): ?> · <?php echo htmlspecialchars($b['weaning_date']); ?><?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="breed-field-value" style="color: var(--text-muted); font-weight: 400; font-style: italic;">Not yet weaned</span>
                                <?php endif; ?>
                            </div>

                            <!-- Col 4: Status & Actions -->
                            <div class="breed-card-actions">
                                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 6px;">
                                    <span class="tbl-badge <?php echo $sBadgeClass; ?>"><?php echo ucfirst(htmlspecialchars($b['status'])); ?></span>
                                    <?php if ($countdownHtml): ?>
                                        <?php echo $countdownHtml; ?>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 8px;">
                                    <?php if ($b['status'] === 'pregnant'): ?>
                                        <button class="btn btn-primary" style="padding: 0.35rem 0.8rem; font-size: 0.8rem;" onclick='openFarrowingModal(<?php echo json_encode($b); ?>)'>🍼 Record Farrowing</button>
                                    <?php elseif ($b['status'] === 'farrowed'): ?>
                                        <button class="btn btn-outline" style="padding: 0.35rem 0.8rem; font-size: 0.8rem;" onclick='openWeaningModal(<?php echo json_encode($b); ?>)'>🐖 Log Weaning</button>
                                    <?php else: ?>
                                        <span class="tbl-badge grey">✔ Complete</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
</div>

<!-- Modal 1: Add Weight Record -->
<div class="modal" id="growthModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Log Weight / Growth Record</h3>
            <button class="close-btn" onclick="closeModal('growthModal')">&times;</button>
        </div>
        <form action="pig_view.php?id=<?php echo $pigId; ?>" method="POST">
            <input type="hidden" name="add_growth" value="1">
            <div class="form-group">
                <label>Date of Weighing</label>
                <input type="date" name="growth_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Weight (in kg)</label>
                <input type="number" step="0.1" name="weight" class="form-control" placeholder="e.g. 45.5" required>
            </div>
            <div class="form-group">
                <label>Remarks / Notes</label>
                <input type="text" name="growth_remarks" class="form-control" placeholder="e.g. Good progress after new feed formulation">
            </div>
            <div style="text-align: right; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('growthModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Weight</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Add Health & Vaccination Record -->
<div class="modal" id="vacModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Log Health / Vaccination Record</h3>
            <button class="close-btn" onclick="closeModal('vacModal')">&times;</button>
        </div>
        <form action="pig_view.php?id=<?php echo $pigId; ?>" method="POST">
            <input type="hidden" name="add_vaccination" value="1">
            <div class="form-group">
                <label>Date of Administration</label>
                <input type="date" name="vac_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Vaccine / Medication Name</label>
                <input type="text" name="vaccine" class="form-control" placeholder="e.g. Iron Injection, Dewormer, Swine Fever Vac" required>
            </div>
            <div class="form-group">
                <label>Dose Amount</label>
                <input type="text" name="dose" class="form-control" placeholder="e.g. 2ml, 1 tablet" required>
            </div>
            <div class="form-group">
                <label>Route</label>
                <select name="route" class="form-control">
                    <option value="Intramuscular (IM)">Intramuscular (IM)</option>
                    <option value="Subcutaneous (SC)">Subcutaneous (SC)</option>
                    <option value="Oral">Oral</option>
                    <option value="Topical">Topical</option>
                </select>
            </div>
            <div class="form-group">
                <label>Administered By</label>
                <input type="text" name="administered_by" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_fullname']); ?>" required>
            </div>
            <div class="form-group">
                <label>Remarks</label>
                <input type="text" name="vac_remarks" class="form-control" placeholder="Optional notes...">
            </div>
            <div style="text-align: right; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('vacModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Health Record</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Add Breeding Record -->
<?php if (strtolower($pig['sex']) === 'female'): ?>
<div class="modal" id="breedingModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Log Breeding / Mating Event</h3>
            <button class="close-btn" onclick="closeModal('breedingModal')">&times;</button>
        </div>
        <form action="pig_view.php?id=<?php echo $pigId; ?>" method="POST">
            <input type="hidden" name="add_breeding" value="1">
            <div class="form-group">
                <label>Date of Service (Mating Date)</label>
                <input type="date" name="date_of_service" id="dateOfService" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Sire Tag (Father)</label>
                <input type="text" name="sire_no" class="form-control" placeholder="e.g. S-001 or Male Ear Tag" required>
            </div>
            <div class="form-group">
                <label>Expected Farrowing Date (Leave blank for auto 114 days calculation)</label>
                <input type="date" name="expected_farrowing" id="expectedFarrowing" class="form-control">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="breeding_status" class="form-control">
                    <option value="pregnant">Pregnant</option>
                    <option value="farrowed">Farrowed</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <hr style="margin: 15px 0;">
            <p style="font-weight: 600; color: var(--primary-color); margin-bottom: 10px;">Farrowing Outcomes (If already farrowed):</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>Total Born</label>
                    <input type="number" name="total_born" class="form-control" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Born Alive</label>
                    <input type="number" name="born_alive" class="form-control" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Stillborn</label>
                    <input type="number" name="stillborn" class="form-control" placeholder="0">
                </div>
                <div class="form-group">
                    <label>Avg Weaning Wt (kg)</label>
                    <input type="number" step="0.1" name="avg_weaning_wt" class="form-control" placeholder="0.0">
                </div>
            </div>
            <div style="text-align: right; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('breedingModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Breeding Record</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal: Record Farrowing Outcome -->
<?php if (strtolower($pig['sex']) === 'female'): ?>
<div class="modal" id="farrowingModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🍼 Record Farrowing Outcome</h3>
            <button class="close-btn" onclick="closeModal('farrowingModal')">&times;</button>
        </div>
        <form action="pig_view.php?id=<?php echo $pigId; ?>" method="POST">
            <input type="hidden" name="record_farrowing" value="1">
            <input type="hidden" name="breeding_id" id="farrowing_breeding_id">
            
            <div class="form-group">
                <label>Actual Farrowing Date</label>
                <input type="date" name="actual_farrowing" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Born Alive (Healthy Piglets)</label>
                <input type="number" name="born_alive" id="farrowing_born_alive" class="form-control" placeholder="e.g. 10" required min="0">
            </div>
            <div class="form-group">
                <label>Stillborn / Dead at Birth</label>
                <input type="number" name="stillborn" id="farrowing_stillborn" class="form-control" value="0" min="0">
            </div>
            <div style="text-align: right; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('farrowingModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Farrowing Outcome</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Log Weaning Event -->
<div class="modal" id="weaningModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🐖 Log Weaning Event</h3>
            <button class="close-btn" onclick="closeModal('weaningModal')">&times;</button>
        </div>
        <form action="pig_view.php?id=<?php echo $pigId; ?>" method="POST">
            <input type="hidden" name="record_weaning" value="1">
            <input type="hidden" name="breeding_id" id="weaning_breeding_id">
            
            <div class="form-group">
                <label>Weaning Date</label>
                <input type="date" name="weaning_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label>Number of Piglets Weaned</label>
                <input type="number" name="weaned_count" id="weaned_count_input" class="form-control" placeholder="e.g. 9" required min="0">
            </div>
            <div class="form-group">
                <label>Average Weaning Weight (kg)</label>
                <input type="number" step="0.1" name="avg_weaning_wt" class="form-control" placeholder="e.g. 7.5">
            </div>
            <div style="text-align: right; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('weaningModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Weaning Event</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal 4: Edit Pig Details & Update Ear Tag -->
<div class="modal" id="editPigModal">
    <div class="modal-content" style="max-width: 550px;">
        <div class="modal-header">
            <h3>Edit Pig Information &amp; Ear Tag</h3>
            <button class="close-btn" onclick="closeModal('editPigModal')">&times;</button>
        </div>
        <form action="pig_view.php?id=<?php echo $pigId; ?>" method="POST" onsubmit="return validateEditForm()">
            <input type="hidden" name="edit_pig_details" value="1">
            
            <div class="form-group">
                <label style="font-weight: 700; color: var(--primary-color);">Ear Tag No</label>
                <input type="text" name="tag_no" class="form-control" value="<?php echo htmlspecialchars($pig['tag_no']); ?>" required style="font-weight: 700;">
                <small style="color: var(--text-muted);">Assign an official ear tag or update temporary IDs (e.g., Mother tag + int, EXT-XXX).</small>
            </div>
            <div class="form-group">
                <label>Source / Origin</label>
                <select name="source" id="editSourceSelect" class="form-control" onchange="toggleEditSourceFields()">
                    <option value="Born on Farm" <?php echo ($pig['source'] ?? '') === 'Born on Farm' ? 'selected' : ''; ?>>Born on Farm</option>
                    <option value="External Purchase" <?php echo ($pig['source'] ?? '') === 'External Purchase' ? 'selected' : ''; ?>>External Purchase / Bought</option>
                </select>
            </div>

            <!-- External Purchase fields in Edit Modal -->
            <div id="editExternalGroup" style="background: #E3F2FD; border: 1px solid #90CAF9; padding: 12px 15px; border-radius: 8px; margin-bottom: 1rem; display: <?php echo ($pig['source'] ?? '') === 'External Purchase' ? 'block' : 'none'; ?>;">
                <label style="font-weight: 700; color: #1565C0;">💰 External Purchase / Bought Cost</label>
                <div class="form-group" style="margin-top: 8px; margin-bottom: 8px;">
                    <label>Bought Amount / Purchase Price (MWK)</label>
                    <input type="number" step="0.01" name="purchase_price" class="form-control" placeholder="e.g. 75000" value="<?php echo htmlspecialchars($pig['purchase_price'] ?? ''); ?>">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Vendor / Supplier Info</label>
                    <input type="text" name="vendor" class="form-control" placeholder="e.g. Liwonde Livestock Market / Farmer Phiri" value="<?php echo htmlspecialchars($pig['vendor'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Life Stage</label>
                <select name="stage" class="form-control">
                    <option value="adult" <?php echo $pig['stage'] === 'adult' ? 'selected' : ''; ?>>Adult</option>
                    <option value="weaner" <?php echo $pig['stage'] === 'weaner' ? 'selected' : ''; ?>>Weaner</option>
                    <option value="piglet" <?php echo $pig['stage'] === 'piglet' ? 'selected' : ''; ?>>Piglet</option>
                    <option value="grower" <?php echo $pig['stage'] === 'grower' ? 'selected' : ''; ?>>Grower</option>
                    <option value="finisher" <?php echo $pig['stage'] === 'finisher' ? 'selected' : ''; ?>>Finisher</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sex / Gender</label>
                <select name="sex" id="editSexSelect" class="form-control">
                    <option value="Male" <?php echo $pig['sex'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $pig['sex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <!-- Castration fields in Edit Modal -->
            <div id="editCastrationGroup" style="background: #E8F5E9; border: 1px solid #A5D6A7; padding: 12px 15px; border-radius: 8px; margin-bottom: 1rem; display: <?php echo $pig['sex'] === 'Male' ? 'block' : 'none'; ?>;">
                <label style="font-weight: 600; color: #1B5E20;">✂️ Male Castration Details</label>
                <div class="form-group" style="margin-top: 8px; margin-bottom: 8px;">
                    <label>Castration Status</label>
                    <select name="castrated" id="editCastratedSelect" class="form-control">
                        <option value="0" <?php echo empty($pig['castrated']) ? 'selected' : ''; ?>>🐗 Intact Male (Boar)</option>
                        <option value="1" <?php echo !empty($pig['castrated']) ? 'selected' : ''; ?>>✂️ Castrated Male (Barrow)</option>
                    </select>
                </div>
                <div class="form-group" id="editCastrationDateGroup" style="display: <?php echo !empty($pig['castrated']) ? 'block' : 'none'; ?>; margin-bottom: 0;">
                    <label>Castration Date</label>
                    <input type="date" name="castration_date" id="edit_castration_date" class="form-control" value="<?php echo htmlspecialchars($pig['castration_date'] ?? date('Y-m-d')); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Breed</label>
                <input type="text" name="breed" class="form-control" value="<?php echo htmlspecialchars($pig['breed']); ?>" required>
            </div>
            <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="dob" id="edit_dob" class="form-control" value="<?php echo htmlspecialchars($pig['dob']); ?>" required>
            </div>
            <div class="form-group">
                <label>Sire Tag (Father)</label>
                <input type="text" name="sire" class="form-control" value="<?php echo htmlspecialchars($pig['sire']); ?>">
            </div>
            <div class="form-group">
                <label>Dam Tag (Mother)</label>
                <select name="dam" class="form-control">
                    <option value="">-- None / Unknown --</option>
                    <?php foreach($dams as $d): ?>
                        <option value="<?php echo htmlspecialchars($d['tag_no']); ?>" <?php echo $pig['dam'] === $d['tag_no'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['tag_no']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="text-align: right; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('editPigModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes &amp; Update Tag</button>
            </div>
        </form>
    </div>
</div>

<script>
    const statusSelect = document.getElementById('statusSelect');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            document.getElementById('soldFields').style.display = this.value === 'sold' ? 'block' : 'none';
            document.getElementById('meatFields').style.display = this.value === 'sold_meat' ? 'block' : 'none';
            document.getElementById('deadFields').style.display = this.value === 'dead' ? 'block' : 'none';
        });
    }

    function calcMeatTotal() {
        const wt = parseFloat(document.getElementById('meat_weight').value) || 0;
        const ppk = parseFloat(document.getElementById('meat_price_per_kg').value) || 0;
        if (wt > 0 && ppk > 0) {
            document.getElementById('meat_price').value = (wt * ppk).toFixed(2);
        }
    }

    function toggleEditSourceFields() {
        const sel = document.getElementById('editSourceSelect');
        const grp = document.getElementById('editExternalGroup');
        if (sel && grp) {
            grp.style.display = sel.value === 'External Purchase' ? 'block' : 'none';
        }
    }

    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function openFarrowingModal(record) {
        document.getElementById('farrowing_breeding_id').value = record.id;
        if (record.born_alive !== null) {
            document.getElementById('farrowing_born_alive').value = record.born_alive;
        }
        if (record.stillborn !== null) {
            document.getElementById('farrowing_stillborn').value = record.stillborn;
        }
        openModal('farrowingModal');
    }

    function openWeaningModal(record) {
        document.getElementById('weaning_breeding_id').value = record.id;
        if (record.born_alive !== null) {
            document.getElementById('weaned_count_input').value = record.born_alive;
        }
        openModal('weaningModal');
    }

    // Edit modal castration toggle
    const editSexSelect = document.getElementById('editSexSelect');
    const editCastrationGroup = document.getElementById('editCastrationGroup');
    const editCastratedSelect = document.getElementById('editCastratedSelect');
    const editCastrationDateGroup = document.getElementById('editCastrationDateGroup');

    if (editSexSelect && editCastrationGroup) {
        editSexSelect.addEventListener('change', function() {
            editCastrationGroup.style.display = this.value === 'Male' ? 'block' : 'none';
        });
    }

    if (editCastratedSelect && editCastrationDateGroup) {
        editCastratedSelect.addEventListener('change', function() {
            editCastrationDateGroup.style.display = this.value === '1' ? 'block' : 'none';
        });
    }

    function validateEditForm() {
        const sex = document.getElementById('editSexSelect').value;
        const castrated = document.getElementById('editCastratedSelect').value;
        const castDate = document.getElementById('edit_castration_date').value;
        const dob = document.getElementById('edit_dob').value;

        if (sex === 'Male' && castrated === '1' && castDate && dob) {
            if (castDate < dob) {
                alert(`Error: Castration date (${castDate}) cannot be earlier than Date of Birth (${dob}).`);
                return false;
            }
        }
        return true;
    }

    // Auto calculate expected farrowing date (+114 days) in modal
    const dateOfServiceInput = document.getElementById('dateOfService');
    const expectedFarrowingInput = document.getElementById('expectedFarrowing');
    if (dateOfServiceInput && expectedFarrowingInput) {
        dateOfServiceInput.addEventListener('change', function() {
            if (this.value) {
                const dt = new Date(this.value);
                dt.setDate(dt.getDate() + 114);
                expectedFarrowingInput.value = dt.toISOString().split('T')[0];
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>

