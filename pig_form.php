<?php
require_once 'db.php';
requireLogin();

$error = null;
$successMsg = null;
$activeTab = $_POST['entry_type'] ?? ($_GET['tab'] ?? 'single');

// Fetch active dams (female pigs)
$dams = $pdo->query("SELECT tag_no FROM pigs WHERE sex = 'Female' AND status = 'active' ORDER BY tag_no ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entryType = $_POST['entry_type'] ?? 'single';

    if ($entryType === 'single') {
        // ==========================================
        // SINGLE PIG REGISTRATION
        // ==========================================
        $source = $_POST['source'] ?? 'Born on Farm';
        $dobMode = $_POST['dob_mode'] ?? 'known';
        $sex = $_POST['sex'] ?? 'Male';
        $breed = trim($_POST['breed'] ?? 'Large White');
        $sire = trim($_POST['sire'] ?? '');
        $dam = trim($_POST['dam'] ?? '');
        $tag_no = trim($_POST['tag_no'] ?? '');

        if ($dobMode === 'unknown') {
            $stage = $_POST['stage_est'] ?? 'piglet';
            $approxDays = !empty($_POST['approx_days']) ? (int)$_POST['approx_days'] : null;
            $dob = getEstimatedDobForStage($stage, $approxDays);
        } else {
            $dob = $_POST['dob'] ?? date('Y-m-d');
            $stage = computePigStage($dob);
        }

        $castrated = ($sex === 'Male' && isset($_POST['castrated']) && $_POST['castrated'] === '1') ? 1 : 0;
        $castration_date = ($castrated === 1 && !empty($_POST['castration_date'])) ? $_POST['castration_date'] : null;

        // VALIDATION 1: Castration date cannot be before Date of Birth
        if ($castrated === 1 && !empty($castration_date)) {
            if ($castration_date < $dob) {
                $error = "Validation Error: Castration date (" . htmlspecialchars($castration_date) . ") cannot be earlier than Date of Birth (" . htmlspecialchars($dob) . ").";
            }
        }

        if (!$error) {
            // Auto-generate tag if empty
            if (empty($tag_no)) {
                if ($source === 'External Purchase') {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM pigs WHERE source = 'External Purchase'");
                    $count = (int)$stmt->fetchColumn();
                    $tag_no = 'EXT-' . sprintf('%03d', $count + 1);
                } else if (!empty($dam)) {
                    // Mother's tag + integer
                    $stmt = $pdo->prepare("SELECT tag_no FROM pigs WHERE dam = ? OR tag_no LIKE ?");
                    $stmt->execute([$dam, $dam . '-%']);
                    $existingTags = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    $maxIdx = 0;
                    foreach ($existingTags as $t) {
                        if (preg_match('/^' . preg_quote($dam, '/') . '-(\d+)$/', $t, $m)) {
                            $idx = (int)$m[1];
                            if ($idx > $maxIdx) $maxIdx = $idx;
                        }
                    }
                    $tag_no = $dam . '-' . ($maxIdx + 1);
                } else {
                    $stmt = $pdo->query("SELECT COUNT(*) FROM pigs");
                    $count = (int)$stmt->fetchColumn();
                    $tag_no = 'PIG-' . sprintf('%03d', $count + 1);
                }
            }

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO pigs (tag_no, sex, breed, dob, sire, dam, stage, source, castrated, castration_date, last_known_stage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tag_no, $sex, $breed, $dob, $sire, $dam, $stage, $source, $castrated, $castration_date, $stage]);

                $castNote = ($sex === 'Male') ? ($castrated ? " [Castrated Barrow]" : " [Intact Boar]") : "";
                logActivity($pdo, 'pig_created', "Registered single pig #$tag_no (Sex: $sex$castNote, Breed: $breed, Stage: " . ucfirst($stage) . ", Source: $source)");
                $pdo->commit();

                header("Location: pigs.php");
                exit();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = "Error saving pig record: Tag number '" . htmlspecialchars($tag_no) . "' already exists or is invalid.";
            }
        }

    } else if ($entryType === 'batch') {
        // ==========================================
        // BATCH / MULTIPLE PIG REGISTRATION
        // ==========================================
        $source = $_POST['batch_source'] ?? 'Born on Farm';
        $dam = trim($_POST['batch_dam'] ?? '');
        $sire = trim($_POST['batch_sire'] ?? '');
        $breed = trim($_POST['batch_breed'] ?? 'Large White');

        $dobMode = $_POST['batch_dob_mode'] ?? 'known';
        if ($dobMode === 'unknown') {
            $stage = $_POST['batch_stage_est'] ?? 'piglet';
            $approxDays = !empty($_POST['batch_approx_days']) ? (int)$_POST['batch_approx_days'] : null;
            $dob = getEstimatedDobForStage($stage, $approxDays);
        } else {
            $dob = $_POST['batch_dob'] ?? date('Y-m-d');
            $stage = computePigStage($dob);
        }

        $malesCount = max(0, (int)($_POST['males_count'] ?? 0));
        $femalesCount = max(0, (int)($_POST['females_count'] ?? 0));
        $totalBatch = $malesCount + $femalesCount;

        $batchCastrated = (isset($_POST['batch_castrated']) && $_POST['batch_castrated'] === '1') ? 1 : 0;
        $batchCastrationDate = ($batchCastrated === 1 && !empty($_POST['batch_castration_date'])) ? $_POST['batch_castration_date'] : null;

        // VALIDATION 1: Total pigs in batch must be at least 1
        if ($totalBatch < 1) {
            $error = "Validation Error: Please specify at least 1 Male or 1 Female pig to register in batch.";
        }

        // VALIDATION 2: Castration date cannot be before Date of Birth
        if ($batchCastrated === 1 && !empty($batchCastrationDate) && $malesCount > 0) {
            if ($batchCastrationDate < $dob) {
                $error = "Validation Error: Castration date (" . htmlspecialchars($batchCastrationDate) . ") cannot be earlier than Date of Birth (" . htmlspecialchars($dob) . ").";
            }
        }

        if (!$error) {
            try {
                $pdo->beginTransaction();

                // Determine base prefix and starting index for auto-tagging
                $customPrefix = trim($_POST['batch_prefix'] ?? '');
                $startIndex = 1;

                if (!empty($customPrefix)) {
                    $prefix = $customPrefix;
                    // Find highest integer suffix for custom prefix
                    $stmt = $pdo->prepare("SELECT tag_no FROM pigs WHERE tag_no LIKE ?");
                    $stmt->execute([$prefix . '-%']);
                    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($existing as $t) {
                        if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', $t, $m)) {
                            $idx = (int)$m[1];
                            if ($idx >= $startIndex) $startIndex = $idx + 1;
                        }
                    }
                } else if ($source === 'Born on Farm' && !empty($dam)) {
                    $prefix = $dam;
                    // Query existing piglets/pigs with dam tag
                    $stmt = $pdo->prepare("SELECT tag_no FROM pigs WHERE dam = ? OR tag_no LIKE ?");
                    $stmt->execute([$dam, $dam . '-%']);
                    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($existing as $t) {
                        if (preg_match('/^' . preg_quote($dam, '/') . '-(\d+)$/', $t, $m)) {
                            $idx = (int)$m[1];
                            if ($idx >= $startIndex) $startIndex = $idx + 1;
                        }
                    }
                } else if ($source === 'External Purchase') {
                    $prefix = 'EXT';
                    $stmt = $pdo->query("SELECT tag_no FROM pigs WHERE source = 'External Purchase' OR tag_no LIKE 'EXT-%'");
                    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($existing as $t) {
                        if (preg_match('/^EXT-(\d+)$/', $t, $m)) {
                            $idx = (int)$m[1];
                            if ($idx >= $startIndex) $startIndex = $idx + 1;
                        }
                    }
                } else {
                    $prefix = 'PIG';
                    $stmt = $pdo->query("SELECT tag_no FROM pigs WHERE tag_no LIKE 'PIG-%'");
                    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($existing as $t) {
                        if (preg_match('/^PIG-(\d+)$/', $t, $m)) {
                            $idx = (int)$m[1];
                            if ($idx >= $startIndex) $startIndex = $idx + 1;
                        }
                    }
                }

                $insertedTags = [];
                $currentIndex = $startIndex;

                $insertStmt = $pdo->prepare("INSERT INTO pigs (tag_no, sex, breed, dob, sire, dam, stage, source, castrated, castration_date, last_known_stage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                // Insert Male pigs
                for ($i = 0; $i < $malesCount; $i++) {
                    $tag = $prefix . '-' . $currentIndex;
                    $insertStmt->execute([
                        $tag, 'Male', $breed, $dob, $sire, $dam, $stage, $source,
                        $batchCastrated, $batchCastrationDate, $stage
                    ]);
                    $insertedTags[] = $tag;
                    $currentIndex++;
                }

                // Insert Female pigs
                for ($i = 0; $i < $femalesCount; $i++) {
                    $tag = $prefix . '-' . $currentIndex;
                    $insertStmt->execute([
                        $tag, 'Female', $breed, $dob, $sire, $dam, $stage, $source,
                        0, null, $stage
                    ]);
                    $insertedTags[] = $tag;
                    $currentIndex++;
                }

                $castSummary = ($malesCount > 0 && $batchCastrated) ? " (Males Castrated on $batchCastrationDate)" : "";
                logActivity($pdo, 'batch_pigs_created', "Registered batch of $totalBatch pigs ($malesCount Males, $femalesCount Females) under prefix '$prefix'$castSummary. Source: $source, Breed: $breed.");

                $pdo->commit();
                header("Location: pigs.php");
                exit();

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = "Error processing batch registration: " . $e->getMessage();
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div>
            <h2>Register Pigs / Livestock Entry</h2>
            <p>Single pig registration or batch entry for born piglets and purchased pigs.</p>
        </div>
        <a href="pigs.php" class="btn btn-outline">&larr; Back to Directory</a>
    </div>

    <!-- Registration Mode Tab Selector -->
    <div style="max-width: 720px; margin: 0 auto 20px;">
        <div style="display:flex; border-bottom: 2px solid var(--border-color); background: #f8f9fa; border-radius: 8px 8px 0 0; overflow:hidden;">
            <button type="button" class="tab-btn <?php echo $activeTab === 'single' ? 'active-tab' : ''; ?>" onclick="switchTab('single')" style="flex:1; padding: 14px 20px; font-weight:700; font-size:0.95rem; border:none; background:none; cursor:pointer; text-align:center;">
                🐖 Single Pig Registration
            </button>
            <button type="button" class="tab-btn <?php echo $activeTab === 'batch' ? 'active-tab' : ''; ?>" onclick="switchTab('batch')" style="flex:1; padding: 14px 20px; font-weight:700; font-size:0.95rem; border:none; background:none; cursor:pointer; text-align:center;">
                👥 Batch / Multiple Pig Registration
            </button>
        </div>
    </div>

    <style>
        .tab-btn { color: var(--text-muted); transition: all 0.2s; }
        .tab-btn:hover { background: #e9ecef; }
        .active-tab { background: #ffffff !important; color: var(--primary-color) !important; border-bottom: 3px solid var(--primary-color) !important; }
        .form-section-title { font-size: 0.9rem; font-weight: 700; color: var(--primary-color); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; }
    </style>

    <div class="card" style="max-width: 720px; margin: 0 auto;">
        <?php if($error): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- 1. SINGLE PIG FORM -->
        <!-- ============================================================== -->
        <div id="singlePigFormSection" style="display: <?php echo $activeTab === 'single' ? 'block' : 'none'; ?>;">
            <form action="pig_form.php" method="POST" onsubmit="return validateSingleForm()">
                <input type="hidden" name="entry_type" value="single">

                <div class="form-section-title">1. Origin &amp; Tagging</div>
                
                <div class="form-group">
                    <label for="source">Source / Origin</label>
                    <select name="source" id="source" class="form-control" required onchange="updateSingleTagHint()">
                        <option value="Born on Farm">Born on Farm</option>
                        <option value="External Purchase">External Purchase / Bought</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="dam">Dam Tag (Mother)</label>
                    <select name="dam" id="dam" class="form-control" onchange="updateSingleTagHint()">
                        <option value="">-- Select Mother (Optional) --</option>
                        <?php foreach($dams as $d): ?>
                            <option value="<?php echo htmlspecialchars($d['tag_no']); ?>"><?php echo htmlspecialchars($d['tag_no']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="sire">Sire Tag (Father)</label>
                    <input type="text" id="sire" name="sire" class="form-control" placeholder="Optional father tag no">
                </div>

                <div class="form-group">
                    <label for="tag_no">Ear Tag No (Optional)</label>
                    <input type="text" id="tag_no" name="tag_no" class="form-control" placeholder="e.g. M-001-1 or leave blank to auto-generate">
                    <small style="color: var(--text-muted); display: block; margin-top: 4px;" id="tagHint">
                        💡 If left blank: Farm born pig inherits Mother's Tag + integer (e.g. M-001-1). Purchased pigs take EXT-XXX.
                    </small>
                </div>

                <div class="form-section-title" style="margin-top:20px;">2. Sex &amp; Male Castration</div>

                <div class="form-group">
                    <label for="sex">Sex / Gender</label>
                    <select name="sex" id="sex" class="form-control" required onchange="toggleSexFields()">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </div>

                <!-- Male Castration Group -->
                <div id="maleCastrationGroup" style="background: #E8F5E9; border: 1px solid #A5D6A7; padding: 14px 16px; border-radius: 8px; margin-bottom: 1.2rem;">
                    <label style="font-weight: 700; color: #1B5E20; font-size: 0.95rem;">✂️ Male Castration Details</label>
                    <div class="form-group" style="margin-top: 10px; margin-bottom: 8px;">
                        <label for="castratedSelect">Castration Status</label>
                        <select name="castrated" id="castratedSelect" class="form-control" onchange="toggleCastrationDate()">
                            <option value="0">🐗 Intact Male (Boar / Breeding Male)</option>
                            <option value="1">✂️ Castrated Male (Barrow / Fattening)</option>
                        </select>
                    </div>
                    <div class="form-group" id="castrationDateGroup" style="display: none; margin-bottom: 0;">
                        <label for="castration_date">Castration Date</label>
                        <input type="date" id="castration_date" name="castration_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        <small style="color:#C62828; display:block; margin-top:4px;" id="castrationValMsg"></small>
                    </div>
                </div>

                <div class="form-section-title" style="margin-top:20px;">3. Breed &amp; Birth Details / Stage</div>

                <div class="form-group">
                    <label for="breed">Breed</label>
                    <input type="text" id="breed" name="breed" class="form-control" value="Large White" placeholder="e.g. Large White, Landrace, Duroc" required>
                </div>

                <!-- DOB Mode Choice -->
                <div class="form-group">
                    <label style="font-weight:600;">Birth Record Availability</label>
                    <div style="display:flex; gap:20px; margin-top:4px;">
                        <label style="font-weight:normal; cursor:pointer;">
                            <input type="radio" name="dob_mode" value="known" checked onclick="toggleDobMode('single', 'known')"> Exact Birth Date Known
                        </label>
                        <label style="font-weight:normal; cursor:pointer;">
                            <input type="radio" name="dob_mode" value="unknown" onclick="toggleDobMode('single', 'unknown')"> No Birth Record (Select Stage / Estimate Age)
                        </label>
                    </div>
                </div>

                <!-- Known DOB Field -->
                <div class="form-group" id="singleDobContainer">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" class="form-control" value="<?php echo date('Y-m-d'); ?>" onchange="updateCastrationMinDate('single')">
                </div>

                <!-- Unknown DOB / Stage Selection Box -->
                <div id="singleStageContainer" style="display:none; background:#F1F8E9; border:1px solid #C5E1A5; padding:14px 16px; border-radius:8px; margin-bottom:1rem;">
                    <p style="margin:0 0 10px; font-weight:700; color:#33691E; font-size:0.9rem;">
                        💡 Select Life Stage &amp; Estimate Age (System will calculate estimated DOB)
                    </p>
                    <div class="form-group">
                        <label for="stage_est">Select Life Stage</label>
                        <select name="stage_est" id="stage_est" class="form-control" onchange="updateApproxHint('single')">
                            <option value="piglet">🐽 Piglet (0 – 4 weeks old)</option>
                            <option value="weaner">🐖 Weaner (4 – 12 weeks old)</option>
                            <option value="grower">📈 Grower (3 – 5 months old)</option>
                            <option value="finisher">🏁 Finisher (5 – 7 months old)</option>
                            <option value="adult">🐗 Adult / Breeder (7+ months old)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="approx_days">Approximate Age in Days (Optional)</label>
                        <input type="number" name="approx_days" id="approx_days" class="form-control" placeholder="e.g. 14 for 2 weeks old, 56 for 8 weeks old" onchange="updateCastrationMinDate('single')">
                        <small style="color:#558B2F; display:block; margin-top:4px;" id="singleApproxHint">Default age midpoint will be used if left blank.</small>
                    </div>
                </div>

                <div style="margin-top: 1.8rem; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="padding: 0.6rem 1.5rem;">Save Single Pig</button>
                    <a href="pigs.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>


        <!-- ============================================================== -->
        <!-- 2. BATCH / MULTIPLE PIG FORM -->
        <!-- ============================================================== -->
        <div id="batchPigFormSection" style="display: <?php echo $activeTab === 'batch' ? 'block' : 'none'; ?>;">
            <form action="pig_form.php" method="POST" onsubmit="return validateBatchForm()">
                <input type="hidden" name="entry_type" value="batch">

                <div style="background: #E3F2FD; border: 1px solid #90CAF9; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 6px; color: #0D47A1;">👥 Batch Pig Registration</h4>
                    <p style="margin: 0; font-size: 0.88rem; color: #1565C0; line-height: 1.4;">
                        Ideal for newly born litters or large external purchases. The system will automatically split the count into <strong>Males</strong> and <strong>Females</strong>, and generate sequential tag numbers (e.g. Mother's tag + integer <code>M-001-1</code>, <code>M-001-2</code>...).
                    </p>
                </div>

                <div class="form-section-title">1. Batch Source &amp; Tagging</div>

                <div class="form-group">
                    <label for="batch_source">Source / Origin</label>
                    <select name="batch_source" id="batch_source" class="form-control" required onchange="updateBatchTagHint()">
                        <option value="Born on Farm">Born on Farm</option>
                        <option value="External Purchase">External Purchase / Bought</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="batch_dam">Dam Tag (Mother)</label>
                    <select name="batch_dam" id="batch_dam" class="form-control" onchange="updateBatchTagHint()">
                        <option value="">-- Select Mother Sow (Optional) --</option>
                        <?php foreach($dams as $d): ?>
                            <option value="<?php echo htmlspecialchars($d['tag_no']); ?>"><?php echo htmlspecialchars($d['tag_no']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="batch_sire">Sire Tag (Father)</label>
                    <input type="text" id="batch_sire" name="batch_sire" class="form-control" placeholder="Optional sire tag no">
                </div>

                <div class="form-group">
                    <label for="batch_prefix">Custom Tag Prefix (Optional)</label>
                    <input type="text" id="batch_prefix" name="batch_prefix" class="form-control" placeholder="e.g. M-001 or EXT-2026 (Leave blank to use Mother's Tag)">
                    <small style="color: var(--text-muted); display: block; margin-top: 4px;" id="batchTagHint">
                        💡 Litters will inherit Dam Tag + suffix integer: e.g. M-001-1, M-001-2, M-001-3...
                    </small>
                </div>

                <div class="form-section-title" style="margin-top:20px;">2. Gender Classification &amp; Counts</div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="males_count" style="color:#1565C0; font-weight:700;">♂️ Number of Male Pigs</label>
                        <input type="number" min="0" value="0" name="males_count" id="males_count" class="form-control" style="font-size:1.1rem; font-weight:700; text-align:center;" onchange="updateBatchTotal()" onkeyup="updateBatchTotal()">
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="females_count" style="color:#C2185B; font-weight:700;">♀️ Number of Female Pigs</label>
                        <input type="number" min="0" value="0" name="females_count" id="females_count" class="form-control" style="font-size:1.1rem; font-weight:700; text-align:center;" onchange="updateBatchTotal()" onkeyup="updateBatchTotal()">
                    </div>
                </div>

                <div style="background:#F5F5F5; border:1px solid #E0E0E0; border-radius:6px; padding:8px 12px; margin-bottom:15px; text-align:right;">
                    <strong>Total Pigs in Batch: <span id="batchTotalDisplay" style="font-size:1.2rem; color:var(--primary-color);">0</span></strong>
                </div>

                <!-- Batch Male Castration Details -->
                <div id="batchMaleCastrationGroup" style="background: #E8F5E9; border: 1px solid #A5D6A7; padding: 14px 16px; border-radius: 8px; margin-bottom: 1.2rem;">
                    <label style="font-weight: 700; color: #1B5E20; font-size: 0.95rem;">✂️ Male Castration Details (For All Males in Batch)</label>
                    <div class="form-group" style="margin-top: 10px; margin-bottom: 8px;">
                        <label for="batch_castrated">Castration Status for Males</label>
                        <select name="batch_castrated" id="batch_castrated" class="form-control" onchange="toggleBatchCastrationDate()">
                            <option value="0">🐗 Intact Males (Boars / Breeding)</option>
                            <option value="1">✂️ Castrated Males (Barrows / Fattening)</option>
                        </select>
                    </div>
                    <div class="form-group" id="batchCastrationDateGroup" style="display: none; margin-bottom: 0;">
                        <label for="batch_castration_date">Castration Date</label>
                        <input type="date" id="batch_castration_date" name="batch_castration_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        <small style="color:#C62828; display:block; margin-top:4px;" id="batchCastrationValMsg"></small>
                    </div>
                </div>

                <div class="form-section-title" style="margin-top:20px;">3. Breed &amp; Birth Details / Stage</div>

                <div class="form-group">
                    <label for="batch_breed">Breed</label>
                    <input type="text" id="batch_breed" name="batch_breed" class="form-control" value="Large White" placeholder="e.g. Large White, Landrace, Duroc" required>
                </div>

                <!-- DOB Mode Choice for Batch -->
                <div class="form-group">
                    <label style="font-weight:600;">Birth Record Availability</label>
                    <div style="display:flex; gap:20px; margin-top:4px;">
                        <label style="font-weight:normal; cursor:pointer;">
                            <input type="radio" name="batch_dob_mode" value="known" checked onclick="toggleDobMode('batch', 'known')"> Exact Birth Date Known
                        </label>
                        <label style="font-weight:normal; cursor:pointer;">
                            <input type="radio" name="batch_dob_mode" value="unknown" onclick="toggleDobMode('batch', 'unknown')"> No Birth Record (Select Stage / Estimate Age)
                        </label>
                    </div>
                </div>

                <!-- Known DOB Field -->
                <div class="form-group" id="batchDobContainer">
                    <label for="batch_dob">Date of Birth</label>
                    <input type="date" id="batch_dob" name="batch_dob" class="form-control" value="<?php echo date('Y-m-d'); ?>" onchange="updateCastrationMinDate('batch')">
                </div>

                <!-- Unknown DOB / Stage Selection Box -->
                <div id="batchStageContainer" style="display:none; background:#F1F8E9; border:1px solid #C5E1A5; padding:14px 16px; border-radius:8px; margin-bottom:1rem;">
                    <p style="margin:0 0 10px; font-weight:700; color:#33691E; font-size:0.9rem;">
                        💡 Select Life Stage &amp; Estimate Age (System will calculate estimated DOB)
                    </p>
                    <div class="form-group">
                        <label for="batch_stage_est">Select Life Stage</label>
                        <select name="batch_stage_est" id="batch_stage_est" class="form-control">
                            <option value="piglet">🐽 Piglet (0 – 4 weeks old)</option>
                            <option value="weaner">🐖 Weaner (4 – 12 weeks old)</option>
                            <option value="grower">📈 Grower (3 – 5 months old)</option>
                            <option value="finisher">🏁 Finisher (5 – 7 months old)</option>
                            <option value="adult">🐗 Adult / Breeder (7+ months old)</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="batch_approx_days">Approximate Age in Days (Optional)</label>
                        <input type="number" name="batch_approx_days" id="batch_approx_days" class="form-control" placeholder="e.g. 14 for 2 weeks old, 56 for 8 weeks old" onchange="updateCastrationMinDate('batch')">
                        <small style="color:#558B2F; display:block; margin-top:4px;">Default age midpoint will be used if left blank.</small>
                    </div>
                </div>

                <div style="margin-top: 1.8rem; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success" style="padding: 0.6rem 1.5rem;">Save Batch Pigs</button>
                    <a href="pigs.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active-tab'));
    if (tab === 'single') {
        document.querySelector('.tab-btn:first-child').classList.add('active-tab');
        document.getElementById('singlePigFormSection').style.display = 'block';
        document.getElementById('batchPigFormSection').style.display = 'none';
    } else {
        document.querySelector('.tab-btn:last-child').classList.add('active-tab');
        document.getElementById('singlePigFormSection').style.display = 'none';
        document.getElementById('batchPigFormSection').style.display = 'block';
    }
}

function toggleSexFields() {
    const sex = document.getElementById('sex').value;
    const grp = document.getElementById('maleCastrationGroup');
    grp.style.display = (sex === 'Male') ? 'block' : 'none';
}

function toggleCastrationDate() {
    const val = document.getElementById('castratedSelect').value;
    const grp = document.getElementById('castrationDateGroup');
    grp.style.display = (val === '1') ? 'block' : 'none';
}

function toggleBatchCastrationDate() {
    const val = document.getElementById('batch_castrated').value;
    const grp = document.getElementById('batchCastrationDateGroup');
    grp.style.display = (val === '1') ? 'block' : 'none';
}

function toggleDobMode(prefix, mode) {
    const dobCont = document.getElementById(prefix + 'DobContainer');
    const stageCont = document.getElementById(prefix + 'StageContainer');
    if (mode === 'known') {
        dobCont.style.display = 'block';
        stageCont.style.display = 'none';
    } else {
        dobCont.style.display = 'none';
        stageCont.style.display = 'block';
    }
    updateCastrationMinDate(prefix);
}

function updateCastrationMinDate(prefix) {
    const modeRad = document.querySelector(`input[name="${prefix === 'single' ? 'dob_mode' : 'batch_dob_mode'}"]:checked`);
    const mode = modeRad ? modeRad.value : 'known';
    let dobVal = '';

    if (mode === 'known') {
        dobVal = document.getElementById(prefix === 'single' ? 'dob' : 'batch_dob').value;
    } else {
        // Calculate estimated date
        const stage = document.getElementById(prefix === 'single' ? 'stage_est' : 'batch_stage_est').value;
        const approxDaysInput = document.getElementById(prefix === 'single' ? 'approx_days' : 'batch_approx_days').value;
        let days = 14;
        if (approxDaysInput && !isNaN(approxDaysInput) && approxDaysInput >= 0) {
            days = parseInt(approxDaysInput);
        } else {
            if (stage === 'weaner') days = 56;
            else if (stage === 'grower') days = 118;
            else if (stage === 'finisher') days = 180;
            else if (stage === 'adult') days = 240;
        }
        const d = new Date();
        d.setDate(d.getDate() - days);
        dobVal = d.toISOString().split('T')[0];
    }

    const castDateInput = document.getElementById(prefix === 'single' ? 'castration_date' : 'batch_castration_date');
    if (castDateInput && dobVal) {
        castDateInput.min = dobVal;
    }
}

function updateSingleTagHint() {
    const source = document.getElementById('source').value;
    const dam = document.getElementById('dam').value;
    const hint = document.getElementById('tagHint');
    if (source === 'External Purchase') {
        hint.innerText = "💡 Leave blank to auto-generate EXT-XXX. Official tag can be entered.";
    } else if (dam) {
        hint.innerText = `💡 Leave blank to inherit Mother's tag: ${dam}-1, ${dam}-2...`;
    } else {
        hint.innerText = "💡 Leave blank to auto-generate PIG-XXX.";
    }
}

function updateBatchTagHint() {
    const source = document.getElementById('batch_source').value;
    const dam = document.getElementById('batch_dam').value;
    const hint = document.getElementById('batchTagHint');
    if (source === 'External Purchase') {
        hint.innerText = "💡 External pigs will inherit EXT-1, EXT-2... or custom prefix.";
    } else if (dam) {
        hint.innerText = `💡 Litters will inherit Mother's tag: ${dam}-1, ${dam}-2, ${dam}-3...`;
    } else {
        hint.innerText = "💡 Batch will auto-generate PIG-1, PIG-2... or custom prefix.";
    }
}

function updateBatchTotal() {
    const m = parseInt(document.getElementById('males_count').value) || 0;
    const f = parseInt(document.getElementById('females_count').value) || 0;
    document.getElementById('batchTotalDisplay').innerText = m + f;
}

// CLIENT-SIDE VALIDATION: Castration Date >= DOB
function validateSingleForm() {
    const sex = document.getElementById('sex').value;
    const castrated = document.getElementById('castratedSelect').value;
    const castDate = document.getElementById('castration_date').value;
    const mode = document.querySelector('input[name="dob_mode"]:checked').value;
    
    let dob = '';
    if (mode === 'known') {
        dob = document.getElementById('dob').value;
    } else {
        const stage = document.getElementById('stage_est').value;
        const approxDaysInput = document.getElementById('approx_days').value;
        let days = 14;
        if (approxDaysInput && !isNaN(approxDaysInput) && approxDaysInput >= 0) days = parseInt(approxDaysInput);
        else {
            if (stage === 'weaner') days = 56;
            else if (stage === 'grower') days = 118;
            else if (stage === 'finisher') days = 180;
            else if (stage === 'adult') days = 240;
        }
        const d = new Date();
        d.setDate(d.getDate() - days);
        dob = d.toISOString().split('T')[0];
    }

    if (sex === 'Male' && castrated === '1' && castDate && dob) {
        if (castDate < dob) {
            alert(`Error: Castration date (${castDate}) cannot be earlier than Date of Birth (${dob}).`);
            return false;
        }
    }
    return true;
}

function validateBatchForm() {
    const m = parseInt(document.getElementById('males_count').value) || 0;
    const f = parseInt(document.getElementById('females_count').value) || 0;
    if (m + f < 1) {
        alert('Please specify at least 1 Male or 1 Female pig to register.');
        return false;
    }

    const castrated = document.getElementById('batch_castrated').value;
    const castDate = document.getElementById('batch_castration_date').value;
    const mode = document.querySelector('input[name="batch_dob_mode"]:checked').value;

    let dob = '';
    if (mode === 'known') {
        dob = document.getElementById('batch_dob').value;
    } else {
        const stage = document.getElementById('batch_stage_est').value;
        const approxDaysInput = document.getElementById('batch_approx_days').value;
        let days = 14;
        if (approxDaysInput && !isNaN(approxDaysInput) && approxDaysInput >= 0) days = parseInt(approxDaysInput);
        else {
            if (stage === 'weaner') days = 56;
            else if (stage === 'grower') days = 118;
            else if (stage === 'finisher') days = 180;
            else if (stage === 'adult') days = 240;
        }
        const d = new Date();
        d.setDate(d.getDate() - days);
        dob = d.toISOString().split('T')[0];
    }

    if (m > 0 && castrated === '1' && castDate && dob) {
        if (castDate < dob) {
            alert(`Error: Male Castration date (${castDate}) cannot be earlier than Date of Birth (${dob}).`);
            return false;
        }
    }
    return true;
}

// Initial script execution
updateSingleTagHint();
updateBatchTagHint();
updateBatchTotal();
updateCastrationMinDate('single');
updateCastrationMinDate('batch');
</script>

<?php include 'includes/footer.php'; ?>
