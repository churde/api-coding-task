<?php

namespace App\Services;

use App\Models\Character;
use App\Services\Auth;
use App\Cache;
use Monolog\Logger;

class CharacterService
{
    private $auth;
    private $cache;
    private $cacheConfig;
    private $characterModel;
    private $log;

    public function __construct(Auth $auth, Cache $cache, array $cacheConfig, Character $characterModel, Logger $log)
    {
        $this->auth = $auth;
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
        $this->characterModel = $characterModel;
        $this->log = $log;
    }

    public function getAllCharacters(string $token, int $page, int $perPage): array
    {
        if (!$this->auth->hasPermission($token, 'read', 'character')) {
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
            $result = $this->characterModel->getAllCharactersWithRelations($page, $perPage);

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
        if (!$this->auth->hasPermission($token, 'read', 'character')) {
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
            $character = $this->characterModel->getCharacterWithRelations($characterId);

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

    public function createCharacter(string $token, array $data): array
    {
        if (!$this->auth->hasPermission($token, 'create', 'character')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Creating a new character');
        $newCharacter = $this->characterModel->createCharacter($data);
        $this->log->info('Created character with ID: ' . $newCharacter['id']);

        return $newCharacter;
    }

    public function updateCharacter(string $token, int $characterId, array $data): array
    {
        if (!$this->auth->hasPermission($token, 'update', 'character')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Updating character with ID: ' . $characterId);
        $updatedCharacter = $this->characterModel->updateCharacter($characterId, $data);

        if (!$updatedCharacter) {
            throw new \Exception('Character not found', 404);
        }

        $this->cache->delete('character_with_relations_' . $characterId);
        $this->log->info('Updated character with ID: ' . $characterId);

        return $updatedCharacter;
    }

    public function deleteCharacter(string $token, int $characterId): void
    {
        if (!$this->auth->hasPermission($token, 'delete', 'character')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Deleting character with ID: ' . $characterId);
        $result = $this->characterModel->deleteCharacter($characterId);

        if (!$result) {
            throw new \Exception('Character not found', 404);
        }

        $this->cache->delete('character_with_relations_' . $characterId);
        $this->log->info('Deleted character with ID: ' . $characterId);
    }
}
