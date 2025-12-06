<?php
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'danger');
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        // Get current user
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        }
        
        // Validate new password
        if (empty($new_password)) {
            $errors[] = 'New password is required';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        }
        
        // Validate password strength
        $passwordValidation = validatePassword($new_password);
        if (!$passwordValidation['valid']) {
            $errors = array_merge($errors, $passwordValidation['errors']);
        }
        
        // Check if new password is same as current
        if (password_verify($new_password, $user['password'])) {
            $errors[] = 'New password must be different from current password';
        }
        
        if (empty($errors)) {
            try {
                $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                
                logActivity('password_changed', 'User changed their password');
                setFlashMessage('Password changed successfully!', 'success');
                redirect('dashboard.php');
            } catch (Exception $e) {
                $errors[] = 'Failed to change password: ' . $e->getMessage();
            }
        }
        
        if (!empty($errors)) {
            setFlashMessage(implode('<br>', $errors), 'danger');
        }
    }
}

$pageTitle = 'Change Password';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-key"></i> Change Password</h2>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="new_password" required>
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

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div id="confirm-password-feedback" class="mt-1"></div>
                        </div>

                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">Password Requirements</h6>
                                <div id="password-requirements"></div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Change Password
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm bg-info bg-opacity-10">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-shield-check"></i> Password Security Tips</h5>
                    <ul>
                        <li>Use a unique password that you don't use for other accounts</li>
                        <li>Make your password at least 8 characters long</li>
                        <li>Include uppercase and lowercase letters</li>
                        <li>Include numbers and special characters</li>
                        <li>Avoid using personal information</li>
                        <li>Don't share your password with anyone</li>
                        <li>Change your password regularly</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

