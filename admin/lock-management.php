<?php
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Handle unlock action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'danger');
    } else {
        $action = $_POST['action'] ?? '';
        $userId = intval($_POST['user_id'] ?? 0);
        
        if ($action === 'unlock') {
            if (!hasPermission('can_unlock_users')) {
                setFlashMessage('You do not have permission to unlock users.', 'danger');
                redirect('lock-management.php');
            }
            $stmt = $db->prepare("UPDATE users SET is_locked = 0, failed_attempts = 0, last_failed_attempt = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            logActivity('account_unlocked', "Unlocked user ID: $userId");
            setFlashMessage('Account unlocked successfully.', 'success');
            redirect('lock-management.php');
        } elseif ($action === 'reset_attempts') {
            if (!hasPermission('can_reset_passwords')) {
                setFlashMessage('You do not have permission to reset login attempts.', 'danger');
                redirect('lock-management.php');
            }
            $stmt = $db->prepare("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            logActivity('attempts_reset', "Reset login attempts for user ID: $userId");
            setFlashMessage('Login attempts reset successfully.', 'success');
            redirect('lock-management.php');
        }
    }
}

// Get locked accounts
$stmt = $db->query("
    SELECT id, username, full_name, email, failed_attempts, last_failed_attempt, created_at
    FROM users 
    WHERE is_locked = 1 
    ORDER BY last_failed_attempt DESC
");
$lockedUsers = $stmt->fetchAll();

// Get accounts with warning (2+ failed attempts)
$stmt = $db->query("
    SELECT id, username, full_name, email, failed_attempts, last_failed_attempt
    FROM users 
    WHERE is_locked = 0 AND failed_attempts >= 2 AND failed_attempts < 3
    ORDER BY failed_attempts DESC, last_failed_attempt DESC
");
$warningUsers = $stmt->fetchAll();

// Get statistics
$totalLocked = count($lockedUsers);
$warningCount = count($warningUsers);
$totalAffected = $totalLocked + $warningCount;

$pageTitle = 'Lock Management';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-lock"></i> Account Lock Management</h2>
            <p class="text-muted">Manage and unlock locked user accounts</p>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Locked Accounts</h6>
                            <h2 class="mb-0"><?= $totalLocked ?></h2>
                        </div>
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="bi bi-lock-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">At Risk (2 Attempts)</h6>
                            <h2 class="mb-0"><?= $warningCount ?></h2>
                        </div>
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Total Affected</h6>
                            <h2 class="mb-0"><?= $totalAffected ?></h2>
                        </div>
                        <div class="stat-icon bg-white bg-opacity-25">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
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
                            <h5 class="card-title">How Account Locking Works</h5>
                            <p class="card-text mb-0">
                                Accounts are automatically locked after <strong>3 failed login attempts</strong> for security. 
                                Administrators can manually unlock accounts or reset login attempt counters from this page. 
                                Use the unlock function to grant users access again.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Locked Accounts Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-lock-fill"></i> Locked Accounts</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($lockedUsers)): ?>
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle"></i> No locked accounts. All users can access their accounts.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Failed Attempts</th>
                                    <th>Last Failed Attempt</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lockedUsers as $user): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><span class="badge bg-danger">3/3</span></td>
                                    <td><small><?= $user['last_failed_attempt'] ? formatDate($user['last_failed_attempt']) : 'N/A' ?></small></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="unlock">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-unlock"></i> Unlock
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Accounts Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Accounts at Risk (2 Failed Attempts)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($warningUsers)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle"></i> No accounts at risk. All accounts have 0-1 failed attempts.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Failed Attempts</th>
                                    <th>Last Failed Attempt</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($warningUsers as $user): ?>
                                <tr class="table-warning">
                                    <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['full_name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td><span class="badge bg-warning text-dark"><?= $user['failed_attempts'] ?>/3</span></td>
                                    <td><small><?= formatDate($user['last_failed_attempt']) ?></small></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="reset_attempts">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-warning" title="Reset failed attempts counter">
                                                <i class="bi bi-arrow-clockwise"></i> Reset Attempts
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
