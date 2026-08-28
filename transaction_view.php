<?php
require_once 'db.php';
requireLogin();

$transId = (int)($_GET['id'] ?? 0);

if (!$transId) {
    header("Location: reports.php");
    exit();
}

// Fetch transaction record with associated pig details if available
$stmt = $pdo->prepare("SELECT s.*, p.id as pig_db_id, p.breed, p.sex, p.stage, p.dob, p.source as pig_source 
                        FROM sales s 
                        LEFT JOIN pigs p ON s.reference_id = p.tag_no 
                        WHERE s.id = ?");
$stmt->execute([$transId]);
$trans = $stmt->fetch();

if (!$trans) {
    header("Location: reports.php?error=trans_not_found");
    exit();
}

$type = $trans['type'] ?? '';
$isMeat = ($type === 'meat_sale');
$isLiveSale = ($type === 'live_pig');
$isPurchase = in_array($type, ['purchase', 'pig_purchase', 'external_purchase']);

$typeTitle = match($type) {
    'live_pig'                      => '🐖 Live Pig Sale Transaction',
    'meat_sale'                     => '🥩 Meat / Pork Sale Record',
    'purchase','pig_purchase',
    'external_purchase'             => '💰 External Pig Purchase & Acquisition',
    default                         => '💳 Transaction Record: ' . ucfirst(str_replace('_', ' ', $type))
};

$typeBadgeClass = match($type) {
    'live_pig'                      => 'badge-sold',
    'meat_sale'                     => 'badge-meat',
    'purchase','pig_purchase',
    'external_purchase'             => 'badge-purchase',
    default                         => 'badge-active'
};

$amount = (float)($trans['amount'] ?? 0);
$weight = !empty($trans['weight']) ? (float)$trans['weight'] : null;
$pricePerKg = ($weight && $weight > 0 && $amount > 0) ? ($amount / $weight) : null;

include 'includes/header.php';
?>

<div class="dashboard-wrapper">

    <!-- Printable Invoice Header (Hidden on screen, visible on print) -->
    <div class="print-only-header" style="display:none;">
        <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2E7D32; padding-bottom: 12px;">
            <h2 style="color: #2E7D32; margin: 0;">Msantha Investments &amp; General Suppliers (MIGS)</h2>
            <p style="margin: 2px 0; font-size: 0.9rem; color: #555;">Liwonde, Machinga, Malawi | Tel: +265 888 880 057</p>
            <h3 style="margin: 8px 0 0; text-transform: uppercase;"><?php echo $typeTitle; ?></h3>
        </div>
    </div>

    <!-- Transaction Hero Card -->
    <div class="pig-hero-card" style="border-left: 5px solid <?php echo $isPurchase ? '#C62828' : ($isMeat ? '#E65100' : '#2E7D32'); ?>;">
        <div class="pig-hero-top">
            <div class="pig-avatar-wrap">
                <div class="pig-avatar" style="background: <?php echo $isPurchase ? '#FFEBEE' : ($isMeat ? '#FFF3E0' : '#E8F5E9'); ?>; border-color: <?php echo $isPurchase ? '#FFCDD2' : ($isMeat ? '#FFE082' : '#C8E6C9'); ?>;">
                    <?php echo $isPurchase ? '💰' : ($isMeat ? '🥩' : '🐖'); ?>
                </div>
            </div>
            <div class="pig-hero-info">
                <div class="pig-hero-badge-row">
                    <span class="pig-badge <?php echo $typeBadgeClass; ?>"><?php echo $typeTitle; ?></span>
                    <span class="pig-badge" style="background:#ECEFF1; color:#37474F;">Ref #<?php echo htmlspecialchars($trans['reference_id'] ?: 'N/A'); ?></span>
                </div>
                <h2 class="pig-hero-title">
                    <span style="color: <?php echo $isPurchase ? '#C62828' : '#2E7D32'; ?>;">
                        <?php echo $isPurchase ? '- ' : '+ '; ?>MWK <?php echo number_format($amount, 2); ?>
                    </span>
                </h2>
                <p class="pig-hero-meta">
                    <strong>Transaction Date:</strong> <?php echo date('d M Y', strtotime($trans['date'])); ?> · 
                    <span>Party: <strong><?php echo htmlspecialchars($trans['buyer_info'] ?: 'General / Unspecified'); ?></strong></span>
                </p>
            </div>
        </div>

        <!-- Quick KPI Bar for Transaction -->
        <div class="pig-hero-kpi-grid">
            <div class="pig-kpi-pill">
                <span class="pig-kpi-icon">📅</span>
                <div>
                    <span class="pig-kpi-label">Date</span>
                    <strong class="pig-kpi-val"><?php echo htmlspecialchars($trans['date']); ?></strong>
                </div>
            </div>
            <div class="pig-kpi-pill">
                <span class="pig-kpi-icon">⚖️</span>
                <div>
                    <span class="pig-kpi-label"><?php echo $isMeat ? 'Carcass / Meat' : 'Weight'; ?></span>
                    <strong class="pig-kpi-val"><?php echo $weight ? htmlspecialchars($weight) . ' kg' : '—'; ?></strong>
                </div>
            </div>
            <div class="pig-kpi-pill">
                <span class="pig-kpi-icon">💵</span>
                <div>
                    <span class="pig-kpi-label">Price per kg</span>
                    <strong class="pig-kpi-val"><?php echo $pricePerKg ? 'MWK ' . number_format($pricePerKg, 2) : '—'; ?></strong>
                </div>
            </div>
            <div class="pig-kpi-pill">
                <span class="pig-kpi-icon">🐖</span>
                <div>
                    <span class="pig-kpi-label">Pig Ear Tag</span>
                    <strong class="pig-kpi-val">#<?php echo htmlspecialchars($trans['reference_id'] ?: 'N/A'); ?></strong>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pig-actions-bar">
            <button class="btn btn-primary pig-action-btn" onclick="window.print()">🖨️ Print Receipt / Invoice</button>
            <?php if (!empty($trans['pig_db_id'])): ?>
                <a href="pig_view.php?id=<?php echo $trans['pig_db_id']; ?>" class="btn btn-success pig-action-btn">🐖 View Pig #<?php echo htmlspecialchars($trans['reference_id']); ?></a>
            <?php endif; ?>
            <a href="reports.php" class="btn btn-outline pig-action-btn">📈 Back to Reports</a>
            <a href="dashboard.php" class="btn btn-outline pig-action-btn">&larr; Dashboard</a>
        </div>
    </div>

    <!-- Main Spec Breakdown Card -->
    <div class="dashboard-content" style="grid-template-columns: 1fr; margin-top: 20px;">
        <div class="card pig-spec-card">
            <div class="pig-spec-card-header">
                <h3 style="margin: 0; color: var(--primary-color);">🧾 Complete Transaction Details</h3>
                <span style="font-size: 0.85rem; color: var(--text-muted);">Record ID: #TRX-<?php echo str_pad($trans['id'], 5, '0', STR_PAD_LEFT); ?></span>
            </div>

            <div class="pig-spec-grid">
                <div class="pig-spec-item">
                    <span class="pig-spec-label">Transaction Category</span>
                    <span class="pig-spec-value">
                        <strong style="color: <?php echo $isPurchase ? '#C62828' : 'var(--primary-color)'; ?>;">
                            <?php echo $typeTitle; ?>
                        </strong>
                    </span>
                </div>

                <div class="pig-spec-item">
                    <span class="pig-spec-label">Transaction Date</span>
                    <span class="pig-spec-value"><strong><?php echo date('l, d F Y', strtotime($trans['date'])); ?></strong></span>
                </div>

                <div class="pig-spec-item">
                    <span class="pig-spec-label"><?php echo $isPurchase ? 'Total Purchase Cost' : 'Total Revenue Collected'; ?></span>
                    <span class="pig-spec-value" style="font-size: 1.25rem; font-weight: 800; color: <?php echo $isPurchase ? '#C62828' : 'var(--primary-color)'; ?>;">
                        MWK <?php echo number_format($amount, 2); ?>
                    </span>
                </div>

                <div class="pig-spec-item">
                    <span class="pig-spec-label"><?php echo $isMeat ? 'Meat / Carcass Weight' : 'Pig Weight (kg)'; ?></span>
                    <span class="pig-spec-value">
                        <?php if ($weight): ?>
                            <strong style="font-size: 1.1rem; color: #1565C0;">⚖️ <?php echo number_format($weight, 2); ?> kg</strong>
                        <?php else: ?>
                            <span style="color: var(--text-muted); font-style: italic;">Weight not specified</span>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if ($isMeat || ($weight && $pricePerKg)): ?>
                <div class="pig-spec-item">
                    <span class="pig-spec-label">Calculated Price per Kilogram</span>
                    <span class="pig-spec-value">
                        <?php if ($pricePerKg): ?>
                            <strong style="color: #2E7D32; font-size: 1.05rem;">MWK <?php echo number_format($pricePerKg, 2); ?> / kg</strong>
                        <?php else: ?>
                            <span style="color: var(--text-muted); font-style: italic;">N/A</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>

                <div class="pig-spec-item">
                    <span class="pig-spec-label"><?php echo $isPurchase ? 'Supplier / Vendor / Farm' : 'Customer / Butchery / Buyer Information'; ?></span>
                    <span class="pig-spec-value">
                        <strong><?php echo htmlspecialchars($trans['buyer_info'] ?: 'Not recorded'); ?></strong>
                    </span>
                </div>

                <div class="pig-spec-item">
                    <span class="pig-spec-label">Reference Pig Ear Tag</span>
                    <span class="pig-spec-value">
                        <?php if (!empty($trans['reference_id'])): ?>
                            <span style="font-size: 1.1rem; font-weight: 700; color: var(--primary-color);">#<?php echo htmlspecialchars($trans['reference_id']); ?></span>
                            <?php if (!empty($trans['pig_db_id'])): ?>
                                <a href="pig_view.php?id=<?php echo $trans['pig_db_id']; ?>" class="btn btn-outline" style="padding: 2px 8px; font-size: 0.72rem; margin-left: 8px;">View Pig Profile &rarr;</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">None</span>
                        <?php endif; ?>
                    </span>
                </div>

                <?php if (!empty($trans['breed']) || !empty($trans['sex'])): ?>
                <div class="pig-spec-item">
                    <span class="pig-spec-label">Pig Details at Record</span>
                    <span class="pig-spec-value">
                        <span>Breed: <strong><?php echo htmlspecialchars($trans['breed'] ?: 'N/A'); ?></strong></span> · 
                        <span>Sex: <strong><?php echo htmlspecialchars($trans['sex'] ?: 'N/A'); ?></strong></span> · 
                        <span>Stage: <strong><?php echo ucfirst(htmlspecialchars($trans['stage'] ?: 'N/A')); ?></strong></span>
                    </span>
                </div>
                <?php endif; ?>

                <div class="pig-spec-item" style="grid-column: 1 / -1;">
                    <span class="pig-spec-label">Transaction Remarks &amp; Notes</span>
                    <span class="pig-spec-value" style="font-style: italic; color: #444; line-height: 1.5;">
                        <?php echo nl2br(htmlspecialchars($trans['remarks'] ?: 'No additional notes provided.')); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body {
        background: #fff !important;
    }
    .sidebar, .topbar, .pig-actions-bar, .mobile-toggle-btn, .sidebar-overlay, .landing-header, .modern-app-footer {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .content-area {
        padding: 0 !important;
    }
    .print-only-header {
        display: block !important;
    }
    .pig-hero-card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        padding: 15px !important;
    }
    .pig-spec-card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
