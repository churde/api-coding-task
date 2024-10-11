<?php
require_once __DIR__ . '/../vendor/autoload.php';

use DI\Container;
use App\Auth;
use App\Cache;

$container = new Container();

$container->set('db', function () {
    return new PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
});

$container->set('cache', function () {
    return new Cache();
});

$container->set('cacheConfig', function () {
    return require __DIR__ . '/../config/cache_config.php';
});

$container->set('auth', function ($c) {
    return new Auth($c->get('cache'), $c->get('db'), $c->get('cacheConfig'));
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