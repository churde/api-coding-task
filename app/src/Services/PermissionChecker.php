<?php

namespace App\Services;

use App\Services\Cache;
use PDO;

class PermissionChecker
{
    private $cache;
    private $db;

    public function __construct(Cache $cache, PDO $db)
    {
        $this->cache = $cache;
        $this->db = $db;
    }

    public function hasPermission($userId, $roleId, $permission)
    {
        $cacheKey = "permissions:{$userId}:{$roleId}";
        $permissions = $this->cache->get($cacheKey);

        if (!$permissions) {
            $permissions = $this->fetchPermissionsFromDatabase($roleId);
            $this->cache->set($cacheKey, $permissions, 3600); // Cache for 1 hour
        }

        return in_array($permission, $permissions);
    }

    private function fetchPermissionsFromDatabase($roleId)
    {
        $stmt = $this->db->prepare("SELECT p.name FROM permissions p 
                               JOIN role_permissions rp ON p.id = rp.permission_id 
                               WHERE rp.role_id = ?");
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}