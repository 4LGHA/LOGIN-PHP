<?php
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Require view permission
requirePermission('can_view');

$db = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid request.', 'danger');
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            requirePermission('can_add');
            
            $title = sanitize($_POST['title'] ?? '');
            $content = sanitize($_POST['content'] ?? '');
            $errors = [];
            
            if (empty($title)) $errors[] = 'Title is required';
            if (empty($content)) $errors[] = 'Content is required';
            
            if (empty($errors)) {
                try {
                    $stmt = $db->prepare("INSERT INTO user_notes (user_id, title, content) VALUES (?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $title, $content]);
                    logActivity('note_added', "Added note: $title");
                    setFlashMessage('Note added successfully!', 'success');
                    redirect('notes.php');
                } catch (Exception $e) {
                    setFlashMessage('Failed to add note: ' . $e->getMessage(), 'danger');
                }
            } else {
                setFlashMessage(implode('<br>', $errors), 'danger');
            }
        } elseif ($action === 'edit') {
            requirePermission('can_edit');
            
            $noteId = intval($_POST['note_id'] ?? 0);
            $title = sanitize($_POST['title'] ?? '');
            $content = sanitize($_POST['content'] ?? '');
            $errors = [];
            
            // Verify note belongs to user
            $stmt = $db->prepare("SELECT id FROM user_notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$noteId, $_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                setFlashMessage('Note not found.', 'danger');
                redirect('notes.php');
            }
            
            if (empty($title)) $errors[] = 'Title is required';
            if (empty($content)) $errors[] = 'Content is required';
            
            if (empty($errors)) {
                try {
                    $stmt = $db->prepare("UPDATE user_notes SET title = ?, content = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$title, $content, $noteId, $_SESSION['user_id']]);
                    logActivity('note_updated', "Updated note: $title");
                    setFlashMessage('Note updated successfully!', 'success');
                    redirect('notes.php');
                } catch (Exception $e) {
                    setFlashMessage('Failed to update note: ' . $e->getMessage(), 'danger');
                }
            } else {
                setFlashMessage(implode('<br>', $errors), 'danger');
            }
        } elseif ($action === 'delete') {
            requirePermission('can_delete');
            
            $noteId = intval($_POST['note_id'] ?? 0);
            
            // Verify note belongs to user
            $stmt = $db->prepare("SELECT title FROM user_notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$noteId, $_SESSION['user_id']]);
            $note = $stmt->fetch();
            
            if (!$note) {
                setFlashMessage('Note not found.', 'danger');
                redirect('notes.php');
            }
            
            try {
                $stmt = $db->prepare("DELETE FROM user_notes WHERE id = ? AND user_id = ?");
                $stmt->execute([$noteId, $_SESSION['user_id']]);
                logActivity('note_deleted', "Deleted note: " . $note['title']);
                setFlashMessage('Note deleted successfully!', 'success');
                redirect('notes.php');
            } catch (Exception $e) {
                setFlashMessage('Failed to delete note: ' . $e->getMessage(), 'danger');
            }
        }
    }
}

// Get user's notes
$stmt = $db->prepare("SELECT * FROM user_notes WHERE user_id = ? ORDER BY updated_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$notes = $stmt->fetchAll();

$pageTitle = 'My Notes';
include 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-sticky"></i> My Notes</h2>
                <?php if (hasPermission('can_add')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                    <i class="bi bi-plus-circle"></i> Add Note
                </button>
                <?php else: ?>
                <button class="btn btn-primary" disabled title="You don't have permission to add notes">
                    <i class="bi bi-plus-circle"></i> Add Note
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($notes)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> You don't have any notes yet. 
                <?php if (hasPermission('can_add')): ?>
                <a href="#addNoteModal" data-bs-toggle="modal">Create your first note!</a>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
            <?php foreach ($notes as $note): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($note['title']) ?></h5>
                        <p class="card-text text-muted"><?= htmlspecialchars(substr($note['content'], 0, 100)) ?>...</p>
                        <small class="text-muted">Updated: <?= formatDate($note['updated_at']) ?></small>
                    </div>
                    <div class="card-footer bg-white border-top">
                        <div class="d-flex gap-2">
                            <?php if (hasPermission('can_edit')): ?>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                    data-bs-target="#editNoteModal" data-note-id="<?= $note['id'] ?>"
                                    data-note-title="<?= htmlspecialchars($note['title']) ?>"
                                    data-note-content="<?= htmlspecialchars($note['content']) ?>">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-primary" disabled title="You don't have permission to edit notes">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('can_delete')): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this note?');">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="note_id" value="<?= $note['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-danger" disabled title="You don't have permission to delete notes">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="content" name="content" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Note Modal -->
<div class="modal fade" id="editNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="note_id" id="editNoteId" value="">
                    
                    <div class="mb-3">
                        <label for="editTitle" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editTitle" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editContent" class="form-label">Content <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editContent" name="content" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle edit modal population
document.getElementById('editNoteModal').addEventListener('show.bs.modal', function(e) {
    const button = e.relatedTarget;
    document.getElementById('editNoteId').value = button.getAttribute('data-note-id');
    document.getElementById('editTitle').value = button.getAttribute('data-note-title');
    document.getElementById('editContent').value = button.getAttribute('data-note-content');
});
</script>

<?php include 'includes/footer.php'; ?>
