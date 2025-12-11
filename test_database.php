<?php
/**
 * Quick Test - Verify Edit User Query Works
 * This tests the database query used in admin/edit-user.php
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h3>Testing Edit User Query</h3>";
    
    // Test the query used in edit-user.php
    $userId = 1; // Admin user
    
    $stmt = $db->prepare("SELECT u.*, 
        COALESCE(ur.can_add, 0) as can_add, 
        COALESCE(ur.can_edit, 0) as can_edit, 
        COALESCE(ur.can_view, 1) as can_view, 
        COALESCE(ur.can_delete, 0) as can_delete, 
        COALESCE(ur.can_edit_users, 0) as can_edit_users, 
        COALESCE(ur.can_activate_users, 0) as can_activate_users, 
        COALESCE(ur.can_unlock_users, 0) as can_unlock_users, 
        COALESCE(ur.can_reset_passwords, 0) as can_reset_passwords
    FROM users u
    LEFT JOIN user_restrictions ur ON u.id = ur.user_id
    WHERE u.id = ?");
    
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p><strong>✓ Query successful for user ID: $userId</strong></p>";
        echo "<h4>User Data:</h4>";
        echo "<ul>";
        echo "<li>ID: " . htmlspecialchars($user['id']) . "</li>";
        echo "<li>Username: " . htmlspecialchars($user['username']) . "</li>";
        echo "<li>Email: " . htmlspecialchars($user['email']) . "</li>";
        echo "<li>Full Name: " . htmlspecialchars($user['full_name']) . "</li>";
        echo "<li>User Level: " . htmlspecialchars($user['user_level']) . "</li>";
        echo "</ul>";
        
        echo "<h4>Permissions:</h4>";
        echo "<ul>";
        echo "<li>Can View: " . ($user['can_view'] ? '✓ Yes' : '✗ No') . "</li>";
        echo "<li>Can Add: " . ($user['can_add'] ? '✓ Yes' : '✗ No') . "</li>";
        echo "<li>Can Edit: " . ($user['can_edit'] ? '✓ Yes' : '✗ No') . "</li>";
        echo "<li>Can Delete: " . ($user['can_delete'] ? '✓ Yes' : '✗ No') . "</li>";
        echo "<li>Can Edit Users: " . ($user['can_edit_users'] ? '✓ Yes' : '✗ No') . "</li>";
        echo "<li>Can Activate Users: " . ($user['can_activate_users'] ? '✓ Yes' : '✗ No') . "</li>";
        echo "<li>Can Unlock Users: " . ($user['can_unlock_users'] ? '✓ Yes' : '✗ No') . "</li>";
        echo "<li>Can Reset Passwords: " . ($user['can_reset_passwords'] ? '✓ Yes' : '✗ No') . "</li>";
        echo "</ul>";
        
        echo "<p><strong>✓ All tests passed! The database is properly configured.</strong></p>";
        echo "<p><a href='admin/users.php'>Go to Users Management</a></p>";
    } else {
        echo "<p><strong>✗ No user found</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p><strong style='color: red;'>✗ Query failed:</strong></p>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
