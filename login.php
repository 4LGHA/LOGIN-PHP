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

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request. Please try again.', 'danger');
    } else {
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            setFlashMessage('Please enter both username and password.', 'warning');
        } else {
            $result = attemptLogin($username, $password);
            
            if ($result['success']) {
                if (isAdmin()) {
                    redirect('admin/dashboard.php');
                } else {
                    redirect('user/dashboard.php');
                }
            } else {
                setFlashMessage($result['message'], 'danger');
            }
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
    <title>Login - Secure Login System</title>
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
                        <h3 class="mb-1 fw-bold">Sign In</h3>
                        <p class="text-muted small mb-4">Enter your credentials</p>

                        <?php
                        $flash = getFlashMessage();
                        if ($flash):
                        ?>
                        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($flash['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label small fw-500">Username</label>
                                <input type="text" class="form-control form-control-sm" id="username" name="username" 
                                       placeholder="username" required autofocus>
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

                            <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">Sign In</button>
                            <a href="register.php" class="btn btn-light btn-sm w-100">Create New Account</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>

