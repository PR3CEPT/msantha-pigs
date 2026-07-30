<?php
require_once 'db.php';
requireLogin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stage = $_POST['stage'] ?? 'adult';
    $tag_no = trim($_POST['tag_no'] ?? '');
    $sex = $_POST['sex'] ?? 'Male';
    $breed = trim($_POST['breed'] ?? 'Large White');
    $dob = $_POST['dob'] ?? date('Y-m-d');
    $sire = trim($_POST['sire'] ?? '');
    $dam = trim($_POST['dam'] ?? '');

    // Auto-generate tag for weaners/piglets if empty
    if (empty($tag_no)) {
        if (in_array($stage, ['weaner', 'piglet'])) {
            if (!empty($dam)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM pigs WHERE dam = ? AND stage IN ('weaner', 'piglet')");
                $stmt->execute([$dam]);
                $count = $stmt->fetchColumn();
                $tag_no = $dam . '-W' . ($count + 1);
            } else {
                $stmt = $pdo->query("SELECT COUNT(*) FROM pigs WHERE stage IN ('weaner', 'piglet')");
                $count = $stmt->fetchColumn();
                $tag_no = 'W-' . sprintf('%03d', $count + 1);
            }
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) FROM pigs WHERE stage = 'adult'");
            $count = $stmt->fetchColumn();
            $tag_no = 'PIG-' . sprintf('%03d', $count + 1);
        }
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO pigs (tag_no, sex, breed, dob, sire, dam, stage) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tag_no, $sex, $breed, $dob, $sire, $dam, $stage]);
        $pdo->commit();
        header("Location: pigs.php");
        exit();
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error saving pig record. Ensure tag number ('" . htmlspecialchars($tag_no) . "') is unique.";
    }
}

// Fetch dams (female pigs)
$dams = $pdo->query("SELECT tag_no FROM pigs WHERE sex = 'Female' AND status = 'active'")->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-header">
        <h2>Add New Pig</h2>
        <p>Register a new piglet, weaner, or adult pig into the farm inventory.</p>
    </div>

    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="pig_form.php" method="POST">
            <div class="form-group">
                <label for="stageSelect">Life Stage</label>
                <select name="stage" class="form-control" id="stageSelect" required>
                    <option value="adult">Adult</option>
                    <option value="weaner">Weaner</option>
                    <option value="piglet">Piglet</option>
                </select>
            </div>
            <div class="form-group" id="tagGroup">
                <label for="tag_no">Ear Tag No (Leave blank to auto-generate ID)</label>
                <input type="text" id="tag_no" name="tag_no" class="form-control" placeholder="e.g. M-001">
            </div>
            <div class="form-group">
                <label for="sex">Sex / Gender</label>
                <select name="sex" id="sex" class="form-control" required>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
            <div class="form-group">
                <label for="breed">Breed</label>
                <input type="text" id="breed" name="breed" class="form-control" placeholder="e.g. Large White, Landrace, Duroc" required>
            </div>
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" id="dob" name="dob" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="sire">Sire Tag (Father)</label>
                <input type="text" id="sire" name="sire" class="form-control" placeholder="Optional sire tag no">
            </div>
            <div class="form-group">
                <label for="dam">Dam Tag (Mother)</label>
                <select name="dam" id="dam" class="form-control">
                    <option value="">-- Select Mother (Optional) --</option>
                    <?php foreach($dams as $d): ?>
                        <option value="<?php echo htmlspecialchars($d['tag_no']); ?>"><?php echo htmlspecialchars($d['tag_no']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-top: 1.5rem; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Save Pig</button>
                <a href="pigs.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    const stageSelect = document.getElementById('stageSelect');
    const tagLabel = document.querySelector('#tagGroup label');

    if (stageSelect && tagLabel) {
        stageSelect.addEventListener('change', function() {
            if (this.value === 'weaner' || this.value === 'piglet') {
                tagLabel.innerHTML = 'Ear Tag No (Auto-generated based on Dam if left empty)';
            } else {
                tagLabel.innerHTML = 'Ear Tag No (Leave blank to auto-generate PIG-XXX)';
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
