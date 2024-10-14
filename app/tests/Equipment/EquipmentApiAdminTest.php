<?php

namespace Tests\Equipment;

use Tests\UnitTestBase;

class EquipmentApiAdminTest extends UnitTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->generateToken('admin'); // Admin role ID
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

    public function testCreateEquipment()
    {
        $newEquipment = [
            'name' => 'Test Equipment',
            'type' => 'weapon',
            'made_by' => 'Test Maker'
        ];

        $response = $this->makeRequest('POST', '/equipment', $newEquipment);
        $this->assertStringContainsString('201 Created', $response['status']);
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($newEquipment['name'], $data['name']);

        return $data['id'];
    }

    /**
     * @depends testCreateEquipment
     */
    public function testUpdateEquipment($equipmentId)
    {
        $updatedEquipment = [
            'name' => 'Updated Test Equipment',
            'type' => 'armor',
            'made_by' => 'Updated Test Maker'
        ];

        $response = $this->makeRequest('PUT', "/equipment/{$equipmentId}", $updatedEquipment);
        $this->assertStringContainsString('200 OK', $response['status']);
        $data = json_decode($response['body'], true);
        $this->assertEquals($equipmentId, $data['id']);
        $this->assertEquals($updatedEquipment['name'], $data['name']);

        return $equipmentId;
    }

    /**
     * @depends testUpdateEquipment
     */
    public function testDeleteEquipment($equipmentId)
    {
        $response = $this->makeRequest('DELETE', "/equipment/{$equipmentId}");
        $this->assertStringContainsString('204 No Content', $response['status']);

        // Verify that the equipment has been deleted
        $response = $this->makeRequest('GET', "/equipment/{$equipmentId}");
        $this->assertStringContainsString('404 Not Found', $response['status']);
        
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Equipment not found', $data['error']);
    }

    public function testUnauthorizedAccess()
    {
        $response = $this->makeRequest('GET', '/equipment', null, false);
        $this->assertStringContainsString('401 Unauthorized', $response['status']);
        
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Unauthorized', $data['error']);
    }

    public function testInvalidToken()
    {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Bearer invalidtoken'
            ]
        ];
        $context = stream_context_create($options);
        $url = $this->baseUrl . '/equipment';
        $result = @file_get_contents($url, false, $context);
        $this->assertFalse($result);
        $this->assertStringContainsString('401 Unauthorized', $http_response_header[0]);
    }

    public function testSearchEquipment()
    {
        $uniqueName = 'UniqueTestEquipment_' . uniqid();
        $newEquipment = [
            'name' => $uniqueName,
            'type' => 'weapon',
            'made_by' => 'Test Maker'
        ];

        $response = $this->makeRequest('POST', '/equipment', $newEquipment);
        $this->assertStringContainsString('201 Created', $response['status']);
        $createdEquipment = json_decode($response['body'], true);
        $equipmentId = $createdEquipment['id'];

        // Search for the equipment
        $searchResponse = $this->makeRequest('GET', "/equipment?search=" . urlencode($uniqueName));
        $this->assertStringContainsString('200 OK', $searchResponse['status']);
        $searchResult = json_decode($searchResponse['body'], true);

        // Assert that we have exactly one result
        $this->assertArrayHasKey('data', $searchResult);
        $this->assertCount(1, $searchResult['data']);
        $this->assertEquals($uniqueName, $searchResult['data'][0]['name']);

        // Clean up: delete the created equipment
        $deleteResponse = $this->makeRequest('DELETE', "/equipment/$equipmentId");
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }
}
