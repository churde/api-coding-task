<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\Auth;
use App\Services\AuthenticationService;
use App\Services\AuthorizationService;

class CharacterApiViewerTest extends TestCase
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

    public function testGetAllCharacters()
    {
        $response = $this->makeRequest('GET', '/characters');
        $this->assertStringContainsString('200 OK', $response['status']);
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
    }

    public function testGetCharacterById()
    {
        $response = $this->makeRequest('GET', '/characters/1');
        $this->assertStringContainsString('200 OK', $response['status']);

        $data = json_decode($response['body'], true);

        if ($data === null) {
            echo "JSON decode error: " . json_last_error_msg() . "\n";
        }

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);
    }

    public function testCreateCharacterNotAllowed()
    {
        $newCharacter = [
            'name' => 'Test Character',
            'birth_date' => '2023-01-01',
            'kingdom' => 'Test Kingdom',
            'equipment_id' => 1,
            'faction_id' => 1
        ];

        $response = $this->makeRequest('POST', '/characters', $newCharacter);
        $this->assertStringContainsString('403 Forbidden', $response['status']);
        
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);

        
    }

    public function testUpdateCharacterNotAllowed()
    {
        // First, create a character with admin token
        $newCharacter = [
            'name' => 'Character to Update',
            'birth_date' => '2023-01-01',
            'kingdom' => 'Test Kingdom',
            'equipment_id' => 1,
            'faction_id' => 1
        ];
        $createResponse = $this->makeRequest('POST', '/characters', $newCharacter, true, $this->adminToken);
        $this->assertStringContainsString('201 Created', $createResponse['status']);
        $createdCharacter = json_decode($createResponse['body'], true);
        $characterId = $createdCharacter['id'];

        // Attempt to update with viewer token
        $updatedCharacter = [
            'name' => 'Updated Test Character',
            'birth_date' => '2023-02-02',
            'kingdom' => 'Updated Test Kingdom',
            'equipment_id' => 2,
            'faction_id' => 2
        ];

        $response = $this->makeRequest('PUT', "/characters/{$characterId}", $updatedCharacter);
        $this->assertStringContainsString('403 Forbidden', $response['status']);
        
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);

        // Clean up: Delete the character using admin token
        $deleteResponse = $this->makeRequest('DELETE', "/characters/{$characterId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }

    public function testDeleteCharacterNotAllowed()
    {
        // First, create a character with admin token
        $newCharacter = [
            'name' => 'Character to Delete',
            'birth_date' => '2023-01-01',
            'kingdom' => 'Test Kingdom',
            'equipment_id' => 1,
            'faction_id' => 1
        ];
        $createResponse = $this->makeRequest('POST', '/characters', $newCharacter, true, $this->adminToken);
        $this->assertStringContainsString('201 Created', $createResponse['status']);
        $createdCharacter = json_decode($createResponse['body'], true);
        $characterId = $createdCharacter['id'];

        // Attempt to delete with viewer token
        $deleteResponse = $this->makeRequest('DELETE', "/characters/{$characterId}");
        $this->assertStringContainsString('403 Forbidden', $deleteResponse['status']);
        
        $data = json_decode($deleteResponse['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);

        // Verify that the character still exists
        $getResponse = $this->makeRequest('GET', "/characters/{$characterId}");
        $this->assertStringContainsString('200 OK', $getResponse['status']);

        // Clean up: Delete the character using admin token
        $adminDeleteResponse = $this->makeRequest('DELETE', "/characters/{$characterId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $adminDeleteResponse['status']);
    }
}
