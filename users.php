<?php
require_once 'db.php';
requireAdmin();

$msg = null;
$error = null;

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'clerk';
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password) || empty($full_name)) {
        $error = "Username, Full Name, and Password are required.";
    } else {
        try {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $role, $full_name, $phone]);
            logActivity($pdo, 'user_created', "Created new system user account '$username' ('$full_name') with role '" . ucfirst($role) . "'");
            $pdo->commit();
            $msg = "New system user '$username' created successfully!";
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to create user. Username might already exist.";
        }
    }
}

// Handle Reset Password / Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $targetId = $_POST['user_id'] ?? null;
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'clerk';
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($targetId) {
        try {
            $pdo->beginTransaction();
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, phone = ?, password = ? WHERE id = ?");
                $stmt->execute([$full_name, $role, $phone, $hash, $targetId]);
                logActivity($pdo, 'user_updated', "Updated user account '$full_name' (ID #$targetId) details and reset password");
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, phone = ? WHERE id = ?");
                $stmt->execute([$full_name, $role, $phone, $targetId]);
                logActivity($pdo, 'user_updated', "Updated user account '$full_name' (ID #$targetId) details");
            }
            $pdo->commit();
            $msg = "User account updated successfully!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Failed to update user account: " . $e->getMessage();
        }
    }
}

// Fetch all users
$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
            <h2>User Account Management</h2>
            <p>Admin control panel for managing farm system users and permissions.</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addUserModal')">+ Add New User Account</button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-color); text-align: left;">
                    <th style="padding: 1rem;">ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 1rem;"><?php echo $u['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                        <td>
                            <span class="badge" style="padding: 4px 8px; border-radius: 4px; background: <?php echo $u['role'] === 'admin' ? '#E3F2FD' : '#F3E5F5'; ?>; color: <?php echo $u['role'] === 'admin' ? '#1565C0' : '#7B1FA2'; ?>;">
                                <?php echo ucfirst(htmlspecialchars($u['role'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($u['phone'] ?: 'N/A'); ?></td>
                        <td>
                            <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem;" 
                                onclick='editUser(<?php echo json_encode($u); ?>)'>Edit / Reset Password</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add New User -->
<div class="modal" id="addUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New System User</h3>
            <button class="close-btn" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form action="users.php" method="POST">
            <input type="hidden" name="add_user" value="1">
            <div class="form-group">
                <label for="new_username">Username</label>
                <input type="text" id="new_username" name="username" class="form-control" placeholder="e.g. jbanda" required>
            </div>
            <div class="form-group">
                <label for="new_fullname">Full Name</label>
                <input type="text" id="new_fullname" name="full_name" class="form-control" placeholder="e.g. John Banda" required>
            </div>
            <div class="form-group">
                <label for="new_role">System Role</label>
                <select name="role" id="new_role" class="form-control" required>
                    <option value="clerk">Clerk (Farm Operator)</option>
                    <option value="admin">Admin (Full Access)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="new_phone">Phone Number</label>
                <input type="text" id="new_phone" name="phone" class="form-control" placeholder="e.g. +265 888 123456">
            </div>
            <div class="form-group">
                <label for="new_pass">Initial Password</label>
                <input type="password" id="new_pass" name="password" class="form-control" required>
            </div>
            <div style="text-align: right; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User Account</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal" id="editUserModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit User Account</h3>
            <button class="close-btn" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form action="users.php" method="POST">
            <input type="hidden" name="edit_user" value="1">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="edit_username" class="form-control" disabled style="background: #eee;">
            </div>
            <div class="form-group">
                <label for="edit_full_name">Full Name</label>
                <input type="text" id="edit_full_name" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="edit_role">System Role</label>
                <select name="role" id="edit_role" class="form-control" required>
                    <option value="clerk">Clerk</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label for="edit_phone">Phone Number</label>
                <input type="text" id="edit_phone" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_pass">Reset Password (Optional)</label>
                <input type="password" id="edit_pass" name="password" class="form-control" placeholder="Leave empty to keep current password">
            </div>
            <div style="text-align: right; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User Account</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(modalId) {
        document.getElementById(modalId).classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function editUser(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_full_name').value = user.full_name;
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_phone').value = user.phone || '';
        document.getElementById('edit_pass').value = '';
        openModal('editUserModal');
    }
</script>

<?php include 'includes/footer.php'; ?>
