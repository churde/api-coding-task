<?php

namespace App\Services\Data;

use App\Interfaces\EquipmentRepositoryInterface;
use App\Interfaces\EquipmentServiceInterface;
use App\Services\Auth;
use App\Validators\EquipmentValidator;
use Monolog\Logger;

class EquipmentService implements EquipmentServiceInterface
{
    private $auth;
    private $equipmentRepository;
    private $log;
    private $equipmentValidator;

    public function __construct(
        Auth $auth,
        EquipmentRepositoryInterface $equipmentRepository,
        Logger $log,
        EquipmentValidator $equipmentValidator
    ) {
        $this->auth = $auth;
        $this->equipmentRepository = $equipmentRepository;
        $this->log = $log;
        $this->equipmentValidator = $equipmentValidator;
    }

    public function getAllEquipment(string $token, int $page = 1, int $perPage = 10, ?string $searchTerm = null): array
    {
        if (!$this->auth->hasPermission($token, 'read')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Fetching all equipment, Page: ' . $page . ', PerPage: ' . $perPage . ', SearchTerm: ' . ($searchTerm ?? 'None'));

        if ($searchTerm) {
            return $this->equipmentRepository->searchEquipment($searchTerm, $page, $perPage);
        }
        
        return $this->equipmentRepository->getAll($page, $perPage);
    }

    public function getEquipmentById(string $token, int $equipmentId): array
    {
        if (!$this->auth->hasPermission($token, 'read')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Fetching equipment with relations, ID: ' . $equipmentId);
        $equipment = $this->equipmentRepository->getById($equipmentId);

        if (!$equipment) {
            throw new \Exception('Equipment not found', 404);
        }

        return $equipment;
    }

    public function createEquipment(string $token, array $data): array
    {
        $this->log->info('Attempting to create equipment with token: ' . substr($token, 0, 10) . '...');
        
        if (!$this->auth->hasPermission($token, 'create')) {
            $this->log->warning('Permission denied: User lacks "create" permission for equipment creation');
            throw new \Exception('Forbidden', 403);
        }
        
        $this->log->info('Permission check passed. Proceeding with equipment creation.');
        
        // Validate input data
        $validationErrors = $this->equipmentValidator->validateCreateInput($data);

        if (!empty($validationErrors)) {
            return ['errors' => $validationErrors];
        }

        // If validation passes, proceed with equipment creation
        $equipment = $this->equipmentRepository->create($data);

        if (!$equipment) {
            return ['errors' => ['Failed to create equipment']];
        }

        return $equipment;
    }

    public function updateEquipment(string $token, int $equipmentId, array $data): array
    {
        if (!$this->auth->hasPermission($token, 'update')) {
            throw new \Exception('Forbidden', 403);
        }

        // Validate input data
        $validationErrors = $this->equipmentValidator->validateUpdateInput($data);

        if (!empty($validationErrors)) {
            throw new \Exception('Invalid input: ' . implode(', ', $validationErrors), 400);
        }

        $this->log->info('Updating equipment with ID: ' . $equipmentId);
        $updatedEquipment = $this->equipmentRepository->update($equipmentId, $data);

        if (!$updatedEquipment) {
            throw new \Exception('Equipment not found', 404);
        }

        $this->log->info('Updated equipment with ID: ' . $equipmentId);

        return $updatedEquipment;
    }

    public function deleteEquipment(string $token, int $equipmentId): void
    {
        if (!$this->auth->hasPermission($token, 'delete')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Deleting equipment with ID: ' . $equipmentId);
        $result = $this->equipmentRepository->delete($equipmentId);

        if (!$result) {
            $this->log->warning('Equipment not found for deletion, ID: ' . $equipmentId);
            throw new \Exception('Equipment not found', 404);
        }

        $this->log->info('Deleted equipment with ID: ' . $equipmentId);
    }
}
