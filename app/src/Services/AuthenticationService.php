<?php

namespace App\Services;

use App\Services\TokenManager;

class AuthenticationService
{
    private TokenManager $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    public function generateToken($userId, $roleId)
    {
        return $this->tokenManager->generateToken($userId, $roleId);
    }

    public function validateToken($token)
    {
        return $this->tokenManager->validateToken($token);
    }
}
