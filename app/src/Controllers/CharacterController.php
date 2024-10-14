<?php

namespace App\Controllers;

use App\Services\Data\CharacterService;
use App\Validators\CharacterValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CharacterController
{
    private CharacterService $characterService;
    private CharacterValidator $characterValidator;

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
        $searchTerm = isset($queryParams['search']) ? $queryParams['search'] : null;

        try {
            $result = $this->characterService->getAllCharacters($token, $page, $perPage, $searchTerm);
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

        // Perform basic input validation for create
        $validationErrors = $this->characterValidator->validateCreateInput($data);

        if (!empty($validationErrors)) {
            $response->getBody()->write(json_encode(['errors' => $validationErrors]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $result = $this->characterService->createCharacter($token, $data);
            $response->getBody()->write(json_encode($result));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
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
        $code = $e->getCode();

        switch ($code) {
            case 400:
                return 400; // Bad Request
            case 401:
                return 401; // Unauthorized
            case 403:
                return 403; // Forbidden
            case 404:
                return 404; // Not Found
            case 409:
                return 409; // Conflict
            case 422:
                return 422; // Unprocessable Entity
            default:
                return 500; // Internal Server Error
        }
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
