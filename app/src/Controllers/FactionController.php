<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\FactionService;
use App\Validators\FactionValidator;

class FactionController
{
    private $factionService;
    private $factionValidator;

    public function __construct(FactionService $factionService, FactionValidator $factionValidator)
    {
        $this->factionService = $factionService;
        $this->factionValidator = $factionValidator;
    }

    public function getAllFactions(Request $request, Response $response): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int)$queryParams['per_page'] : 10;
        $searchTerm = isset($queryParams['search']) ? $queryParams['search'] : null;

        try {
            $result = $this->factionService->getAllFactions($token, $page, $perPage, $searchTerm);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    public function getFactionById(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $factionId = $args['id'];

        try {
            $result = $this->factionService->getFactionById($token, $factionId);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    public function createFaction(Request $request, Response $response): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $data = $request->getParsedBody();

        $validationErrors = $this->factionValidator->validateCreateInput($data);

        if (!empty($validationErrors)) {
            $response->getBody()->write(json_encode(['errors' => $validationErrors]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $result = $this->factionService->createFaction($token, $data);
            $response->getBody()->write(json_encode($result));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    public function updateFaction(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $factionId = $args['id'];
        $data = $request->getParsedBody();

        $validationErrors = $this->factionValidator->validateUpdateInput($data);

        if (!empty($validationErrors)) {
            $response->getBody()->write(json_encode(['errors' => $validationErrors]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $updatedFaction = $this->factionService->updateFaction($token, $factionId, $data);
            $response->getBody()->write(json_encode($updatedFaction));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    public function deleteFaction(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $factionId = $args['id'];

        try {
            $this->factionService->deleteFaction($token, $factionId);
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
        if (getenv('APP_ENV') === 'production') {
            return 'An error occurred while processing your request.';
        }

        return $e->getMessage();
    }
}
