<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\Auth;
use App\Services\AuthenticationService;
use App\Services\AuthorizationService;

class FactionApiViewerTest extends TestCase
{
    private string $baseUrl = 'http://localhost:8080/v1';
    private string $viewerToken;
    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->viewerToken = $this->generateToken(3); // Viewer role (assuming 3 is the viewer role ID)
        $this->adminToken = $this->generateToken(1);  // Admin role
    }

    private function generateToken($roleId)
    {
        $secretKey = getenv('JWT_SECRET_KEY') ?: 'your-secret-key';
        $tokenManager = new \App\Services\TokenManager($secretKey);
        return $tokenManager->generateToken(1, $roleId);
    }

    private function makeRequest($method, $endpoint, $data = null, $useToken = true, $token = null)
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
            $options['http']['header'][] = 'Authorization: Bearer ' . ($token ?: $this->viewerToken);
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

    public function testGetAllFactions()
    {
        $response = $this->makeRequest('GET', '/factions');
        $this->assertStringContainsString('200 OK', $response['status']);
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
    }

    public function testGetFactionById()
    {
        $response = $this->makeRequest('GET', '/factions/1');
        $this->assertStringContainsString('200 OK', $response['status']);

        $data = json_decode($response['body'], true);

        if ($data === null) {
            echo "JSON decode error: " . json_last_error_msg() . "\n";
        }

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);
        $this->assertArrayHasKey('faction_name', $data['data']);
        $this->assertArrayHasKey('description', $data['data']);
    }

    public function testCreateFactionNotAllowed()
    {
        $newFaction = [
            'faction_name' => 'Test Faction',
            'description' => 'A test faction'
        ];

        $response = $this->makeRequest('POST', '/factions', $newFaction);
        $this->assertStringContainsString('403 Forbidden', $response['status']);
        
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);
    }

    public function testUpdateFactionNotAllowed()
    {
        // First, create faction with admin token
        $newFaction = [
            'faction_name' => 'Faction to Update',
            'description' => 'A test faction'
        ];
        $createResponse = $this->makeRequest('POST', '/factions', $newFaction, true, $this->adminToken);
        $this->assertStringContainsString('201 Created', $createResponse['status']);
        $createdFaction = json_decode($createResponse['body'], true);
        $factionId = $createdFaction['id'];

        // Attempt to update with viewer token
        $updatedFaction = [
            'faction_name' => 'Updated Test Faction',
            'description' => 'An updated test faction'
        ];

        $response = $this->makeRequest('PUT', "/factions/{$factionId}", $updatedFaction);
        $this->assertStringContainsString('403 Forbidden', $response['status']);
        
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);

        // Clean up: Delete the faction using admin token
        $deleteResponse = $this->makeRequest('DELETE', "/factions/{$factionId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }

    public function testDeleteFactionNotAllowed()
    {
        // First, create faction with admin token
        $newFaction = [
            'faction_name' => 'Faction to Delete',
            'description' => 'A test faction'
        ];
        $createResponse = $this->makeRequest('POST', '/factions', $newFaction, true, $this->adminToken);
        $this->assertStringContainsString('201 Created', $createResponse['status']);
        $createdFaction = json_decode($createResponse['body'], true);
        $factionId = $createdFaction['id'];

        // Attempt to delete with viewer token
        $deleteResponse = $this->makeRequest('DELETE', "/factions/{$factionId}");
        $this->assertStringContainsString('403 Forbidden', $deleteResponse['status']);
        
        $data = json_decode($deleteResponse['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);

        // Verify that the faction still exists
        $getResponse = $this->makeRequest('GET', "/factions/{$factionId}");
        $this->assertStringContainsString('200 OK', $getResponse['status']);

        // Clean up: Delete the faction using admin token
        $adminDeleteResponse = $this->makeRequest('DELETE', "/factions/{$factionId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $adminDeleteResponse['status']);
    }
}
