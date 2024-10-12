<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\CharacterService;

class CharacterController
{
    private $characterService;

    public function __construct(CharacterService $characterService)
    {
        $this->characterService = $characterService;
    }

    public function getAllCharacters(Request $request, Response $response): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int)$queryParams['per_page'] : 10;

        try {
            $result = $this->characterService->getAllCharacters($token, $page, $perPage);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }

    public function getCharacterById(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $characterId = $args['id'];

        try {
            $result = $this->characterService->getCharacterById($token, $characterId);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }

    public function createCharacter(Request $request, Response $response): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $data = $request->getParsedBody();

        try {
            $newCharacter = $this->characterService->createCharacter($token, $data);
            $response->getBody()->write(json_encode($newCharacter));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }

    public function updateCharacter(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $characterId = $args['id'];
        $data = $request->getParsedBody();

        try {
            $updatedCharacter = $this->characterService->updateCharacter($token, $characterId, $data);
            $response->getBody()->write(json_encode($updatedCharacter));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }

    public function deleteCharacter(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $characterId = $args['id'];

        try {
            $this->characterService->deleteCharacter($token, $characterId);
            return $response->withStatus(204);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($e->getCode());
        }
    }
}