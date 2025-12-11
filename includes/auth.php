<?php
/**
 * Authentication Functions
 */

require_once __DIR__ . '/functions.php';

/**
 * Attempt to login user
 * Returns array with 'success' boolean and 'message' string
 */
function attemptLogin($username, $password) {
    $db = getDB();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    try {
        // Get user by username
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        // Check if user exists
        if (!$user) {
            logLoginAttempt(null, $username, $ip_address, false, 'User not found');
            return ['success' => false, 'message' => 'Invalid username or password'];
        }
        
        // Check if account is locked
        if ($user['is_locked']) {
            logLoginAttempt($user['id'], $username, $ip_address, false, 'Account locked');
            return ['success' => false, 'message' => 'Account is locked. Please contact administrator.'];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            logLoginAttempt($user['id'], $username, $ip_address, false, 'Account inactive');
            return ['success' => false, 'message' => 'Account is inactive. Please contact administrator.'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            // Increment failed attempts
            incrementFailedAttempts($user['id']);
            logLoginAttempt($user['id'], $username, $ip_address, false, 'Invalid password');
            
            // Check if account should be locked
            $failedAttempts = $user['failed_attempts'] + 1;
            if ($failedAttempts >= 3) {
                lockAccount($user['id']);
                return ['success' => false, 'message' => 'Account locked due to multiple failed login attempts. Please contact administrator.'];
            }
            
            $remainingAttempts = 3 - $failedAttempts;
            return ['success' => false, 'message' => "Invalid username or password. $remainingAttempts attempt(s) remaining."];
        }
        
        // Login successful - reset failed attempts
        resetFailedAttempts($user['id']);
        logLoginAttempt($user['id'], $username, $ip_address, true, 'Login successful');
        
        // Get user restrictions
        $stmt = $db->prepare("SELECT * FROM user_restrictions WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $restrictions = $stmt->fetch();
        
        // Get admin permissions (if user is admin)
        $adminPerms = ['can_view' => 0, 'can_edit' => 0, 'can_add' => 0, 'can_delete' => 0];
        if ($user['user_level'] === 'admin') {
            $stmt = $db->prepare("SELECT * FROM admin_permissions WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $adminPerms = $stmt->fetch() ?: $adminPerms;
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_level'] = $user['user_level'];
        $_SESSION['permissions'] = [
            'can_add' => $restrictions['can_add'] ?? 0,
            'can_edit' => $restrictions['can_edit'] ?? 0,
            'can_view' => $restrictions['can_view'] ?? 1,
            'can_delete' => $restrictions['can_delete'] ?? 0
        ];
        $_SESSION['admin_permissions'] = [
            'can_view' => $adminPerms['can_view'] ?? 0,
            'can_edit' => $adminPerms['can_edit'] ?? 0,
            'can_add' => $adminPerms['can_add'] ?? 0,
            'can_delete' => $adminPerms['can_delete'] ?? 0
        ];
        
        // Log activity
        logActivity('login', 'User logged in successfully', $user['id']);
        
        return ['success' => true, 'message' => 'Login successful'];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred. Please try again.'];
    }
}

/**
 * Logout user
 */
function logout() {
    if (isLoggedIn()) {
        logActivity('logout', 'User logged out');
    }
    
    session_unset();
    session_destroy();
    session_start();
}

/**
 * Log login attempt
 */
function logLoginAttempt($user_id, $username, $ip_address, $success, $failure_reason = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO login_attempts (user_id, username, ip_address, success, failure_reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $username, $ip_address, $success ? 1 : 0, $failure_reason]);
    } catch (Exception $e) {
        error_log("Failed to log login attempt: " . $e->getMessage());
    }
}

/**
 * Increment failed login attempts
 */
function incrementFailedAttempts($user_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET failed_attempts = failed_attempts + 1, last_failed_attempt = NOW() WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Failed to increment failed attempts: " . $e->getMessage());
    }
}

/**
 * Reset failed login attempts
 */
function resetFailedAttempts($user_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
    } catch (Exception $e) {
        error_log("Failed to reset failed attempts: " . $e->getMessage());
    }
}

/**
 * Lock user account
 */
function lockAccount($user_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE users SET is_locked = 1 WHERE id = ?");
        $stmt->execute([$user_id]);
        logActivity('account_locked', 'Account locked due to failed login attempts', $user_id);
    } catch (Exception $e) {
        error_log("Failed to lock account: " . $e->getMessage());
    }
}
?>

