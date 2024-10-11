<?php
namespace App;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Cache;

class Auth
{
    private $cache;
    private $secretKey;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
        $this->secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key';
    }

    public function generateToken($userId, $roleId)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // Token valid for 1 hour

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'userId' => $userId,
            'roleId' => $roleId
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function hasPermission($token, $permission, $model)
    {
        $decoded = $this->validateToken($token);
        if (!$decoded) {
            return false;
        }

        $cacheKey = "permissions:{$decoded->userId}:{$decoded->roleId}";
        $permissions = $this->cache->get($cacheKey);

        if (!$permissions) {
            $permissions = $this->fetchPermissionsFromDatabase($decoded->roleId);
            $this->cache->set($cacheKey, $permissions, 3600); // Cache for 1 hour
        }

        return in_array($permission, $permissions);
    }

    private function fetchPermissionsFromDatabase($roleId)
    {
        // Implement database query to fetch permissions for the role
        // This is just a placeholder
        $pdo = new \PDO('mysql:host=db;dbname=lotr;charset=utf8mb4', 'root', 'root');
        $stmt = $pdo->prepare("SELECT p.name FROM permissions p 
                               JOIN role_permissions rp ON p.id = rp.permission_id 
                               WHERE rp.role_id = ?");
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}