<?php

namespace App\Services;

use App\Repositories\CharacterRepositoryInterface;
use App\Services\Auth;
use Monolog\Logger;
use App\Validators\CharacterValidator;

class CharacterService
{
    private $auth;
    private $characterRepository;
    private $log;
    private $characterValidator;

    public function __construct(
        Auth $auth,
        CharacterRepositoryInterface $characterRepository,
        Logger $log,
        CharacterValidator $characterValidator
    ) {
        $this->auth = $auth;
        $this->characterRepository = $characterRepository;
        $this->log = $log;
        $this->characterValidator = $characterValidator;
    }

    public function getAllCharacters(string $token, int $page, int $perPage): array
    {
        if (!$this->auth->hasPermission($token, 'read')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Fetching all characters with relations');
        $result = $this->characterRepository->getAllWithRelations($page, $perPage);

        return $result;
    }

    public function getCharacterById(string $token, int $characterId): array
    {
        if (!$this->auth->hasPermission($token, 'read')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Fetching character with relations, ID: ' . $characterId);
        $character = $this->characterRepository->getByIdWithRelations($characterId);

        if (!$character) {
            throw new \Exception('Character not found', 404);
        }

        return [
            'data' => $character
        ];
    }

    public function createCharacter(array $data): array
    {
        // Validate foreign keys
        $foreignKeyErrors = $this->characterValidator->validateForeignKeys($data);

        if (!empty($foreignKeyErrors)) {
            return ['errors' => $foreignKeyErrors];
        }

        // If foreign key validation passes, proceed with character creation
        $character = $this->characterRepository->create($data);

        if (!$character) {
            return ['errors' => ['Failed to create character']];
        }

        return $character;
    }

    public function updateCharacter(string $token, int $characterId, array $data): array
    {
        if (!$this->auth->hasPermission($token, 'update')) {
            throw new \Exception('Forbidden', 403);
        }

        // Validate foreign keys only for the fields that are being updated
        $foreignKeyErrors = $this->characterValidator->validateForeignKeys(array_intersect_key($data, array_flip(['equipment_id', 'faction_id'])));

        if (!empty($foreignKeyErrors)) {
            throw new \Exception('Invalid foreign keys: ' . implode(', ', $foreignKeyErrors), 400);
        }

        $this->log->info('Updating character with ID: ' . $characterId);
        $updatedCharacter = $this->characterRepository->update($characterId, $data);

        if (!$updatedCharacter) {
            throw new \Exception('Character not found', 404);
        }

        $this->cache->delete('character_with_relations_' . $characterId);
        $this->log->info('Updated character with ID: ' . $characterId);

        return $updatedCharacter;
    }

    public function deleteCharacter(string $token, int $characterId): void
    {
        if (!$this->auth->hasPermission($token, 'delete')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Deleting character with ID: ' . $characterId);
        $result = $this->characterRepository->delete($characterId);

        if (!$result) {
            $this->log->warning('Character not found for deletion, ID: ' . $characterId);
            throw new \Exception('Character not found', 404);
        }

        $this->cache->delete('character_with_relations_' . $characterId);
        $this->log->info('Deleted character with ID: ' . $characterId);
    }
}
