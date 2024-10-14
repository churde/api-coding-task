<?php

namespace Tests\Character;

use Tests\UnitTestBase;

class CharacterApiViewerTest extends UnitTestBase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->token = $this->generateToken('viewer');
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
        $adminToken = $this->generateToken('admin');
        $newCharacter = [
            'name' => 'Character to Update',
            'birth_date' => '2023-01-01',
            'kingdom' => 'Test Kingdom',
            'equipment_id' => 1,
            'faction_id' => 1
        ];
        $createResponse = $this->makeRequest('POST', '/characters', $newCharacter, true, $adminToken);
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
        $deleteResponse = $this->makeRequest('DELETE', "/characters/{$characterId}", null, true, $adminToken);
        $this->assertStringContainsString('204 No Content', $deleteResponse['status']);
    }

    public function testDeleteCharacterNotAllowed()
    {
        // First, create a character with admin token
        $adminToken = $this->generateToken('admin');
        $newCharacter = [
            'name' => 'Character to Delete',
            'birth_date' => '2023-01-01',
            'kingdom' => 'Test Kingdom',
            'equipment_id' => 1,
            'faction_id' => 1
        ];
        $createResponse = $this->makeRequest('POST', '/characters', $newCharacter, true, $adminToken);
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
        $adminDeleteResponse = $this->makeRequest('DELETE', "/characters/{$characterId}", null, true, $adminToken);
        $this->assertStringContainsString('204 No Content', $adminDeleteResponse['status']);
    }
}
