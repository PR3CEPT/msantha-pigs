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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name)) {
        $error = "Full Name is required.";
    } else if (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } else {
        try {
            $pdo->beginTransaction();

            if (!empty($new_password)) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, password = ? WHERE id = ?");
                $updateStmt->execute([$full_name, $phone, $hash, $userId]);
                logActivity($pdo, 'password_changed', "Updated account password and profile details");
            } else {
                $updateStmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
                $updateStmt->execute([$full_name, $phone, $userId]);
                logActivity($pdo, 'profile_updated', "Updated personal user profile details");
            }

            $pdo->commit();
            $_SESSION['user_fullname'] = $full_name;
            $msg = "Profile updated successfully!";

            // Refresh user details
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

include 'includes/header.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-header">
        <h2>My User Profile</h2>
        <p>View and update your personal user account details.</p>
    </div>

    <div class="card" style="max-width: 600px; margin: 0 auto;">
        <?php if ($msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="profile.php" method="POST">
            <div class="form-group">
                <label>Username (System ID)</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background: #eee;">
            </div>
            <div class="form-group">
                <label>System Role</label>
                <input type="text" class="form-control" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" disabled style="background: #eee;">
            </div>
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="e.g. +265 888 123 456">
            </div>
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border-color);">
            <h3>Change Password (Optional)</h3>
            <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 1rem;">Leave password fields empty if you do not wish to change your current password.</p>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password" style="padding-right: 42px;">
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
                <button type="submit" class="btn btn-primary">Save Profile Changes</button>
            </div>
        </form>
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
    </script>
</div>

<?php include 'includes/footer.php'; ?>
