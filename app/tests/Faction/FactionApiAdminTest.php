<?php

namespace Tests\Faction;

use Tests\UnitTestBase;

class FactionApiAdminTest extends UnitTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->generateToken('admin'); // Admin role ID
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

        return $factionId;
    }

    /**
     * @depends testUpdateFaction
     */
    public function testDeleteFaction($factionId)
    {
        $response = $this->makeRequest('DELETE', "/factions/{$factionId}");
        $this->assertStringContainsString('204 No Content', $response['status']);

        // Verify that the faction has been deleted
        $response = $this->makeRequest('GET', "/factions/{$factionId}");
        $this->assertStringContainsString('404 Not Found', $response['status']);
        
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Faction not found', $data['error']);
    }

    public function testUnauthorizedAccess()
    {
        $response = $this->makeRequest('GET', '/factions', null, false);
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
        $url = $this->baseUrl . '/factions';
        $result = @file_get_contents($url, false, $context);
        $this->assertFalse($result);
        $this->assertStringContainsString('401 Unauthorized', $http_response_header[0]);
    }

    public function testSearchFactions()
    {
        $uniqueName = 'UniqueTestFaction_' . uniqid();
        $newFaction = [
            'faction_name' => $uniqueName,
            'description' => 'A unique test faction'
        ];

        $response = $this->makeRequest('POST', '/factions', $newFaction);
        $this->assertStringContainsString('201 Created', $response['status']);
        $createdFaction = json_decode($response['body'], true);
        $factionId = $createdFaction['id'];

        // Search for the faction
        $searchResponse = $this->makeRequest('GET', "/factions?search=" . urlencode($uniqueName));
        $this->assertStringContainsString('200 OK', $searchResponse['status']);
        $searchResult = json_decode($searchResponse['body'], true);

        // Assert that we have exactly one result
        $this->assertArrayHasKey('data', $searchResult);
        $this->assertCount(1, $searchResult['data']);
        $this->assertEquals($uniqueName, $searchResult['data'][0]['faction_name']);

        // Clean up: delete the created faction
        $deleteResponse = $this->makeRequest('DELETE', "/factions/$factionId");
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }
}
