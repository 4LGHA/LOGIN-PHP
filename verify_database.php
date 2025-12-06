<?php
// Verify database setup and test login credentials

echo "=================================================================\n";
echo "           DATABASE VERIFICATION & LOGIN TEST\n";
echo "=================================================================\n\n";

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'login_system';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connection: SUCCESS\n\n";
} catch (PDOException $e) {
    echo "✗ Database connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Check tables
echo "Checking tables...\n";
$tables = ['users', 'user_restrictions', 'login_attempts', 'user_sessions', 'activity_log'];
foreach ($tables as $table) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "  ✓ Table '$table' exists\n";
    } else {
        echo "  ✗ Table '$table' NOT FOUND\n";
    }
}

echo "\n";

// Check users
echo "Checking users...\n";
$stmt = $pdo->query("SELECT username, email, user_level, is_active, is_locked, failed_attempts FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "\n  Username: {$user['username']}\n";
    echo "  Email: {$user['email']}\n";
    echo "  Level: {$user['user_level']}\n";
    echo "  Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
    echo "  Locked: " . ($user['is_locked'] ? 'Yes' : 'No') . "\n";
    echo "  Failed Attempts: {$user['failed_attempts']}\n";
}

echo "\n=================================================================\n";
echo "           PASSWORD VERIFICATION TEST\n";
echo "=================================================================\n\n";

// Test admin password
$test_credentials = [
    ['username' => 'admin', 'password' => 'Admin@123'],
    ['username' => 'testuser', 'password' => 'User@123']
];

foreach ($test_credentials as $cred) {
    echo "Testing: {$cred['username']} / {$cred['password']}\n";
    
    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$cred['username']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        if (password_verify($cred['password'], $user['password'])) {
            echo "  ✓ Password verification: PASSED\n";
            echo "  ✓ Login will work!\n\n";
        } else {
            echo "  ✗ Password verification: FAILED\n";
            echo "  ✗ Login will NOT work!\n";
            echo "  Hash in DB: {$user['password']}\n\n";
        }
    } else {
        echo "  ✗ User not found in database\n\n";
    }
}

echo "=================================================================\n";
echo "           SUMMARY\n";
echo "=================================================================\n\n";

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "Total users in database: $total\n";
echo "\nDefault credentials:\n";
echo "  Admin:     admin / Admin@123\n";
echo "  Test User: testuser / User@123\n";
echo "\nAccess your system at:\n";
echo "  http://localhost/login-system/\n";
echo "\n=================================================================\n";
?>

