<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\CharacterService;
use OpenApi\Annotations as OA;
use InvalidArgumentException;

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
            return $this->handleException($response, $e);
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
            return $this->handleException($response, $e);
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
            return $this->handleException($response, $e);
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
            return $this->handleException($response, $e);
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
            return $this->handleException($response, $e);
        }
    }

    private function handleException(Response $response, \Exception $e): Response
    {
        $statusCode = $this->getHttpStatusCode($e);
        $errorMessage = $this->getErrorMessage($e);

        $response->getBody()->write(json_encode(['error' => $errorMessage]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    private function getHttpStatusCode(\Exception $e): int
    {
        if ($e instanceof \PDOException) {
            // Map database error codes to HTTP status codes
            switch ($e->getCode()) {
                case '23000': // Integrity constraint violation
                    return 400; // Bad Request
                case '42S02': // Base table or view not found
                    return 404; // Not Found
                default:
                    return 500; // Internal Server Error
            }
        }

        // For custom exceptions, you can add more specific mappings
        // For example:
        // if ($e instanceof \App\Exceptions\UnauthorizedException) {
        //     return 401;
        // }

        // Default to Internal Server Error for unhandled exceptions
        return 500;
    }

    private function getErrorMessage(\Exception $e): string
    {
        // In production, you might want to return generic error messages
        // instead of exposing internal error details
        if (getenv('APP_ENV') === 'production') {
            return 'An error occurred while processing your request.';
        }

        return $e->getMessage();
    }
}
