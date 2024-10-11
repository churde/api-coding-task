<?php
namespace App;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Cache;
use PDO;

class Auth
{
    private $cache;
    private $secretKey;
    private $db;
    private $config;

    public function __construct(Cache $cache, PDO $db, array $config)
    {
        $this->cache = $cache;
        $this->secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key';
        $this->db = $db;
        $this->config = $config;
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
        $stmt = $this->db->prepare("SELECT p.name FROM permissions p 
                               JOIN role_permissions rp ON p.id = rp.permission_id 
                               WHERE rp.role_id = ?");
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}