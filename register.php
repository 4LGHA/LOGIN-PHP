<?php
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $full_name = sanitize($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $agree_terms = isset($_POST['agree_terms']) ? 1 : 0;

        $errors = [];

        // Validation
        if (empty($username)) {
            $errors[] = "Username is required";
        } elseif (strlen($username) < 3) {
            $errors[] = "Username must be at least 3 characters long";
        } elseif (strlen($username) > 50) {
            $errors[] = "Username cannot exceed 50 characters";
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, underscores, and hyphens";
        }

        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($full_name)) {
            $errors[] = "Full name is required";
        } elseif (strlen($full_name) < 2) {
            $errors[] = "Full name must be at least 2 characters long";
        } elseif (strlen($full_name) > 100) {
            $errors[] = "Full name cannot exceed 100 characters";
        }

        if (empty($password)) {
            $errors[] = "Password is required";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        if (!$agree_terms) {
            $errors[] = "You must agree to the terms and conditions";
        }

        // Validate password strength
        if (!empty($password)) {
            $passwordValidation = validatePassword($password);
            if (!$passwordValidation['valid']) {
                $errors = array_merge($errors, $passwordValidation['errors']);
            }
        }

        if (empty($errors)) {
            $db = Database::getInstance()->getConnection();

            // Check if username exists
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $errors[] = "Username already exists. Please choose another one.";
            }

            // Check if email exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Email already registered. Please use another email or try logging in.";
            }

            if (empty($errors)) {
                try {
                    $db->beginTransaction();

                    // Insert user (all new registrations are regular users by default)
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        INSERT INTO users (username, password, email, full_name, user_level, is_active)
                        VALUES (?, ?, ?, ?, 'user', 1)
                    ");
                    $stmt->execute([$username, $hashed_password, $email, $full_name]);
                    $user_id = $db->lastInsertId();

                    // Insert default restrictions (can_view enabled by default)
                    $stmt = $db->prepare("
                        INSERT INTO user_restrictions (user_id, can_add, can_edit, can_view, can_delete)
                        VALUES (?, 0, 0, 1, 0)
                    ");
                    $stmt->execute([$user_id]);

                    $db->commit();

                    // Log registration activity
                    logActivity('registration', "New user registered: $username", $user_id);

                    setFlashMessage('Registration successful! You can now login.', 'success');
                    redirect('login.php');
                } catch (Exception $e) {
                    $db->rollBack();
                    error_log("Registration error: " . $e->getMessage());
                    $errors[] = "Registration failed. Please try again later.";
                }
            }
        }

        if (!empty($errors)) {
            setFlashMessage(implode('<br>', $errors), 'danger');
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Secure Login System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card border-0 shadow">
                    <div class="card-body p-4">
                        <h3 class="mb-1 fw-bold">Sign Up</h3>
                        <p class="text-muted small mb-4">Create your account</p>

                        <?php displayFlashMessage(); ?>

                        <form method="POST" action="" id="registerForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <div class="mb-3">
                                <label for="username" class="form-label small fw-500">Username</label>
                                <input type="text" class="form-control form-control-sm" id="username" name="username"
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                       placeholder="username" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label small fw-500">Email</label>
                                <input type="email" class="form-control form-control-sm" id="email" name="email"
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="you@example.com" required>
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label small fw-500">Full Name</label>
                                <input type="text" class="form-control form-control-sm" id="full_name" name="full_name"
                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                       placeholder="John Doe" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label small fw-500">Password</label>
                                <div class="input-group input-group-sm">
                                    <input type="password" class="form-control" id="password" name="password"
                                           placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label small fw-500">Confirm Password</label>
                                <input type="password" class="form-control form-control-sm" id="confirm_password" name="confirm_password"
                                       placeholder="••••••••" required>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agree_terms" name="agree_terms" required>
                                    <label class="form-check-label small" for="agree_terms">
                                        I agree to the <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#termsModal">terms</a>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">Create Account</button>
                            <a href="login.php" class="btn btn-light btn-sm w-100">Back to Login</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h6 class="modal-title fw-bold">Terms and Conditions</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body small" style="max-height: 350px; overflow-y: auto;">
                    <p><strong>1. User Account</strong><br>By registering, you agree to provide accurate information and maintain password confidentiality.</p>
                    <p><strong>2. Acceptable Use</strong><br>You agree not to use this system for unlawful purposes or unauthorized access.</p>
                    <p><strong>3. Intellectual Property</strong><br>All platform content is protected by copyright laws.</p>
                    <p><strong>4. Liability</strong><br>The system is provided "as is" without warranties.</p>
                    <p><strong>5. Account Termination</strong><br>We reserve the right to suspend or terminate accounts for policy violations.</p>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/password-strength.js"></script>
    <script>
        // Initialize password strength checker
        const passwordChecker = new PasswordStrengthChecker('password', 'confirm_password');

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const icon = this.querySelector('i');

            if (password.type === 'password') {
                password.type = 'text';
                confirmPassword.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                password.type = 'password';
                confirmPassword.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const agreeTerms = document.getElementById('agree_terms');
            if (!agreeTerms.checked) {
                e.preventDefault();
                alert('Please agree to the terms and conditions.');
                agreeTerms.focus();
            }
        });
    </script>
</body>
</html>
