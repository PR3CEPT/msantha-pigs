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
$stmt = $pdo->prepare("SELECT *, TIMESTAMPDIFF(MONTH, dob, CURDATE()) as age_months, DATEDIFF(CURDATE(), dob) as age_days_calc FROM pigs WHERE id = ?");
$stmt->execute([$pigId]);
$pig = $stmt->fetch();

if (!$pig) {
    header("Location: pigs.php");
    exit();
}

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
        } else if ($status === 'sold') {
            $price = $_POST['price'] ?? 0;
            $buyer = $_POST['buyer'] ?? 'N/A';
            $remarks = $_POST['remarks'] ?? 'Live pig sale';
            $pdo->prepare("INSERT INTO sales (type, reference_id, date, amount, buyer_info, remarks) VALUES ('live_pig', ?, ?, ?, ?, ?)")
                ->execute([$pigId, $date, $price, $buyer, $remarks]);
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
        $pdo->commit();
        $msg = "Breeding record logged successfully!";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Failed to save breeding record: " . $e->getMessage();
    }
}

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
    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>Pig Details: <?php echo htmlspecialchars($pig['tag_no']); ?></h2>
            <p>Stage: <span style="text-transform: capitalize; font-weight: 600;"><?php echo htmlspecialchars($pig['stage']); ?></span> | Status: <span style="font-weight: 600; color: var(--primary-color);"><?php echo htmlspecialchars($pig['status']); ?></span></p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
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
        <!-- Basic Information Card -->
        <div class="card">
            <h3>Basic Information</h3>
            <p style="margin-bottom: 8px;"><strong>Ear Tag No:</strong> <?php echo htmlspecialchars($pig['tag_no']); ?></p>
            <p style="margin-bottom: 8px;"><strong>Sex:</strong> <?php echo htmlspecialchars($pig['sex']); ?></p>
            <p style="margin-bottom: 8px;"><strong>Breed:</strong> <?php echo htmlspecialchars($pig['breed'] ?: 'N/A'); ?></p>
            <p style="margin-bottom: 8px;"><strong>Date of Birth:</strong> <?php echo htmlspecialchars($pig['dob']); ?></p>
            <p style="margin-bottom: 8px;"><strong>Age:</strong> <?php echo htmlspecialchars($pig['age_months']); ?> months (<?php echo htmlspecialchars($pig['age_days_calc']); ?> days)</p>
            <p style="margin-bottom: 8px;"><strong>Sire (Father):</strong> <?php echo htmlspecialchars($pig['sire'] ?: 'Unknown'); ?></p>
            <p style="margin-bottom: 8px;"><strong>Dam (Mother):</strong> <?php echo htmlspecialchars($pig['dam'] ?: 'Unknown'); ?></p>
            <p style="margin-bottom: 8px;"><strong>Current Status:</strong> <span style="color: var(--primary-color); font-weight: bold;"><?php echo htmlspecialchars($pig['status']); ?></span></p>
            <p style="margin-bottom: 8px;"><strong>Life Stage:</strong> <span style="text-transform: capitalize; font-weight: bold;"><?php echo htmlspecialchars($pig['stage']); ?></span></p>
            
            <?php if ($pig['status'] === 'active'): ?>
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border-color);">
            <h3>Update Status (Record keeping)</h3>
            <form action="pig_view.php?id=<?php echo $pigId; ?>" method="POST" style="margin-top: 10px;">
                <input type="hidden" name="update_status" value="1">
                <div class="form-group">
                    <label for="statusSelect">New Status</label>
                    <select name="status" class="form-control" id="statusSelect" required>
                        <option value="">-- Select New Status --</option>
                        <option value="sold">Sold</option>
                        <option value="dead">Dead</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transaction Date</label>
                    <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div id="soldFields" style="display: none;">
                    <div class="form-group">
                        <label>Sale Price / Amount (MWK)</label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="e.g. 150000">
                    </div>
                    <div class="form-group">
                        <label>Buyer Information</label>
                        <input type="text" name="buyer" class="form-control" placeholder="e.g. John Banda (+265...)">
                    </div>
                </div>
                <div id="deadFields" style="display: none;">
                    <div class="form-group">
                        <label>Cause of Death</label>
                        <input type="text" name="cause" class="form-control" placeholder="e.g. Swine Fever / Disease">
                    </div>
                </div>
                <div class="form-group">
                    <label>Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Additional notes...">
                </div>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Right Side Tabs / History Cards -->
        <div>
            <!-- Growth Records Card -->
            <div class="card" style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3>Growth & Weight Records</h3>
                    <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="openModal('growthModal')">+ Add Weight</button>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                            <th style="padding: 8px;">Date</th>
                            <th>Weight (kg)</th>
                            <th>Age (Days)</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($growth_records as $g): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 8px;"><?php echo htmlspecialchars($g['date']); ?></td>
                                <td><strong><?php echo htmlspecialchars($g['weight']); ?> kg</strong></td>
                                <td><?php echo htmlspecialchars($g['age_days']); ?></td>
                                <td><?php echo htmlspecialchars($g['remarks'] ?: '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(count($growth_records) === 0): ?>
                            <tr><td colspan="4" style="padding: 1rem; text-align: center; color: var(--text-muted);">No weight records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Health & Vaccination Card -->
            <div class="card" style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3>Health & Vaccination Records</h3>
                    <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="openModal('vacModal')">+ Add Health</button>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                            <th style="padding: 8px;">Date</th>
                            <th>Vaccine / Treatment</th>
                            <th>Dose</th>
                            <th>Admin By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($health_records as $h): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 8px;"><?php echo htmlspecialchars($h['date']); ?></td>
                                <td><strong><?php echo htmlspecialchars($h['vaccine']); ?></strong></td>
                                <td><?php echo htmlspecialchars($h['dose']); ?> (<?php echo htmlspecialchars($h['route'] ?: 'IM'); ?>)</td>
                                <td><?php echo htmlspecialchars($h['administered_by'] ?: 'Clerk'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(count($health_records) === 0): ?>
                            <tr><td colspan="4" style="padding: 1rem; text-align: center; color: var(--text-muted);">No health records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Breeding Records Card (For Females) -->
            <?php if (strtolower($pig['sex']) === 'female'): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3>Breeding & Farrowing History</h3>
                    <button class="btn btn-success" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" onclick="openModal('breedingModal')">+ Add Breeding</button>
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                            <th style="padding: 8px;">Mating Date</th>
                            <th>Sire Tag</th>
                            <th>Expected Farrow</th>
                            <th>Born (Alive/Still)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($breeding_records as $b): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 8px;"><?php echo htmlspecialchars($b['date_of_service']); ?></td>
                                <td><strong><?php echo htmlspecialchars($b['sire_no'] ?: 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($b['expected_farrowing']); ?></td>
                                <td>
                                    <?php if ($b['total_born'] !== null): ?>
                                        <?php echo htmlspecialchars($b['total_born']); ?> (<?php echo htmlspecialchars($b['born_alive']); ?> alive / <?php echo htmlspecialchars($b['stillborn']); ?> still)
                                    <?php else: ?>
                                        Pending
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge" style="padding: 4px 8px; border-radius: 4px; background: <?php echo $b['status'] === 'pregnant' ? '#FFF3E0' : '#E8F5E9'; ?>; color: <?php echo $b['status'] === 'pregnant' ? '#E65100' : '#2E7D32'; ?>;">
                                        <?php echo ucfirst(htmlspecialchars($b['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(count($breeding_records) === 0): ?>
                            <tr><td colspan="5" style="padding: 1rem; text-align: center; color: var(--text-muted);">No breeding records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
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

<script>
    const statusSelect = document.getElementById('statusSelect');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            document.getElementById('soldFields').style.display = this.value === 'sold' ? 'block' : 'none';
            document.getElementById('deadFields').style.display = this.value === 'dead' ? 'block' : 'none';
        });
    }

    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
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
