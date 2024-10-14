<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class UnitTestBase extends TestCase
{
    protected string $baseUrl = 'http://localhost:8080/v1';
    protected ?string $token = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function generateToken($role)
    {
        $secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key';
        $tokenManager = new \App\Services\TokenManager($secretKey);
        $roleId = $this->getRoleId($role);
        return $tokenManager->generateToken($roleId, $roleId);
    }

    private function getRoleId($role)
    {
        switch ($role) {
            case 'admin':
                return 1;
            case 'editor':
                return 2;
            case 'viewer':
                return 3;
            default:
                throw new \InvalidArgumentException("Invalid role: $role");
        }
    }

    protected function makeRequest($method, $endpoint, $data = null, $useToken = true, $token = null)
    {
        $url = $this->baseUrl . $endpoint;
        $options = [
            'http' => [
                'method' => $method,
                'header' => [
                    'Content-Type: application/json'
                ],
                'ignore_errors' => true
            ]
        ];

        if ($useToken) {
            $options['http']['header'][] = 'Authorization: Bearer ' . ($token ?: $this->token);
        }

        if ($data !== null) {
            $options['http']['content'] = json_encode($data);
        }

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return [
            'status' => $http_response_header[0],
            'body' => $result
        ];
    }
}
