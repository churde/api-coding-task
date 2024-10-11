<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Auth;
use App\Cache;

$cache = new Cache();

// Create Auth instance
$auth = new Auth($cache);

// Check if user ID and role ID are provided as command-line arguments
if ($argc !== 3) {
    echo "Usage: php generate_token.php <user_id> <role_id>\n";
    exit(1);
}

$userId = $argv[1];
$roleId = $argv[2];

// Generate the token
$token = $auth->generateToken($userId, $roleId);

echo "Generated token: " . $token . "\n";
echo "User ID: " . $userId . "\n";
echo "Role ID: " . $roleId . "\n";