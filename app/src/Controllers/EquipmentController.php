<?php

namespace App\Controllers;

use App\Services\Data\EquipmentService;
use App\Validators\EquipmentValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EquipmentController
{
    private $equipmentService;
    private $equipmentValidator;

    public function __construct(EquipmentService $equipmentService, EquipmentValidator $equipmentValidator)
    {
        $this->equipmentService = $equipmentService;
        $this->equipmentValidator = $equipmentValidator;
    }

    public function getAllEquipment(Request $request, Response $response): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $queryParams = $request->getQueryParams();
        $page = isset($queryParams['page']) ? (int)$queryParams['page'] : 1;
        $perPage = isset($queryParams['per_page']) ? (int)$queryParams['per_page'] : 10;
        $searchTerm = isset($queryParams['search']) ? $queryParams['search'] : null;

        try {
            $result = $this->equipmentService->getAllEquipment($token, $page, $perPage, $searchTerm);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    public function getEquipmentById(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $equipmentId = $args['id'];

        try {
            $result = $this->equipmentService->getEquipmentById($token, $equipmentId);
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    public function createEquipment(Request $request, Response $response): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $data = $request->getParsedBody();

        // Perform basic input validation for create
        $validationErrors = $this->equipmentValidator->validateCreateInput($data);

        if (!empty($validationErrors)) {
            $response->getBody()->write(json_encode(['errors' => $validationErrors]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $result = $this->equipmentService->createEquipment($token, $data);
            $response->getBody()->write(json_encode($result));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    public function updateEquipment(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $equipmentId = $args['id'];
        $data = $request->getParsedBody();

        // Perform basic input validation for update
        $validationErrors = $this->equipmentValidator->validateUpdateInput($data);

        if (!empty($validationErrors)) {
            $response->getBody()->write(json_encode(['errors' => $validationErrors]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $updatedEquipment = $this->equipmentService->updateEquipment($token, $equipmentId, $data);
            $response->getBody()->write(json_encode($updatedEquipment));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return $this->handleException($response, $e);
        }
    }

    public function deleteEquipment(Request $request, Response $response, array $args): Response
    {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        $equipmentId = $args['id'];

        try {
            $this->equipmentService->deleteEquipment($token, $equipmentId);
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
