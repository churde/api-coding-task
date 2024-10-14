<?php

namespace Tests\Equipment;

use Tests\UnitTestBase;

class EquipmentApiEditorTest extends UnitTestBase
{
    private $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->generateToken('editor');
        $this->adminToken = $this->generateToken('admin');
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

        // Delete the equipment using admin token
        $deleteResponse = $this->makeRequest('DELETE', "/equipment/{$equipmentId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);

        return $equipmentId;
    }

    public function testDeleteEquipmentNotAllowed()
    {
        $newEquipment = [
            'name' => 'Equipment to Delete',
            'type' => 'weapon',
            'made_by' => 'Test Maker'
        ];

        $createResponse = $this->makeRequest('POST', '/equipment', $newEquipment);
        $this->assertStringContainsString('201 Created', $createResponse['status']);
        $createdEquipment = json_decode($createResponse['body'], true);
        $equipmentId = $createdEquipment['id'];

        $deleteResponse = $this->makeRequest('DELETE', "/equipment/{$equipmentId}");
        $this->assertStringContainsString('403 Forbidden', $deleteResponse['status']);
        
        $data = json_decode($deleteResponse['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);

        // Verify that the equipment still exists
        $getResponse = $this->makeRequest('GET', "/equipment/{$equipmentId}");
        $this->assertStringContainsString('200 OK', $getResponse['status']);

        // Delete the equipment using admin token
        $deleteResponse = $this->makeRequest('DELETE', "/equipment/{$equipmentId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }

    public function testDeleteEquipmentAsAdmin()
    {
        $newEquipment = [
            'name' => 'Equipment to Delete',
            'type' => 'weapon',
            'made_by' => 'Test Maker'
        ];

        $createResponse = $this->makeRequest('POST', '/equipment', $newEquipment);
        $this->assertStringContainsString('201 Created', $createResponse['status']);
        $createdEquipment = json_decode($createResponse['body'], true);
        $equipmentId = $createdEquipment['id'];

        $deleteResponse = $this->makeRequest('DELETE', "/equipment/{$equipmentId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);

        // Verify that the equipment no longer exists
        $getResponse = $this->makeRequest('GET', "/equipment/{$equipmentId}");
        $this->assertStringContainsString('404 Not Found', $getResponse['status']);
    }
}
