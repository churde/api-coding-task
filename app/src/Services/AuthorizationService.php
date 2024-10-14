<?php

namespace App\Services;

use App\Services\PermissionChecker;

class AuthorizationService
{
    private PermissionChecker $permissionChecker;

    public function __construct(PermissionChecker $permissionChecker)
    {
        $this->permissionChecker = $permissionChecker;
    }

    public function hasPermission($userId, $roleId, $permission)
    {
        return $this->permissionChecker->hasPermission($userId, $roleId, $permission);
    }
}
