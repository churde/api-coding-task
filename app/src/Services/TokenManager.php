<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenManager
{
    private string $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function generateToken($userId, $roleId)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 360000; // Token valid for 100 hour

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
            return JWT::decode($token, new Key($this->secretKey, 'HS256'));
        } catch (\Exception $e) {
            return false;
        }
    }
}
