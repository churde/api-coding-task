<?php

use PHPUnit\Framework\TestCase;

class CharacterApiTest extends TestCase {
    private $baseUrl = 'http://localhost:8080/characters';

    public function testGetAllCharacters() {
        $response = file_get_contents($this->baseUrl);
        $this->assertNotEmpty($response);
    }

   

    // Add more tests for update and delete...
}