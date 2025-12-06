<?php
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Get locked users with lock reasons
$stmt = $db->query("
    SELECT u.id, u.username, u.email, u.full_name, u.is_locked, u.failed_attempts, 
           u.last_failed_attempt, u.created_at,
           COUNT(la.id) as total_login_attempts,
           SUM(CASE WHEN la.success = 0 THEN 1 ELSE 0 END) as failed_login_attempts
    FROM users u
    LEFT JOIN login_attempts la ON u.id = la.user_id
    WHERE u.is_locked = 1
    GROUP BY u.id
    ORDER BY u.last_failed_attempt DESC
");
$lockedUsers = $stmt->fetchAll();

// Get users with high failed attempts (not locked yet)
$stmt = $db->query("
    SELECT u.id, u.username, u.email, u.full_name, u.failed_attempts, u.last_failed_attempt
    FROM users u
    WHERE u.failed_attempts >= 2 AND u.is_locked = 0
    ORDER BY u.last_failed_attempt DESC
");
$warningUsers = $stmt->fetchAll();

$pageTitle = 'Account Lock Management';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-lock"></i> Account Lock Management</h2>
            <p class="text-muted">Monitor and manage locked accounts</p>
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
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> No locked accounts at this time.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Full Name</th>
                                        <th>Failed Attempts</th>
                                        <th>Last Failed Attempt</th>
                                        <th>Total Attempts</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lockedUsers as $user): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                                        <td>
                                            <span class="badge bg-danger"><?= $user['failed_attempts'] ?>/3</span>
                                        </td>
                                        <td>
                                            <?php if ($user['last_failed_attempt']): ?>
                                                <?= formatDate($user['last_failed_attempt']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $user['total_login_attempts'] ?></span>
                                        </td>
                                        <td>
                                            <form method="POST" action="users.php" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                                <input type="hidden" name="action" value="unlock">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-success">
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

    <!-- Warning: High Failed Attempts Section -->
    <?php if (!empty($warningUsers)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Warning: High Failed Attempts</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">These accounts have 2+ failed login attempts and will be locked on the next failed attempt.</p>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Failed Attempts</th>
                                    <th>Last Failed Attempt</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($warningUsers as $user): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="badge bg-warning text-dark"><?= $user['failed_attempts'] ?>/3</span>
                                    </td>
                                    <td>
                                        <?php if ($user['last_failed_attempt']): ?>
                                            <?= formatDate($user['last_failed_attempt']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="users.php" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="unlock">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-info" title="Reset Failed Attempts">
                                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                                            </button>
                                        </form>
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
    <?php endif; ?>

    <!-- Information Section -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Lock/Unlock Feature Information</h5>
                </div>
                <div class="card-body small">
                    <h6>Automatic Locking:</h6>
                    <ul>
                        <li>Accounts are automatically locked after 3 consecutive failed login attempts</li>
                        <li>Failed attempts are tracked in the login attempts log</li>
                        <li>Last failed attempt timestamp is recorded</li>
                    </ul>

                    <h6>Manual Locking:</h6>
                    <ul>
                        <li>Admins can manually lock any user account from the Manage Users page</li>
                        <li>Use the <strong>Lock</strong> button to prevent user login</li>
                    </ul>

                    <h6>Unlocking:</h6>
                    <ul>
                        <li>Click the <strong>Unlock</strong> button to unlock a locked account</li>
                        <li>Unlocking also resets the failed login attempts counter</li>
                        <li>User can immediately login after unlocking</li>
                    </ul>

                    <h6>Activity Log:</h6>
                    <ul>
                        <li>All lock/unlock actions are logged in the activity log</li>
                        <li>See Admin → Activity Log for complete audit trail</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Locked Accounts</p>
                        <h3 class="text-danger"><?= count($lockedUsers) ?></h3>
                    </div>
                    <div class="mb-3">
                        <p class="text-muted small mb-1">Warning Status</p>
                        <h3 class="text-warning"><?= count($warningUsers) ?></h3>
                    </div>
                    <div class="mb-0">
                        <p class="text-muted small mb-1">Total Users Affected</p>
                        <h3><?= count($lockedUsers) + count($warningUsers) ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
