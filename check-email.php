<?php
/**
 * AJAX endpoint to check email availability
 * Returns JSON response
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $email = sanitize($_POST['email'] ?? '');

    if (empty($email)) {
        echo json_encode([
            'available' => false,
            'message' => 'Email is required'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'available' => false,
            'message' => 'Invalid email format'
        ]);
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $result = $stmt->fetch();

    if ($result) {
        echo json_encode([
            'available' => false,
            'message' => 'Email already registered'
        ]);
    } else {
        echo json_encode([
            'available' => true,
            'message' => 'Email is available'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'available' => false,
        'message' => 'Error checking email: ' . $e->getMessage()
    ]);
}
?>
