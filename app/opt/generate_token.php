<?php
require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use App\Services\Auth;
use App\Services\TokenManager;
use App\Services\PermissionChecker;
use App\Cache; // Use the correct cache class
use Predis\Client;

// Create a DI container
$container = new Container();

$container->set('db', function () {
    return new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
});

$container->set('cache', function () {
    return new Cache(); // Use the correct cache class
});

$container->set('tokenManager', function () {
    $secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key';
    return new TokenManager($secretKey);
});

$container->set('permissionChecker', function ($c) {
    return new PermissionChecker($c->get('cache'), $c->get('db'));
});

$container->set('auth', function ($c) {
    return new Auth($c->get('tokenManager'), $c->get('permissionChecker'));
});

$auth = $container->get('auth');

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