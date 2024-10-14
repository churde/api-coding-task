<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\Auth;
use App\Services\AuthenticationService;
use App\Services\AuthorizationService;

class EquipmentApiViewerTest extends TestCase
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

    public function testGetAllEquipment()
    {
        $response = $this->makeRequest('GET', '/equipment');
        $this->assertStringContainsString('200 OK', $response['status']);
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
    }

    public function testGetEquipmentById()
    {
        $response = $this->makeRequest('GET', '/equipment/1');
        $this->assertStringContainsString('200 OK', $response['status']);

        $data = json_decode($response['body'], true);

        if ($data === null) {
            echo "JSON decode error: " . json_last_error_msg() . "\n";
        }

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);
    }

    public function testCreateEquipmentNotAllowed()
    {
        $newEquipment = [
            'name' => 'Test Equipment',
            'type' => 'weapon',
            'made_by' => 'Test Maker'
        ];

        $response = $this->makeRequest('POST', '/equipment', $newEquipment);
        $this->assertStringContainsString('403 Forbidden', $response['status']);
        
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);
    }

    public function testUpdateEquipmentNotAllowed()
    {
        // First, create equipment with admin token
        $newEquipment = [
            'name' => 'Equipment to Update',
            'type' => 'weapon',
            'made_by' => 'Test Maker'
        ];
        $createResponse = $this->makeRequest('POST', '/equipment', $newEquipment, true, $this->adminToken);
        $this->assertStringContainsString('201 Created', $createResponse['status']);
        $createdEquipment = json_decode($createResponse['body'], true);
        $equipmentId = $createdEquipment['id'];

        // Attempt to update with viewer token
        $updatedEquipment = [
            'name' => 'Updated Test Equipment',
            'type' => 'armor',
            'made_by' => 'Updated Test Maker'
        ];

        $response = $this->makeRequest('PUT', "/equipment/{$equipmentId}", $updatedEquipment);
        $this->assertStringContainsString('403 Forbidden', $response['status']);
        
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);

        // Clean up: Delete the equipment using admin token
        $deleteResponse = $this->makeRequest('DELETE', "/equipment/{$equipmentId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }

    public function testDeleteEquipmentNotAllowed()
    {
        // First, create equipment with admin token
        $newEquipment = [
            'name' => 'Equipment to Delete',
            'type' => 'weapon',
            'made_by' => 'Test Maker'
        ];
        $createResponse = $this->makeRequest('POST', '/equipment', $newEquipment, true, $this->adminToken);
        $this->assertStringContainsString('201 Created', $createResponse['status']);
        $createdEquipment = json_decode($createResponse['body'], true);
        $equipmentId = $createdEquipment['id'];

        // Attempt to delete with viewer token
        $deleteResponse = $this->makeRequest('DELETE', "/equipment/{$equipmentId}");
        $this->assertStringContainsString('403 Forbidden', $deleteResponse['status']);
        
        $data = json_decode($deleteResponse['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);

        // Verify that the equipment still exists
        $getResponse = $this->makeRequest('GET', "/equipment/{$equipmentId}");
        $this->assertStringContainsString('200 OK', $getResponse['status']);

        // Clean up: Delete the equipment using admin token
        $adminDeleteResponse = $this->makeRequest('DELETE', "/equipment/{$equipmentId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $adminDeleteResponse['status']);
    }
}
