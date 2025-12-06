<?php
require_once '../includes/auth.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    redirect('../login.php');
}

if (!isAdmin()) {
    redirect('../user/dashboard.php');
}

$db = getDB();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
$activeUsers = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE is_locked = 1");
$lockedUsers = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM login_attempts WHERE success = 0 AND attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
$failedAttempts = $stmt->fetch()['total'];

// Get recent login attempts
$stmt = $db->prepare("
    SELECT la.*, u.full_name 
    FROM login_attempts la 
    LEFT JOIN users u ON la.user_id = u.id 
    ORDER BY la.attempt_time DESC 
    LIMIT 10
");
$stmt->execute();
$recentAttempts = $stmt->fetchAll();

// Get recent activities
$stmt = $db->prepare("
    SELECT al.*, u.username, u.full_name 
    FROM activity_log al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recentActivities = $stmt->fetchAll();

$pageTitle = 'Admin Dashboard';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
            <p class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['full_name']) ?>!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Total Users</h6>
                            <h2 class="mb-0"><?= $totalUsers ?></h2>
                        </div>
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Active Users</h6>
                            <h2 class="mb-0"><?= $activeUsers ?></h2>
                        </div>
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Locked Accounts</h6>
                            <h2 class="mb-0"><?= $lockedUsers ?></h2>
                        </div>
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="bi bi-lock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Failed Attempts (24h)</h6>
                            <h2 class="mb-0"><?= $failedAttempts ?></h2>
                        </div>
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Login Attempts -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Login Attempts</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th>User</th>
                                    <th>Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAttempts as $attempt): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($attempt['username']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($attempt['ip_address']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($attempt['success']): ?>
                                            <span class="badge bg-success">Success</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= formatDate($attempt['attempt_time']) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-activity"></i> Recent Activities</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover mb-0">
                            <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivities as $activity): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($activity['username'] ?? 'System') ?></strong></td>
                                    <td><?= htmlspecialchars($activity['action']) ?></td>
                                    <td><small><?= formatDate($activity['created_at']) ?></small></td>
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

<?php include 'includes/footer.php'; ?>

