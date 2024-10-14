<?php

namespace Tests\Faction;

use Tests\UnitTestBase;

class FactionApiEditorTest extends UnitTestBase
{
    private $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->generateToken('editor');
        $this->adminToken = $this->generateToken('admin');
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
    }

    public function testCreateFaction()
    {
        $newFaction = [
            'faction_name' => 'Test Faction',
            'description' => 'A test faction'
        ];

        $response = $this->makeRequest('POST', '/factions', $newFaction);
        $this->assertStringContainsString('201 Created', $response['status']);
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($newFaction['faction_name'], $data['faction_name']);

        return $data['id'];
    }

    /**
     * @depends testCreateFaction
     */
    public function testUpdateFaction($factionId)
    {
        $updatedFaction = [
            'faction_name' => 'Updated Test Faction',
            'description' => 'An updated test faction'
        ];

        $response = $this->makeRequest('PUT', "/factions/{$factionId}", $updatedFaction);
        $this->assertStringContainsString('200 OK', $response['status']);
        $data = json_decode($response['body'], true);
        $this->assertEquals($factionId, $data['id']);
        $this->assertEquals($updatedFaction['faction_name'], $data['faction_name']);

        // Delete the faction using admin token
        $deleteResponse = $this->makeRequest('DELETE', "/factions/{$factionId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);

        return $factionId;
    }

    public function testDeleteFactionNotAllowed()
    {
        $newFaction = [
            'faction_name' => 'Faction to Delete',
            'description' => 'A test faction to delete'
        ];

        $createResponse = $this->makeRequest('POST', '/factions', $newFaction);
        $this->assertStringContainsString('201 Created', $createResponse['status']);
        $createdFaction = json_decode($createResponse['body'], true);
        $factionId = $createdFaction['id'];

        $deleteResponse = $this->makeRequest('DELETE', "/factions/{$factionId}");
        $this->assertStringContainsString('403 Forbidden', $deleteResponse['status']);
        
        $data = json_decode($deleteResponse['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Forbidden', $data['error']);

        // Verify that the faction still exists
        $getResponse = $this->makeRequest('GET', "/factions/{$factionId}");
        $this->assertStringContainsString('200 OK', $getResponse['status']);

        // Delete the faction using admin token
        $deleteResponse = $this->makeRequest('DELETE', "/factions/{$factionId}", null, true, $this->adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }

    
}
