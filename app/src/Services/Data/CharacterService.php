<?php

namespace App\Services\Data;

use App\Interfaces\CharacterRepositoryInterface;
use App\Interfaces\CharacterServiceInterface;
use App\Services\Auth;
use App\Validators\CharacterValidator;
use Monolog\Logger;

class CharacterService implements CharacterServiceInterface
{
    private Auth $auth;
    private CharacterRepositoryInterface $characterRepository;
    private Logger $log;
    private CharacterValidator $characterValidator;

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

    public function getAllCharacters(string $token, int $page = 1, int $perPage = 10, ?string $searchTerm = null): array
    {
        if (!$this->auth->hasPermission($token, 'read')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Fetching all characters, Page: ' . $page . ', PerPage: ' . $perPage . ', SearchTerm: ' . ($searchTerm ?? 'None'));

        if ($searchTerm) {
            return $this->characterRepository->searchCharacters($searchTerm, $page, $perPage);
        }
        
        return $this->characterRepository->getAllWithRelations($page, $perPage);
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

        return $character;
    }

    public function createCharacter(string $token, array $data): array
    {
        $this->log->info('Attempting to create character with token: ' . substr($token, 0, 10) . '...');
        
        if (!$this->auth->hasPermission($token, 'create')) {
            $this->log->warning('Permission denied: User lacks "create" permission for character creation');
            throw new \Exception('Forbidden', 403);
        }
        
        $this->log->info('Permission check passed. Proceeding with character creation.');
        
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

        $this->log->info('Deleted character with ID: ' . $characterId);
    }
}
