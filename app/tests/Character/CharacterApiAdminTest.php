<?php

namespace Tests\Character;

use Tests\UnitTestBase;

class CharacterApiAdminTest extends UnitTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->generateToken('admin'); // Admin role ID
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

        // Debugging: Check if $data is null
        if ($data === null) {
            echo "JSON decode error: " . json_last_error_msg() . "\n";
        }

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data']);
    }

    public function testCreateCharacter()
    {
        $newCharacter = [
            'name' => 'Test Character',
            'birth_date' => '2023-01-01',
            'kingdom' => 'Test Kingdom',
            'equipment_id' => 1,
            'faction_id' => 1
        ];

        $response = $this->makeRequest('POST', '/characters', $newCharacter);
        $this->assertStringContainsString('201 Created', $response['status']);
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals($newCharacter['name'], $data['name']);

        return $data['id'];
    }

    /**
     * @depends testCreateCharacter
     */
    public function testUpdateCharacter($characterId)
    {
        $updatedCharacter = [
            'name' => 'Updated Test Character',
            'birth_date' => '2023-02-02',
            'kingdom' => 'Updated Test Kingdom',
            'equipment_id' => 2,
            'faction_id' => 2
        ];

        $response = $this->makeRequest('PUT', "/characters/{$characterId}", $updatedCharacter);
        $this->assertStringContainsString('200 OK', $response['status']);
        $data = json_decode($response['body'], true);
        $this->assertEquals($characterId, $data['id']);
        $this->assertEquals($updatedCharacter['name'], $data['name']);

        return $characterId;
    }

    /**
     * @depends testUpdateCharacter
     */
    public function testDeleteCharacter($characterId)
    {
        $response = $this->makeRequest('DELETE', "/characters/{$characterId}");
        $this->assertStringContainsString('204 No Content', $response['status']);

        // Verify that the character has been deleted
        $response = $this->makeRequest('GET', "/characters/{$characterId}");
        $this->assertStringContainsString('404 Not Found', $response['status']);
        
        // Check if the response body contains an error message
        $data = json_decode($response['body'], true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Character not found', $data['error']);
    }

    public function testUnauthorizedAccess()
    {
        $response = $this->makeRequest('GET', '/characters', null, false);
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
        $url = $this->baseUrl . '/characters';
        $result = @file_get_contents($url, false, $context);
        $this->assertFalse($result);
        $this->assertStringContainsString('401 Unauthorized', $http_response_header[0]);
    }

    public function testSearchCharacter()
    {
        // Create a character with a unique name
        $uniqueName = 'UniqueTestCharacter_' . uniqid();
        $newCharacter = [
            'name' => $uniqueName,
            'birth_date' => '2023-01-01',
            'kingdom' => 'Test Kingdom',
            'equipment_id' => 1,
            'faction_id' => 1
        ];

        $response = $this->makeRequest('POST', '/characters', $newCharacter);
        $this->assertStringContainsString('201 Created', $response['status']);
        $createdCharacter = json_decode($response['body'], true);
        $characterId = $createdCharacter['id'];

        // Search for the character
        $searchResponse = $this->makeRequest('GET', "/characters?search=$uniqueName");
        $this->assertStringContainsString('200 OK', $searchResponse['status']);
        $searchResult = json_decode($searchResponse['body'], true);

        // Assert that we have exactly one result
        $this->assertArrayHasKey('data', $searchResult);
        $this->assertCount(1, $searchResult['data']);
        $this->assertEquals($uniqueName, $searchResult['data'][0]['name']);

        // Clean up: delete the created character
        $deleteResponse = $this->makeRequest('DELETE', "/characters/$characterId");
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }
}
