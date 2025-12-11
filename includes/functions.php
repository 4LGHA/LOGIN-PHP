<?php
/**
 * Common Functions and Utilities
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate password strength
 * Returns array with 'valid' boolean and 'errors' array
 */
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Calculate password strength (0-100)
 */
function calculatePasswordStrength($password) {
    $strength = 0;
    
    // Length
    if (strlen($password) >= 8) $strength += 20;
    if (strlen($password) >= 12) $strength += 10;
    if (strlen($password) >= 16) $strength += 10;
    
    // Character types
    if (preg_match('/[a-z]/', $password)) $strength += 15;
    if (preg_match('/[A-Z]/', $password)) $strength += 15;
    if (preg_match('/[0-9]/', $password)) $strength += 15;
    if (preg_match('/[^A-Za-z0-9]/', $password)) $strength += 15;
    
    return min(100, $strength);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_level']) && $_SESSION['user_level'] === 'admin';
}

/**
 * Redirect to a page
 */
function redirect($page) {
    header("Location: $page");
    exit();
}

/**
 * Check user permission
 */
function hasPermission($permission) {
    if (!isLoggedIn()) return false;
    if (isAdmin()) return true; // Admin has all permissions
    
    return isset($_SESSION['permissions'][$permission]) && $_SESSION['permissions'][$permission] == 1;
}

/**
 * Check admin permission
 */
function hasAdminPermission($permission) {
    if (!isLoggedIn() || !isAdmin()) return false;
    
    return isset($_SESSION['admin_permissions'][$permission]) && $_SESSION['admin_permissions'][$permission] == 1;
}

/**
 * Require user permission - redirects if user doesn't have permission
 */
function requirePermission($permission) {
    if (!hasPermission($permission)) {
        setFlashMessage('You do not have permission to perform this action.', 'danger');
        redirect('dashboard.php');
    }
}

/**
 * Require admin permission - redirects if admin doesn't have permission
 */
function requireAdminPermission($permission) {
    if (!isAdmin() || !hasAdminPermission($permission)) {
        setFlashMessage('You do not have permission to access this feature.', 'danger');
        redirect('../admin/dashboard.php');
    }
}

/**
 * Log activity
 */
function logActivity($action, $description = null, $user_id = null) {
    try {
        $db = getDB();
        $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $stmt = $db->prepare("INSERT INTO activity_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $description, $ip_address]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Display flash message
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Display flash message (wrapper for getFlashMessage with HTML output)
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash):
    ?>
        <div class="alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php
    endif;
}

/**
 * Format date
 */
function formatDate($date) {
    return date('M d, Y h:i A', strtotime($date));
}
?>

