<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'User Dashboard' ?> - Login System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar text-white p-3" style="width: 250px;">
            <div class="mb-4">
                <h4 class="text-center">
                    <i class="bi bi-person-circle"></i> User Panel
                </h4>
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" 
                   href="dashboard.php">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'change-password.php' ? 'active' : '' ?>" 
                   href="change-password.php">
                    <i class="bi bi-key"></i> Change Password
                </a>
                <hr class="text-white-50">
                <a class="nav-link" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </nav>
            
            <div class="mt-auto pt-4">
                <div class="card bg-white bg-opacity-10 border-0">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <div class="user-avatar me-2">
                                <?= strtoupper(substr($_SESSION['full_name'], 0, 2)) ?>
                            </div>
                            <div>
                                <div class="fw-bold small"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                                <div class="small text-white-50">
                                    <?= $_SESSION['user_level'] === 'admin' ? 'Administrator' : 'User' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 bg-light" style="min-height: 100vh;">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h1"><?= $pageTitle ?? 'User Dashboard' ?></span>
                    <div class="ms-auto">
                        <span class="text-muted me-3">
                            <i class="bi bi-calendar"></i> <?= date('F d, Y') ?>
                        </span>
                        <span class="text-muted">
                            <i class="bi bi-clock"></i> <?= date('h:i A') ?>
                        </span>
                    </div>
                </div>
            </nav>

            <!-- Flash Messages -->
            <?php
            $flash = getFlashMessage();
            if ($flash):
            ?>
            <div class="container-fluid mt-3">
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Page Content -->

