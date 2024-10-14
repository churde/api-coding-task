<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\CharacterService;
use App\Validators\CharacterValidator;

class CharacterController
{
    private $characterService;
    private $characterValidator;

    public function __construct(CharacterService $characterService, CharacterValidator $characterValidator)
    {
        $this->characterService = $characterService;
        $this->characterValidator = $characterValidator;
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
        $data = $request->getParsedBody();

        // Perform basic input validation for create
        $validationErrors = $this->characterValidator->validateCreateInput($data);

        if (!empty($validationErrors)) {
            $response->getBody()->write(json_encode(['errors' => $validationErrors]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        // If basic validation passes, proceed with character creation
        $result = $this->characterService->createCharacter($data);

        if (isset($result['errors'])) {
            $response->getBody()->write(json_encode($result));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    public function updateCharacter(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $characterId = $args['id'];
        $data = $request->getParsedBody();

        // Perform basic input validation for update
        $validationErrors = $this->characterValidator->validateUpdateInput($data);

        if (!empty($validationErrors)) {
            $response->getBody()->write(json_encode(['errors' => $validationErrors]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

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
        if ($e->getCode() === 404) {
            return 404; // Not Found
        }

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
