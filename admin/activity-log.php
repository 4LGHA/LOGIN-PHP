<?php
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$db = getDB();

// Get all activities
$stmt = $db->query("
    SELECT al.*, u.username, u.full_name 
    FROM activity_log al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC
");
$activities = $stmt->fetchAll();

$pageTitle = 'Activity Log';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="bi bi-activity"></i> Activity Log</h2>
            <p class="text-muted">Track all system activities and user actions</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto; overflow-x: auto;">
                <table id="activityTable" class="table table-hover">
                    <thead class="table-light" style="position: sticky; top: 0; z-index: 10;">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td><?= $activity['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($activity['username'] ?? 'System') ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($activity['full_name'] ?? 'N/A') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?= htmlspecialchars($activity['action']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($activity['description'] ?? '-') ?></td>
                            <td><code><?= htmlspecialchars($activity['ip_address']) ?></code></td>
                            <td><?= formatDate($activity['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#activityTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25
    });
});
</script>

<?php include 'includes/footer.php'; ?>

