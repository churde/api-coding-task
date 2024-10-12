<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Character;
use App\Services\Auth;
use App\Cache;
use Monolog\Logger;

class CharacterController
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

    public function getAllCharacters(Request $request, Response $response): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        if (!$this->auth->hasPermission($token, 'read', 'character')) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int)$queryParams['per_page'] : 10;

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

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getCharacterById(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        if (!$this->auth->hasPermission($token, 'read', 'character')) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $characterId = $args['id'];
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
            $this->log->warning('Character not found with ID: ' . $characterId);
            return $response->withStatus(404);
        }

        $result = [
            'data' => $character,
            'meta' => [
                'cache_used' => $cacheUsed
            ]
        ];

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function createCharacter(Request $request, Response $response): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        if (!$this->auth->hasPermission($token, 'create', 'character')) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $this->log->info('Creating a new character');
        $data = $request->getParsedBody();
        $newCharacter = $this->characterModel->createCharacter($data);

        $this->log->info('Created character with ID: ' . $newCharacter['id']);
        $response->getBody()->write(json_encode($newCharacter));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function updateCharacter(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        if (!$this->auth->hasPermission($token, 'update', 'character')) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $characterId = $args['id'];
        $this->log->info('Updating character with ID: ' . $characterId);
        $data = $request->getParsedBody();
        $updatedCharacter = $this->characterModel->updateCharacter($characterId, $data);

        if (!$updatedCharacter) {
            $this->log->warning('Character not found for update with ID: ' . $characterId);
            return $response->withStatus(404);
        }

        $this->cache->delete('character_with_relations_' . $characterId);
        $this->log->info('Updated character with ID: ' . $characterId);
        $response->getBody()->write(json_encode($updatedCharacter));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function deleteCharacter(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        if (!$this->auth->hasPermission($token, 'delete', 'character')) {
            $response->getBody()->write(json_encode(['error' => 'Forbidden']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }

        $characterId = $args['id'];
        $this->log->info('Deleting character with ID: ' . $characterId);
        $result = $this->characterModel->deleteCharacter($characterId);

        if (!$result) {
            $this->log->warning('Character not found for deletion with ID: ' . $characterId);
            return $response->withStatus(404);
        }

        $this->cache->delete('character_with_relations_' . $characterId);
        $this->log->info('Deleted character with ID: ' . $characterId);
        return $response->withStatus(204);
    }
}