<?php

namespace App\Services;

use App\Services\PermissionChecker;

class AuthorizationService
{
    private $permissionChecker;

    public function __construct(PermissionChecker $permissionChecker)
    {
        $this->permissionChecker = $permissionChecker;
    }

    public function hasPermission($userId, $roleId, $permission, $model)
    {
        return $this->permissionChecker->hasPermission($userId, $roleId, $permission, $model);
    }
}
