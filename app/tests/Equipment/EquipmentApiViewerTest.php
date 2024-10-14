<?php

namespace Tests\Equipment;

use Tests\UnitTestBase;

class EquipmentApiViewerTest extends UnitTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->generateToken('viewer');
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
        $adminToken = $this->generateToken('admin');
        $newEquipment = [
            'name' => 'Equipment to Update',
            'type' => 'weapon',
            'made_by' => 'Test Maker'
        ];
        $createResponse = $this->makeRequest('POST', '/equipment', $newEquipment, true, $adminToken);
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
        $deleteResponse = $this->makeRequest('DELETE', "/equipment/{$equipmentId}", null, true, $adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }

    public function testDeleteEquipmentNotAllowed()
    {
        // First, create equipment with admin token
        $adminToken = $this->generateToken('admin');
        $newEquipment = [
            'name' => 'Equipment to Delete',
            'type' => 'weapon',
            'made_by' => 'Test Maker'
        ];
        $createResponse = $this->makeRequest('POST', '/equipment', $newEquipment, true, $adminToken);
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
        $adminDeleteResponse = $this->makeRequest('DELETE', "/equipment/{$equipmentId}", null, true, $adminToken);
        $this->assertStringContainsString('204 No Content', $adminDeleteResponse['status']);
    }
}
