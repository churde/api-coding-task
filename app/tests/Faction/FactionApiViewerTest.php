<?php

namespace Tests\Faction;

use Tests\UnitTestBase;

class FactionApiViewerTest extends UnitTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->generateToken('viewer');
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
        $adminToken = $this->generateToken('admin');
        $newFaction = [
            'faction_name' => 'Faction to Update',
            'description' => 'A test faction'
        ];
        $createResponse = $this->makeRequest('POST', '/factions', $newFaction, true, $adminToken);
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
        $deleteResponse = $this->makeRequest('DELETE', "/factions/{$factionId}", null, true, $adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }

    public function testDeleteFactionNotAllowed()
    {
        // First, create faction with admin token
        $adminToken = $this->generateToken('admin');
        $newFaction = [
            'faction_name' => 'Faction to Delete',
            'description' => 'A test faction'
        ];
        $createResponse = $this->makeRequest('POST', '/factions', $newFaction, true, $adminToken);
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
        $adminDeleteResponse = $this->makeRequest('DELETE', "/factions/{$factionId}", null, true, $adminToken);
        $this->assertStringContainsString('204 No Content', $adminDeleteResponse['status']);
    }
}
