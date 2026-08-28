<?php
require_once 'db.php';
requireLogin();

$userId = $_SESSION['user_id'];
$msg = null;
$error = null;

// Fetch existing user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name)) {
        $error = "Full Name is required.";
    } else if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address (e.g. name@example.com).";
    } else if (!empty($new_password) && strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else if (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } else {
        try {
            $pdo->beginTransaction();

            if (!empty($new_password)) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                $updateStmt->execute([$full_name, $email ?: null, $phone, $hash, $userId]);
                logActivity($pdo, 'password_changed', "Updated account password and profile details for '$full_name'");
            } else {
                $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
                $updateStmt->execute([$full_name, $email ?: null, $phone, $userId]);
                logActivity($pdo, 'profile_updated', "Updated profile details for '$full_name' (Email: " . ($email ?: 'None') . ", Phone: $phone)");
            }

            $pdo->commit();
            $_SESSION['user_fullname'] = $full_name;
            $msg = "Profile updated successfully!";

            // Refresh user details
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}

// Compute initials for the user avatar
$words = preg_split('/\s+/', trim($user['full_name'] ?: $user['username']));
$initials = '';
foreach (array_slice($words, 0, 2) as $w) {
    if (!empty($w)) {
        $initials .= strtoupper(mb_substr($w, 0, 1));
    }
}
if (empty($initials)) $initials = 'U';

$isAdmin = (($user['role'] ?? '') === 'admin');

include 'includes/header.php';
?>

<div class="dashboard-wrapper">

    <!-- Profile Hero Card Banner -->
    <div class="pig-hero-card" style="border-left: 5px solid var(--primary-color);">
        <div class="pig-hero-top">
            <div class="pig-avatar-wrap" style="position: relative;">
                <div class="pig-avatar" style="background: <?php echo $isAdmin ? '#E8F5E9' : '#E3F2FD'; ?>; border-color: <?php echo $isAdmin ? '#C8E6C9' : '#BBDEFB'; ?>; color: <?php echo $isAdmin ? '#1B5E20' : '#1565C0'; ?>; font-weight: 800; font-size: 1.4rem;">
                    <?php echo htmlspecialchars($initials); ?>
                </div>
                <span style="position: absolute; bottom: 2px; right: 2px; width: 14px; height: 14px; background: #4CAF50; border: 2px solid #fff; border-radius: 50%;" title="Session Active"></span>
            </div>
            <div class="pig-hero-info">
                <div class="pig-hero-badge-row">
                    <span class="pig-badge <?php echo $isAdmin ? 'badge-active' : 'badge-sold'; ?>">
                        <?php echo $isAdmin ? '🛡️ Administrator' : '📋 Farm Clerk'; ?>
                    </span>
                    <span class="pig-badge" style="background: #ECEFF1; color: #37474F;">@<?php echo htmlspecialchars($user['username']); ?></span>
                    <span class="pig-badge" style="background: #FFF3E0; color: #E65100;">ID #USR-<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></span>
                </div>
                <h2 class="pig-hero-title"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                <p class="pig-hero-meta">
                    <span>✉️ <strong><?php echo htmlspecialchars($user['email'] ?? 'No email set'); ?></strong></span> · 
                    <span>📱 <strong><?php echo htmlspecialchars($user['phone'] ?: 'No phone set'); ?></strong></span>
                </p>
            </div>
        </div>

        <!-- Quick Summary Bar -->
        <div class="pig-hero-kpi-grid">
            <div class="pig-kpi-pill">
                <span class="pig-kpi-icon">👤</span>
                <div>
                    <span class="pig-kpi-label">Username</span>
                    <strong class="pig-kpi-val"><?php echo htmlspecialchars($user['username']); ?></strong>
                </div>
            </div>
            <div class="pig-kpi-pill">
                <span class="pig-kpi-icon">🛡️</span>
                <div>
                    <span class="pig-kpi-label">System Role</span>
                    <strong class="pig-kpi-val" style="text-transform: capitalize;"><?php echo htmlspecialchars($user['role']); ?></strong>
                </div>
            </div>
            <div class="pig-kpi-pill">
                <span class="pig-kpi-icon">✉️</span>
                <div>
                    <span class="pig-kpi-label">Email Address</span>
                    <strong class="pig-kpi-val"><?php echo htmlspecialchars($user['email'] ?: 'Not configured'); ?></strong>
                </div>
            </div>
            <div class="pig-kpi-pill">
                <span class="pig-kpi-icon">🔒</span>
                <div>
                    <span class="pig-kpi-label">Session Status</span>
                    <strong class="pig-kpi-val" style="color: #2E7D32;">🟢 Active &amp; Secured</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Notifications -->
    <?php if ($msg): ?>
        <div class="alert alert-success" style="margin-bottom: 20px;">
            <strong>✓ Success:</strong> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Two-Column Profile & Settings Layout -->
    <div class="dashboard-content" style="grid-template-columns: 1.4fr 1fr; gap: 20px; align-items: start;">
        
        <!-- Left Column: Personal Information & Password Form -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Personal Details Card -->
            <div class="card">
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 12px; margin-bottom: 15px;">
                    <h3 style="margin: 0; color: var(--primary-color);">👤 Personal &amp; Contact Information</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 3px 0 0;">Update your display name, contact phone, and official email address.</p>
                </div>

                <form action="profile.php" method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Username (System ID)</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="opacity: 0.75; cursor: not-allowed;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Assigned System Role</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" disabled style="opacity: 0.75; cursor: not-allowed;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name <span style="color: #C62828;">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" placeholder="e.g. John Banda" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span style="font-size: 0.8rem; color: var(--text-muted);">(Optional for notifications &amp; alerts)</span></label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="e.g. user@msanthapigs.mw">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g. +265 888 123 456">
                    </div>

                    <hr style="margin: 20px 0 15px; border: 0; border-top: 1px solid var(--border-color);">

                    <h4 style="margin: 0 0 6px; color: var(--primary-color); font-size: 1rem;">🔒 Change Password (Optional)</h4>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 12px;">Leave both password fields blank if you do not want to alter your current password.</p>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password (min. 6 chars)" style="padding-right: 42px;">
                            <button type="button" onclick="togglePassVisibility('new_password', this)" aria-label="Show password" title="Show password" style="position: absolute; right: 8px; background: none; border: none; cursor: pointer; font-size: 1.1rem; color: #6b7280; padding: 6px; line-height: 1; display: flex; align-items: center; justify-content: center; z-index: 2;">
                                <span>👁️</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password" style="padding-right: 42px;">
                            <button type="button" onclick="togglePassVisibility('confirm_password', this)" aria-label="Show password" title="Show password" style="position: absolute; right: 8px; background: none; border: none; cursor: pointer; font-size: 1.1rem; color: #6b7280; padding: 6px; line-height: 1; display: flex; align-items: center; justify-content: center; z-index: 2;">
                                <span>👁️</span>
                            </button>
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.65rem 1.4rem; font-size: 0.95rem;">💾 Save Profile Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: Theme & Security Overview -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            
            <!-- Appearance & Theme Card -->
            <div class="card">
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 12px;">
                    <h3 style="margin: 0; color: var(--primary-color);">🎨 Display &amp; Theme</h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 2px 0 0;">Select your visual interface display mode.</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <label style="cursor: pointer; border: 2px solid var(--border-color); border-radius: 12px; padding: 14px 10px; display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; transition: all 0.2s ease; background: var(--bg-color);" id="themeOptionLight">
                        <input type="radio" name="theme_choice" value="light" style="margin-bottom: 2px;" onchange="if(window.migsSetTheme) window.migsSetTheme('light')">
                        <span style="font-size: 1.8rem;">☀️</span>
                        <strong style="font-size: 0.9rem;">Light Mode</strong>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Clean daylight display</span>
                    </label>

                    <label style="cursor: pointer; border: 2px solid var(--border-color); border-radius: 12px; padding: 14px 10px; display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; transition: all 0.2s ease; background: var(--bg-color);" id="themeOptionDark">
                        <input type="radio" name="theme_choice" value="dark" style="margin-bottom: 2px;" onchange="if(window.migsSetTheme) window.migsSetTheme('dark')">
                        <span style="font-size: 1.8rem;">🌙</span>
                        <strong style="font-size: 0.9rem;">Dark Mode</strong>
                        <span style="font-size: 0.72rem; color: var(--text-muted);">Sleek contrast &amp; night ease</span>
                    </label>
                </div>
            </div>

            <!-- Security & Session Summary Card -->
            <div class="card">
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 12px;">
                    <h3 style="margin: 0; color: var(--primary-color);">🛡️ Session &amp; Permissions</h3>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 2px 0 0;">System protection &amp; active device policy.</p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: var(--bg-color); border-radius: 8px;">
                        <span style="color: var(--text-muted);">Active Device</span>
                        <strong style="color: #2E7D32;">1 Device Allowed</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: var(--bg-color); border-radius: 8px;">
                        <span style="color: var(--text-muted);">Concurrency Lock</span>
                        <strong style="color: #1565C0;">⚡ Real-Time Active</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: var(--bg-color); border-radius: 8px;">
                        <span style="color: var(--text-muted);">Role Permissions</span>
                        <strong><?php echo $isAdmin ? 'Full System Access' : 'Farm Records Entry'; ?></strong>
                    </div>
                </div>

                <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 8px;">
                    <?php if ($isAdmin): ?>
                        <a href="users.php" class="btn btn-outline" style="text-align: center; justify-content: center; font-size: 0.82rem;">⚙️ Manage User Accounts</a>
                        <a href="logs.php" class="btn btn-outline" style="text-align: center; justify-content: center; font-size: 0.82rem;">📋 View System Activity Logs</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-danger" style="text-align: center; justify-content: center; font-size: 0.85rem; font-weight: 600;">🚪 Sign Out of Account</a>
                </div>
            </div>

        </div>

    </div>

    <script>
        function togglePassVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('span');
            if (input && icon) {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.textContent = isPassword ? '🙈' : '👁️';
                btn.title = isPassword ? 'Hide password' : 'Show password';
                btn.setAttribute('aria-label', btn.title);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const currentTheme = localStorage.getItem('migs_theme') || 'light';
            const radio = document.querySelector('input[name="theme_choice"][value="' + currentTheme + '"]');
            if (radio) radio.checked = true;
        });
    </script>
</div>

<?php include 'includes/footer.php'; ?>
