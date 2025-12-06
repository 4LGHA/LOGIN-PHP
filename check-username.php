<?php
/**
 * AJAX endpoint to check username availability
 * Returns JSON response
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $username = sanitize($_POST['username'] ?? '');

    if (empty($username)) {
        echo json_encode([
            'available' => false,
            'message' => 'Username is required'
        ]);
        exit;
    }

    if (strlen($username) < 3) {
        echo json_encode([
            'available' => false,
            'message' => 'Username must be at least 3 characters'
        ]);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
        echo json_encode([
            'available' => false,
            'message' => 'Invalid username format'
        ]);
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $result = $stmt->fetch();

    if ($result) {
        echo json_encode([
            'available' => false,
            'message' => 'Username already taken'
        ]);
    } else {
        echo json_encode([
            'available' => true,
            'message' => 'Username is available'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'available' => false,
        'message' => 'Error checking username: ' . $e->getMessage()
    ]);
}
?>
