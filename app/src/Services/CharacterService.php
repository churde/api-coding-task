<?php

namespace App\Services;

use App\Repositories\CharacterRepositoryInterface;
use App\Services\Auth;
use App\Cache;
use Monolog\Logger;
use App\Validators\CharacterValidator;

class CharacterService
{
    private $auth;
    private $cache;
    private $cacheConfig;
    private $characterRepository;
    private $log;
    private $characterValidator;

    public function __construct(
        Auth $auth,
        Cache $cache,
        array $cacheConfig,
        CharacterRepositoryInterface $characterRepository,
        Logger $log,
        CharacterValidator $characterValidator
    ) {
        $this->auth = $auth;
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
        $this->characterRepository = $characterRepository;
        $this->log = $log;
        $this->characterValidator = $characterValidator;
    }

    public function getAllCharacters(string $token, int $page, int $perPage): array
    {
        if (!$this->auth->hasPermission($token, 'read')) {
            throw new \Exception('Forbidden', 403);
        }

        $cacheKey = "all_characters_with_relations_page_{$page}_perPage_{$perPage}";
        $cachedData = null;
        $cacheUsed = false;

        if ($this->cacheConfig['enable_cache']['get_all_characters']) {
            $cachedData = $this->cache->get($cacheKey);
        }

        if ($cachedData) {
            $this->log->info('Returning cached characters with relations');
            $result = $cachedData;
            $cacheUsed = true;
        } else {
            $this->log->info('Fetching all characters with relations from database');
            $result = $this->characterRepository->getAllWithRelations($page, $perPage);

            if ($this->cacheConfig['enable_cache']['get_all_characters']) {
                $this->cache->set($cacheKey, $result, $this->cacheConfig['cache_ttl']);
                $this->log->info('Cached characters with relations for page ' . $page);
            }
        }

        $result['meta']['cache_used'] = $cacheUsed;
        return $result;
    }

    public function getCharacterById(string $token, int $characterId): array
    {
        if (!$this->auth->hasPermission($token, 'read')) {
            throw new \Exception('Forbidden', 403);
        }

        $cacheKey = 'character_with_relations_' . $characterId;
        $cachedData = null;
        $cacheUsed = false;

        if ($this->cacheConfig['enable_cache']['get_character_by_id']) {
            $cachedData = $this->cache->get($cacheKey);
        }

        if ($cachedData) {
            $this->log->info('Returning cached character with relations, ID: ' . $characterId);
            $character = $cachedData;
            $cacheUsed = true;
        } else {
            $this->log->info('Fetching character with relations, ID: ' . $characterId);
            $character = $this->characterRepository->getByIdWithRelations($characterId);

            if ($character && $this->cacheConfig['enable_cache']['get_character_by_id']) {
                $this->cache->set($cacheKey, $character, $this->cacheConfig['cache_ttl']);
                $this->log->info('Cached character with relations, ID: ' . $characterId);
            }
        }

        if (!$character) {
            throw new \Exception('Character not found', 404);
        }

        return [
            'data' => $character,
            'meta' => [
                'cache_used' => $cacheUsed
            ]
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
