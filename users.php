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
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password) || empty($full_name)) {
        $error = "Username, Full Name, and Password are required.";
    } else if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } else {
        try {
            $pdo->beginTransaction();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $role, $full_name, $email ?: null, $phone]);
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
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($targetId) {
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please provide a valid email address.";
        } else {
            try {
                $pdo->beginTransaction();
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, email = ?, phone = ?, password = ? WHERE id = ?");
                    $stmt->execute([$full_name, $role, $email ?: null, $phone, $hash, $targetId]);
                    logActivity($pdo, 'user_updated', "Updated user account '$full_name' (ID #$targetId) details and reset password");
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, role = ?, email = ?, phone = ? WHERE id = ?");
                    $stmt->execute([$full_name, $role, $email ?: null, $phone, $targetId]);
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
}

// Fetch all users
$users = $pdo->query("SELECT * FROM users ORDER BY id ASC")->fetchAll();

include 'includes/header.php';
?>

<div class="dashboard-wrapper">
    <div class="dashboard-header user-mgmt-header">
        <div>
            <h2>User Account Management</h2>
            <p>Admin control panel for managing farm system users and permissions.</p>
        </div>
        <button class="btn btn-primary user-add-btn" onclick="openModal('addUserModal')">+ Add New User Account</button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card user-mgmt-card">
        <!-- Desktop & Tablet Table View -->
        <div class="table-wrapper user-table-wrapper">
            <table class="data-table striped middle user-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr class="tbl-empty"><td colspan="7">No user accounts found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>#<?php echo $u['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                <td>
                                    <span class="badge" style="padding: 4px 8px; border-radius: 4px; font-weight: 600; background: <?php echo $u['role'] === 'admin' ? '#E3F2FD' : '#F3E5F5'; ?>; color: <?php echo $u['role'] === 'admin' ? '#1565C0' : '#7B1FA2'; ?>;">
                                        <?php echo ucfirst(htmlspecialchars($u['role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($u['email'] ?: '—'); ?></td>
                                <td><?php echo htmlspecialchars($u['phone'] ?: 'N/A'); ?></td>
                                <td style="text-align: right;">
                                    <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; white-space: nowrap;" 
                                        onclick='editUser(<?php echo json_encode($u); ?>)'>✏️ Edit / Reset Password</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Dedicated Mobile Card List (Screen Width <= 768px) -->
        <div class="user-cards-mobile">
            <?php if (empty($users)): ?>
                <div class="text-center" style="padding: 2rem; color: var(--text-muted);">No user accounts found.</div>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <div class="user-card-item">
                        <div class="user-card-header">
                            <div>
                                <strong class="user-card-username"><?php echo htmlspecialchars($u['username']); ?></strong>
                                <span class="user-card-id">#<?php echo $u['id']; ?></span>
                            </div>
                            <span class="badge" style="padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 0.75rem; background: <?php echo $u['role'] === 'admin' ? '#E3F2FD' : '#F3E5F5'; ?>; color: <?php echo $u['role'] === 'admin' ? '#1565C0' : '#7B1FA2'; ?>;">
                                <?php echo ucfirst(htmlspecialchars($u['role'])); ?>
                            </span>
                        </div>
                        <div class="user-card-body">
                            <div class="user-card-row">
                                <span class="user-card-label">Full Name:</span>
                                <span class="user-card-val"><?php echo htmlspecialchars($u['full_name']); ?></span>
                            </div>
                            <div class="user-card-row">
                                <span class="user-card-label">Email:</span>
                                <span class="user-card-val"><?php echo htmlspecialchars($u['email'] ?: '—'); ?></span>
                            </div>
                            <div class="user-card-row">
                                <span class="user-card-label">Phone:</span>
                                <span class="user-card-val"><?php echo htmlspecialchars($u['phone'] ?: 'N/A'); ?></span>
                            </div>
                        </div>
                        <div class="user-card-footer">
                            <button class="btn btn-outline btn-block" style="padding: 0.55rem 0.8rem; font-size: 0.82rem; text-align: center;"
                                onclick='editUser(<?php echo json_encode($u); ?>)'>✏️ Edit / Reset Password</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
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
                <label for="new_email">Email Address</label>
                <input type="email" id="new_email" name="email" class="form-control" placeholder="e.g. jbanda@msanthapigs.mw">
            </div>
            <div class="form-group">
                <label for="new_phone">Phone Number</label>
                <input type="text" id="new_phone" name="phone" class="form-control" placeholder="e.g. +265 888 123456">
            </div>
            <div class="form-group">
                <label for="new_pass">Initial Password</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="new_pass" name="password" class="form-control" required style="padding-right: 42px;">
                    <button type="button" onclick="togglePassVisibility('new_pass', this)" aria-label="Show password" title="Show password" style="position: absolute; right: 8px; background: none; border: none; cursor: pointer; font-size: 1.1rem; color: #6b7280; padding: 6px; line-height: 1; display: flex; align-items: center; justify-content: center; z-index: 2;"><span>👁️</span></button>
                </div>
            </div>
            <div class="modal-actions">
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
                <label for="edit_email">Email Address</label>
                <input type="email" id="edit_email" name="email" class="form-control" placeholder="e.g. user@msanthapigs.mw">
            </div>
            <div class="form-group">
                <label for="edit_phone">Phone Number</label>
                <input type="text" id="edit_phone" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label for="edit_pass">Reset Password (Optional)</label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="password" id="edit_pass" name="password" class="form-control" placeholder="Leave empty to keep current password" style="padding-right: 42px;">
                    <button type="button" onclick="togglePassVisibility('edit_pass', this)" aria-label="Show password" title="Show password" style="position: absolute; right: 8px; background: none; border: none; cursor: pointer; font-size: 1.1rem; color: #6b7280; padding: 6px; line-height: 1; display: flex; align-items: center; justify-content: center; z-index: 2;"><span>👁️</span></button>
                </div>
            </div>
            <div class="modal-actions">
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
        document.getElementById('edit_email').value = user.email || '';
        document.getElementById('edit_phone').value = user.phone || '';
        document.getElementById('edit_pass').value = '';
        openModal('editUserModal');
    }

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

<?php include 'includes/footer.php'; ?>
