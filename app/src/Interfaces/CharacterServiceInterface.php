<?php

namespace App\Interfaces;

interface CharacterServiceInterface
{
    /**
     * Get all characters with pagination
     *
     * @param string $token
     * @param int $page
     * @param int $perPage
     * @return array
     * @throws \Exception
     */
    public function getAllCharacters(string $token, int $page, int $perPage): array;

    /**
     * Get a character by ID
     *
     * @param string $token
     * @param int $characterId
     * @return array
     * @throws \Exception
     */
    public function getCharacterById(string $token, int $characterId): array;

    /**
     * Create a new character
     *
     * @param array $data
     * @return array
     */
    public function createCharacter(array $data): array;

    /**
     * Update an existing character
     *
     * @param string $token
     * @param int $characterId
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function updateCharacter(string $token, int $characterId, array $data): array;

    /**
     * Delete a character
     *
     * @param string $token
     * @param int $characterId
     * @return void
     * @throws \Exception
     */
    public function deleteCharacter(string $token, int $characterId): void;
}
