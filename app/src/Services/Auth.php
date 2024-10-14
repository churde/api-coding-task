<?php

namespace App\Services;

use App\Services\AuthenticationService;
use App\Services\AuthorizationService;

class Auth
{
    private $authenticationService;
    private $authorizationService;

    public function __construct(AuthenticationService $authenticationService, AuthorizationService $authorizationService)
    {
        $this->authenticationService = $authenticationService;
        $this->authorizationService = $authorizationService;
    }

    public function generateToken($userId, $roleId)
    {
        return $this->authenticationService->generateToken($userId, $roleId);
    }

    public function validateToken($token)
    {
        return $this->authenticationService->validateToken($token);
    }

    public function hasPermission($token, $permission)
    {
        $decoded = $this->validateToken($token);
        if (!$decoded) {
            return false;
        }

        return $this->authorizationService->hasPermission($decoded->userId, $decoded->roleId, $permission);
    }
}
