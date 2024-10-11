<?php

namespace App;

use App\Services\TokenManager;
use App\Services\PermissionChecker;

class Auth
{
    private $tokenManager;
    private $permissionChecker;

    public function __construct(TokenManager $tokenManager, PermissionChecker $permissionChecker)
    {
        $this->tokenManager = $tokenManager;
        $this->permissionChecker = $permissionChecker;
    }

    public function generateToken($userId, $roleId)
    {
        return $this->tokenManager->generateToken($userId, $roleId);
    }

    public function validateToken($token)
    {
        return $this->tokenManager->validateToken($token);
    }

    public function hasPermission($token, $permission, $model)
    {
        $decoded = $this->validateToken($token);
        if (!$decoded) {
            return false;
        }

        return $this->permissionChecker->hasPermission($decoded->userId, $decoded->roleId, $permission);
    }
}