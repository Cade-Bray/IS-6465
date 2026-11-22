<?php
/**
 * CLI Utility: Create or Reset Administrator Account
 * IS 6465 Lab 7
 * Usage: php database/create-admin.php [name] [email] [password]
 */
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/repositories/userPDO.php';

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('CLI execution only.');
}

$name = $argv[1] ?? 'Admin Alice';
$email = $argv[2] ?? 'admin@example.edu';
$password = $argv[3] ?? 'AdminPass123!';

$pdo = openDatabase();
$existing = findUserByEmail($pdo, $email);
$hash = password_hash($password, PASSWORD_DEFAULT);

if ($existing) {
    updateUserPassword($pdo, (int)$existing['user_id'], $hash);
    updateUserRole($pdo, (int)$existing['user_id'], 'Administrator');
    updateUserStatus($pdo, (int)$existing['user_id'], 'Active');
    echo "Updated existing administrator account: $email" . PHP_EOL;
} else {
    insertUser($pdo, $name, $email, $hash, 'Administrator', 'Active');
    echo "Created new administrator account: $email" . PHP_EOL;
}

