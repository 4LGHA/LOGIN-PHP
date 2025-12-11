<?php
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'danger');
    } else {
        $userId = intval($_POST['user_id'] ?? 0);
        $can_view = isset($_POST['can_view']) ? 1 : 0;
        $can_edit = isset($_POST['can_edit']) ? 1 : 0;
        $can_add = isset($_POST['can_add']) ? 1 : 0;
        $can_delete = isset($_POST['can_delete']) ? 1 : 0;
        
        // Verify user is admin
        $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND user_level = 'admin'");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            setFlashMessage('Only administrators can have admin tool permissions.', 'danger');
            redirect('admin-tools.php');
        }
        
        try {
            // Check if permissions exist
            $stmt = $db->prepare("SELECT id FROM admin_permissions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                // Update existing
                $stmt = $db->prepare("
                    UPDATE admin_permissions 
                    SET can_view = ?, can_edit = ?, can_add = ?, can_delete = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$can_view, $can_edit, $can_add, $can_delete, $userId]);
            } else {
                // Insert new
                $stmt = $db->prepare("
                    INSERT INTO admin_permissions (user_id, can_view, can_edit, can_add, can_delete)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $can_view, $can_edit, $can_add, $can_delete]);
            }
            
            logActivity('admin_permissions_updated', "Updated admin tool permissions for user ID: $userId");
            setFlashMessage('Admin tool permissions updated successfully!', 'success');
            redirect('admin-tools.php');
        } catch (Exception $e) {
            setFlashMessage('Failed to update permissions: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get all admin users with their permissions
$stmt = $db->query("
    SELECT u.id, u.username, u.full_name, u.email, 
           ap.can_view, ap.can_edit, ap.can_add, ap.can_delete
    FROM users u
    LEFT JOIN admin_permissions ap ON u.id = ap.user_id
    WHERE u.user_level = 'admin'
    ORDER BY u.created_at ASC
");
$admins = $stmt->fetchAll();

$pageTitle = 'Admin Tools & Permissions';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-shield-lock"></i> Admin Tools & Permissions</h2>
            <p class="text-muted">Manage admin access levels and tool permissions</p>
        </div>
    </div>

    <!-- Information Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info shadow-sm">
                <div class="card-body">
                    <div class="d-flex gap-3">
                        <div>
                            <i class="bi bi-info-circle text-info" style="font-size: 1.5rem;"></i>
                        </div>
                        <div>
                            <h5 class="card-title">Permission Levels</h5>
                            <ul class="mb-0 small">
                                <li><strong>Can View:</strong> Access to admin panel and view data</li>
                                <li><strong>Can Edit:</strong> Ability to modify user information and settings</li>
                                <li><strong>Can Add:</strong> Ability to create new users and resources</li>
                                <li><strong>Can Delete:</strong> Ability to delete users and resources</li>
                                <li><strong>Full Access:</strong> All permissions enabled</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Permissions Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Administrator Accounts</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th class="text-center">Can View</th>
                                    <th class="text-center">Can Edit</th>
                                    <th class="text-center">Can Add</th>
                                    <th class="text-center">Can Delete</th>
                                    <th class="text-center">Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($admin['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($admin['full_name']) ?></td>
                                    <td><?= htmlspecialchars($admin['email']) ?></td>
                                    <td class="text-center">
                                        <?php if ($admin['can_view']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-x"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($admin['can_edit']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-x"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($admin['can_add']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-x"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($admin['can_delete']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check"></i></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="bi bi-x"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php 
                                            $allEnabled = $admin['can_view'] && $admin['can_edit'] && $admin['can_add'] && $admin['can_delete'];
                                            if ($allEnabled): ?>
                                                <span class="badge bg-success">Full Access</span>
                                        <?php elseif ($admin['can_view']): ?>
                                                <span class="badge bg-info">Limited</span>
                                        <?php else: ?>
                                                <span class="badge bg-danger">No Access</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                data-bs-target="#editPermissionsModal" data-admin-id="<?= $admin['id'] ?>"
                                                data-username="<?= htmlspecialchars($admin['username']) ?>"
                                                data-can-view="<?= $admin['can_view'] ?>"
                                                data-can-edit="<?= $admin['can_edit'] ?>"
                                                data-can-add="<?= $admin['can_add'] ?>"
                                                data-can-delete="<?= $admin['can_delete'] ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Permissions Modal -->
<div class="modal fade" id="editPermissionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Admin Permissions - <span id="modalUsername"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="user_id" id="editUserId" value="">
                    
                    <div class="alert alert-info">
                        <small>Configure which admin tools this administrator can access:</small>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="can_view" name="can_view">
                        <label class="form-check-label" for="can_view">
                            <strong>Can View</strong>
                            <div class="small text-muted">Access to admin panel and view system data</div>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="can_edit" name="can_edit">
                        <label class="form-check-label" for="can_edit">
                            <strong>Can Edit</strong>
                            <div class="small text-muted">Modify user information and system settings</div>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="can_add" name="can_add">
                        <label class="form-check-label" for="can_add">
                            <strong>Can Add</strong>
                            <div class="small text-muted">Create new users and resources</div>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="can_delete" name="can_delete">
                        <label class="form-check-label" for="can_delete">
                            <strong>Can Delete</strong>
                            <div class="small text-muted">Delete users and resources</div>
                        </label>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <small><i class="bi bi-exclamation-triangle"></i> <strong>Note:</strong> Can View must be enabled for the admin to access the admin panel.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle modal population
document.getElementById('editPermissionsModal').addEventListener('show.bs.modal', function(e) {
    const button = e.relatedTarget;
    document.getElementById('editUserId').value = button.getAttribute('data-admin-id');
    document.getElementById('modalUsername').textContent = button.getAttribute('data-username');
    document.getElementById('can_view').checked = button.getAttribute('data-can-view') == 1;
    document.getElementById('can_edit').checked = button.getAttribute('data-can-edit') == 1;
    document.getElementById('can_add').checked = button.getAttribute('data-can-add') == 1;
    document.getElementById('can_delete').checked = button.getAttribute('data-can-delete') == 1;
});
</script>

<?php include 'includes/footer.php'; ?>
