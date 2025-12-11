<?php
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'danger');
    } else {
        $action = $_POST['action'] ?? '';
        $userId = intval($_POST['user_id'] ?? 0);
        
        switch ($action) {
            case 'activate':
                if (!hasPermission('can_activate_users')) {
                    setFlashMessage('You do not have permission to activate users.', 'danger');
                    break;
                }
                $stmt = $db->prepare("UPDATE users SET is_active = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                logActivity('user_activated', "Activated user ID: $userId");
                setFlashMessage('User activated successfully.', 'success');
                break;
                
            case 'deactivate':
                if (!hasPermission('can_activate_users')) {
                    setFlashMessage('You do not have permission to deactivate users.', 'danger');
                    break;
                }
                $stmt = $db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
                $stmt->execute([$userId]);
                logActivity('user_deactivated', "Deactivated user ID: $userId");
                setFlashMessage('User deactivated successfully.', 'success');
                break;
                
            case 'lock':
                if (!hasPermission('can_unlock_users')) {
                    setFlashMessage('You do not have permission to lock users.', 'danger');
                    break;
                }
                $stmt = $db->prepare("UPDATE users SET is_locked = 1 WHERE id = ?");
                $stmt->execute([$userId]);
                logActivity('user_locked', "Locked user ID: $userId");
                setFlashMessage('User account locked successfully.', 'success');
                break;
                
            case 'unlock':
                if (!hasPermission('can_unlock_users')) {
                    setFlashMessage('You do not have permission to unlock users.', 'danger');
                    break;
                }
                $stmt = $db->prepare("UPDATE users SET is_locked = 0, failed_attempts = 0, last_failed_attempt = NULL WHERE id = ?");
                $stmt->execute([$userId]);
                logActivity('user_unlocked', "Unlocked user ID: $userId");
                setFlashMessage('User account unlocked successfully.', 'success');
                break;
                
            case 'reset_password':
                if (!hasPermission('can_reset_passwords')) {
                    setFlashMessage('You do not have permission to reset passwords.', 'danger');
                    break;
                }
                $newPassword = password_hash('Password@123', PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$newPassword, $userId]);
                logActivity('password_reset', "Reset password for user ID: $userId");
                setFlashMessage('Password reset to: Password@123', 'success');
                break;
                
            case 'delete':
                if (!hasPermission('can_edit_users')) {
                    setFlashMessage('You do not have permission to delete users.', 'danger');
                    break;
                }
                if ($userId != $_SESSION['user_id']) {
                    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    logActivity('user_deleted', "Deleted user ID: $userId");
                    setFlashMessage('User deleted successfully.', 'success');
                } else {
                    setFlashMessage('You cannot delete your own account.', 'danger');
                }
                break;
        }
        
        redirect('users.php');
    }
}

// Get all users
$stmt = $db->query("
    SELECT *
    FROM users
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();

// Get all restrictions
$stmt = $db->query("SELECT * FROM user_restrictions");
$restrictions = $stmt->fetchAll();
$restrictionMap = [];
foreach ($restrictions as $restriction) {
    $restrictionMap[$restriction['user_id']] = $restriction;
}

// Merge restrictions into users
foreach ($users as &$user) {
    if (isset($restrictionMap[$user['id']])) {
        $user['can_add'] = $restrictionMap[$user['id']]['can_add'];
        $user['can_edit'] = $restrictionMap[$user['id']]['can_edit'];
        $user['can_view'] = $restrictionMap[$user['id']]['can_view'];
        $user['can_delete'] = $restrictionMap[$user['id']]['can_delete'];
        $user['can_edit_users'] = $restrictionMap[$user['id']]['can_edit_users'];
        $user['can_activate_users'] = $restrictionMap[$user['id']]['can_activate_users'];
        $user['can_unlock_users'] = $restrictionMap[$user['id']]['can_unlock_users'];
        $user['can_reset_passwords'] = $restrictionMap[$user['id']]['can_reset_passwords'];
    } else {
        $user['can_add'] = 0;
        $user['can_edit'] = 0;
        $user['can_view'] = 0;
        $user['can_delete'] = 0;
        $user['can_edit_users'] = 0;
        $user['can_activate_users'] = 0;
        $user['can_unlock_users'] = 0;
        $user['can_reset_passwords'] = 0;
    }
}

$pageTitle = 'Manage Users';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-people"></i> Manage Users</h2>
                <a href="add-user.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add New User
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto; overflow-x: auto;">
                <table id="usersTable" class="table table-hover">
                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>User Level</th>
                            <th>Status</th>
                            <th>Failed Attempts</th>
                            <th>Restrictions</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if ($user['user_level'] === 'admin'): ?>
                                    <span class="badge bg-danger">Admin</span>
                                <?php else: ?>
                                    <span class="badge bg-info">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_locked']): ?>
                                    <span class="badge status-locked"><i class="bi bi-lock"></i> Locked</span>
                                <?php elseif ($user['is_active']): ?>
                                    <span class="badge status-active"><i class="bi bi-check-circle"></i> Active</span>
                                <?php else: ?>
                                    <span class="badge status-inactive"><i class="bi bi-x-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['failed_attempts'] > 0): ?>
                                    <span class="badge bg-warning"><?= $user['failed_attempts'] ?>/3</span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small>
                                    <?php if ($user['user_level'] === 'admin'): ?>
                                        <span class="badge bg-secondary">Full Access</span>
                                    <?php else: ?>
                                        <?php if ($user['can_add']): ?><span class="badge bg-success">Add</span><?php endif; ?>
                                        <?php if ($user['can_edit']): ?><span class="badge bg-primary">Edit</span><?php endif; ?>
                                        <?php if ($user['can_view']): ?><span class="badge bg-info">View</span><?php endif; ?>
                                        <?php if ($user['can_delete']): ?><span class="badge bg-danger">Delete</span><?php endif; ?>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <?php if (hasPermission('can_edit_users')): ?>
                                    <a href="edit-user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit User">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php else: ?>
                                    <a href="#" class="btn btn-sm btn-outline-primary" disabled title="You don't have permission to edit users">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('can_unlock_users')): ?>
                                        <?php if ($user['is_locked']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="unlock">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Unlock Account">
                                                <i class="bi bi-unlock"></i> Unlock
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="lock">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Lock Account">
                                                <i class="bi bi-lock"></i> Lock
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="You don't have permission to unlock users">
                                            <i class="bi bi-lock"></i> Lock/Unlock
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('can_activate_users')): ?>
                                        <?php if ($user['is_active']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Deactivate User">
                                                <i class="bi bi-x-circle"></i> Deactivate
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Activate User">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled title="You don't have permission to activate/deactivate users">
                                            <i class="bi bi-toggle2-off"></i> Toggle
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (hasPermission('can_reset_passwords')): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Reset password for this user to Password@123?');">
                                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-info" title="Reset Password">
                                            <i class="bi bi-key"></i> Reset Pwd
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-outline-info" disabled title="You don't have permission to reset passwords">
                                        <i class="bi bi-key"></i> Reset Pwd
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25
    });
});
</script>

<?php include 'includes/footer.php'; ?>

