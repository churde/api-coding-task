<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\TokenManager;

// Check if user ID and role ID are provided as command-line arguments
if ($argc !== 3) {
    echo "Usage: php generate_token.php <user_id> <role_id>\n";
    exit(1);
}

$userId = $argv[1];
$roleId = $argv[2];

// Create TokenManager instance
$secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key';
$tokenManager = new TokenManager($secretKey);

// Generate the token
$token = $tokenManager->generateToken($userId, $roleId);

echo "Generated token: " . $token . "\n";