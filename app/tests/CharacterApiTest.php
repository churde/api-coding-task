<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class CharacterApiTest extends TestCase
{
    private $baseUrl = 'http://localhost:8080';
    private $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3Mjg2NDM2MzEsImV4cCI6MTcyOTAwMzYzMSwidXNlcklkIjoiMSIsInJvbGVJZCI6IjEifQ.WuezLAZHRFrDIltAdnuS6BFJfUKWn06nbOyosL2bd4w'; // Admin
    // private $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3Mjg2NDA0NzUsImV4cCI6MTcyODY0NDA3NSwidXNlcklkIjoiMyIsInJvbGVJZCI6IjMifQ._o36t1HOOmG0eZa1yw2LBjYl7NhvE1oaad4Uq1W_zGA'; // Viewer

    private function makeRequest($method, $endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        $options = [
            'http' => [
                'method' => $method,
                'header' => [
                    'Authorization: Bearer ' . $this->token,
                    'Content-Type: application/json'
                ],
                'ignore_errors' => true
            ]
        ];

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
        $this->assertEmpty($response['body']); // Ensure the response body is empty for a 404
    }

    public function testUnauthorizedAccess()
    {
        $options = [
            'http' => [
                'method' => 'GET'
            ]
        ];
        $context = stream_context_create($options);
        $url = $this->baseUrl . '/characters';
        $result = @file_get_contents($url, false, $context);
        $this->assertFalse($result);
        $this->assertStringContainsString('401 Unauthorized', $http_response_header[0]);
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
}