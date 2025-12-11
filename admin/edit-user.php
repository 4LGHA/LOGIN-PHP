<?php
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Check if user has permission to edit users
if (!hasPermission('can_edit_users')) {
    setFlashMessage('You do not have permission to edit users.', 'danger');
    redirect('users.php');
}

$db = getDB();
$userId = intval($_GET['id'] ?? 0);

if ($userId <= 0) {
    setFlashMessage('Invalid user ID.', 'danger');
    redirect('users.php');
}

// Get user data with restrictions
// Use COALESCE to handle cases where columns might not exist yet
try {
    $stmt = $db->prepare("SELECT u.*, 
        COALESCE(ur.can_add, 0) as can_add, 
        COALESCE(ur.can_edit, 0) as can_edit, 
        COALESCE(ur.can_view, 1) as can_view, 
        COALESCE(ur.can_delete, 0) as can_delete, 
        COALESCE(ur.can_edit_users, 0) as can_edit_users, 
        COALESCE(ur.can_activate_users, 0) as can_activate_users, 
        COALESCE(ur.can_unlock_users, 0) as can_unlock_users, 
        COALESCE(ur.can_reset_passwords, 0) as can_reset_passwords
    FROM users u
    LEFT JOIN user_restrictions ur ON u.id = ur.user_id
    WHERE u.id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
} catch (Exception $e) {
    // If query fails due to missing columns, try with basic columns only
    error_log("Edit user query failed: " . $e->getMessage());
    $stmt = $db->prepare("SELECT u.*, 
        0 as can_add, 
        0 as can_edit, 
        1 as can_view, 
        0 as can_delete, 
        0 as can_edit_users, 
        0 as can_activate_users, 
        0 as can_unlock_users, 
        0 as can_reset_passwords
    FROM users u
    WHERE u.id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}

if (!$user) {
    setFlashMessage('User not found.', 'danger');
    redirect('users.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'danger');
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $full_name = sanitize($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $user_level = sanitize($_POST['user_level'] ?? 'user');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $can_add = isset($_POST['can_add']) ? 1 : 0;
        $can_edit = isset($_POST['can_edit']) ? 1 : 0;
        $can_view = isset($_POST['can_view']) ? 1 : 0;
        $can_delete = isset($_POST['can_delete']) ? 1 : 0;
        $can_edit_users = isset($_POST['can_edit_users']) ? 1 : 0;
        $can_activate_users = isset($_POST['can_activate_users']) ? 1 : 0;
        $can_unlock_users = isset($_POST['can_unlock_users']) ? 1 : 0;
        $can_reset_passwords = isset($_POST['can_reset_passwords']) ? 1 : 0;
        
        $errors = [];
        
        // Validation
        if (empty($username)) $errors[] = 'Username is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($full_name)) $errors[] = 'Full name is required';
        
        // If password is provided, validate it
        if (!empty($password)) {
            if ($password !== $confirm_password) {
                $errors[] = 'Passwords do not match';
            }
            
            $passwordValidation = validatePassword($password);
            if (!$passwordValidation['valid']) {
                $errors = array_merge($errors, $passwordValidation['errors']);
            }
        }
        
        // Check if username exists (excluding current user)
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already exists';
        }
        
        // Check if email exists (excluding current user)
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already exists';
        }
        
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                // Update user
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET username = ?, password = ?, email = ?, full_name = ?, user_level = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $hashedPassword, $email, $full_name, $user_level, $is_active, $userId]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE users 
                        SET username = ?, email = ?, full_name = ?, user_level = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $full_name, $user_level, $is_active, $userId]);
                }
                
                // Update or insert user restrictions
                // First, check if restrictions exist for this user
                $stmt = $db->prepare("SELECT id FROM user_restrictions WHERE user_id = ?");
                $stmt->execute([$userId]);
                $restriction = $stmt->fetch();
                
                if ($restriction) {
                    // Update existing restriction
                    $stmt = $db->prepare("
                        UPDATE user_restrictions 
                        SET can_add = ?, can_edit = ?, can_view = ?, can_delete = ?, can_edit_users = ?, can_activate_users = ?, can_unlock_users = ?, can_reset_passwords = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$can_add, $can_edit, $can_view, $can_delete, $can_edit_users, $can_activate_users, $can_unlock_users, $can_reset_passwords, $userId]);
                } else {
                    // Insert new restriction
                    $stmt = $db->prepare("
                        INSERT INTO user_restrictions (user_id, can_add, can_edit, can_view, can_delete, can_edit_users, can_activate_users, can_unlock_users, can_reset_passwords)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$userId, $can_add, $can_edit, $can_view, $can_delete, $can_edit_users, $can_activate_users, $can_unlock_users, $can_reset_passwords]);
                }
                
                $db->commit();
                
                logActivity('user_updated', "Updated user: $username");
                setFlashMessage('User updated successfully!', 'success');
                redirect('users.php');
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Failed to update user: ' . $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            setFlashMessage(implode('<br>', $errors), 'danger');
        }
    }
}

$pageTitle = 'Edit User';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-pencil-square"></i> Edit User</h2>
                <a href="users.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Users
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Leave password fields empty to keep current password
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength-container mt-2">
                                    <div class="progress" style="height: 8px;">
                                        <div id="password-strength-bar" class="progress-bar" role="progressbar" 
                                             style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span id="password-strength-text" class="small"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                <div id="confirm-password-feedback" class="mt-1"></div>
                            </div>
                        </div>

                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">Password Requirements</h6>
                                <div id="password-requirements"></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="user_level" class="form-label">User Level <span class="text-danger">*</span></label>
                                <select class="form-select" id="user_level" name="user_level" required>
                                    <option value="user" <?= $user['user_level'] === 'user' ? 'selected' : '' ?>>Regular User</option>
                                    <option value="admin" <?= $user['user_level'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">Account Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?= $user['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Active Account</label>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-light mb-3">
                            <div class="card-header bg-secondary text-white">
                                <h6 class="mb-0"><i class="bi bi-shield-lock"></i> User Restrictions</h6>
                            </div>
                            <div class="card-body">
                                <h6 class="mb-3">General Permissions</h6>
                                <p class="small text-muted mb-3">Control what actions this user can perform:</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="can_view" name="can_view" 
                                                   <?= $user['can_view'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="can_view">
                                                <i class="bi bi-eye"></i> Can View
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="can_add" name="can_add" 
                                                   <?= $user['can_add'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="can_add">
                                                <i class="bi bi-plus-circle"></i> Can Add
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="can_edit" name="can_edit" 
                                                   <?= $user['can_edit'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="can_edit">
                                                <i class="bi bi-pencil"></i> Can Edit
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="can_delete" name="can_delete" 
                                                   <?= $user['can_delete'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="can_delete">
                                                <i class="bi bi-trash"></i> Can Delete
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <h6 class="mb-3">Admin Permissions</h6>
                                <p class="small text-muted mb-3">Control admin actions this user can perform:</p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="can_edit_users" name="can_edit_users" 
                                                   <?= $user['can_edit_users'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="can_edit_users">
                                                <i class="bi bi-pencil-square"></i> Can Edit Users
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="can_activate_users" name="can_activate_users" 
                                                   <?= $user['can_activate_users'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="can_activate_users">
                                                <i class="bi bi-check-circle"></i> Can Activate Users
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="can_unlock_users" name="can_unlock_users" 
                                                   <?= $user['can_unlock_users'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="can_unlock_users">
                                                <i class="bi bi-unlock"></i> Can Unlock Users
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="can_reset_passwords" name="can_reset_passwords" 
                                                   <?= $user['can_reset_passwords'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="can_reset_passwords">
                                                <i class="bi bi-key"></i> Can Reset Passwords
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

