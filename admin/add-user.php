<?php
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Check if user has permission to add users
if (!hasPermission('can_add')) {
    setFlashMessage('You do not have permission to add users.', 'danger');
    redirect('users.php');
}

$db = getDB();

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
        
        // Restrictions
        $can_add = isset($_POST['can_add']) ? 1 : 0;
        $can_edit = isset($_POST['can_edit']) ? 1 : 0;
        $can_view = isset($_POST['can_view']) ? 1 : 0;
        $can_delete = isset($_POST['can_delete']) ? 1 : 0;
        
        $errors = [];
        
        // Validation
        if (empty($username)) $errors[] = 'Username is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (empty($full_name)) $errors[] = 'Full name is required';
        if (empty($password)) $errors[] = 'Password is required';
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        // Validate password strength
        $passwordValidation = validatePassword($password);
        if (!$passwordValidation['valid']) {
            $errors = array_merge($errors, $passwordValidation['errors']);
        }
        
        // Check if username exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = 'Username already exists';
        }
        
        // Check if email exists
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already exists';
        }
        
        if (empty($errors)) {
            try {
                $db->beginTransaction();
                
                // Insert user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO users (username, password, email, full_name, user_level, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$username, $hashedPassword, $email, $full_name, $user_level, $is_active]);
                $userId = $db->lastInsertId();
                
                // Insert restrictions
                $stmt = $db->prepare("
                    INSERT INTO user_restrictions (user_id, can_add, can_edit, can_view, can_delete) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $can_add, $can_edit, $can_view, $can_delete]);
                
                $db->commit();
                
                logActivity('user_created', "Created new user: $username");
                setFlashMessage('User created successfully!', 'success');
                redirect('users.php');
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Failed to create user: ' . $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            setFlashMessage(implode('<br>', $errors), 'danger');
        }
    }
}

$pageTitle = 'Add New User';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-person-plus"></i> Add New User</h2>
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
                                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
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
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
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
                                    <option value="user" selected>Regular User</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label d-block">Account Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active Account</label>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">User Restrictions</h6>
                                <p class="text-muted small">Select permissions for this user (Admin users have full access)</p>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="can_view" name="can_view" checked>
                                            <label class="form-check-label" for="can_view">Can View</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="can_add" name="can_add">
                                            <label class="form-check-label" for="can_add">Can Add</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="can_edit" name="can_edit">
                                            <label class="form-check-label" for="can_edit">Can Edit</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="can_delete" name="can_delete">
                                            <label class="form-check-label" for="can_delete">Can Delete</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

