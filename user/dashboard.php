<?php
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$db = getDB();

// Get user's recent login attempts
$stmt = $db->prepare("
    SELECT * FROM login_attempts 
    WHERE user_id = ? 
    ORDER BY attempt_time DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$recentAttempts = $stmt->fetchAll();

// Get user's recent activities
$stmt = $db->prepare("
    SELECT * FROM activity_log 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$recentActivities = $stmt->fetchAll();

$pageTitle = 'User Dashboard';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-house-door"></i> Welcome, <?= htmlspecialchars($_SESSION['full_name']) ?>!</h2>
            <p class="text-muted">Here's your account overview</p>
        </div>
    </div>

    <!-- User Info Card -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-circle"></i> Account Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="150">Username:</th>
                            <td><?= htmlspecialchars($_SESSION['username']) ?></td>
                        </tr>
                        <tr>
                            <th>Full Name:</th>
                            <td><?= htmlspecialchars($_SESSION['full_name']) ?></td>
                        </tr>
                        <tr>
                            <th>User Level:</th>
                            <td>
                                <?php if ($_SESSION['user_level'] === 'admin'): ?>
                                    <span class="badge bg-danger">Administrator</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Regular User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Permissions:</th>
                            <td>
                                <?php if (hasPermission('can_view')): ?>
                                    <span class="badge bg-info">View</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">View <i class="bi bi-lock"></i></span>
                                <?php endif; ?>
                                <?php if (hasPermission('can_add')): ?>
                                    <span class="badge bg-success">Add</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Add <i class="bi bi-lock"></i></span>
                                <?php endif; ?>
                                <?php if (hasPermission('can_edit')): ?>
                                    <span class="badge bg-primary">Edit</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Edit <i class="bi bi-lock"></i></span>
                                <?php endif; ?>
                                <?php if (hasPermission('can_delete')): ?>
                                    <span class="badge bg-danger">Delete</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Delete <i class="bi bi-lock"></i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <hr>
                    <a href="change-password.php" class="btn btn-primary">
                        <i class="bi bi-key"></i> Change Password
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-shield-check"></i> Security Status</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle"></i> Your account is secure and active
                    </div>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> Account is active
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> No failed login attempts
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success"></i> Password meets security requirements
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Login Attempts -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Your Recent Login Attempts</h5>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th>IP Address</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentAttempts as $attempt): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($attempt['ip_address']) ?></code></td>
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

        <!-- Recent Activities -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="bi bi-activity"></i> Your Recent Activities</h5>
                </div>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                            <tr>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentActivities as $activity): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($activity['action']) ?></span></td>
                                <td><small><?= htmlspecialchars($activity['description'] ?? '-') ?></small></td>
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

<?php include 'includes/footer.php'; ?>

