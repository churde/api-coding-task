<?php

namespace App\Services\Data;

use App\Interfaces\FactionRepositoryInterface;
use App\Interfaces\FactionServiceInterface;
use App\Services\Auth;
use App\Validators\FactionValidator;
use Monolog\Logger;

class FactionService implements FactionServiceInterface
{
    private Auth $auth;
    private FactionRepositoryInterface $factionRepository;
    private Logger $log;
    private FactionValidator $factionValidator;

    public function __construct(
        Auth $auth,
        FactionRepositoryInterface $factionRepository,
        Logger $log,
        FactionValidator $factionValidator
    ) {
        $this->auth = $auth;
        $this->factionRepository = $factionRepository;
        $this->log = $log;
        $this->factionValidator = $factionValidator;
    }

    public function getAllFactions(string $token, int $page = 1, int $perPage = 10, ?string $searchTerm = null): array
    {
        if (!$this->auth->hasPermission($token, 'read')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Fetching all factions, Page: ' . $page . ', PerPage: ' . $perPage . ', SearchTerm: ' . ($searchTerm ?? 'None'));

        if ($searchTerm) {
            return $this->factionRepository->searchFactions($searchTerm, $page, $perPage);
        }
        
        return $this->factionRepository->getAll($page, $perPage);
    }

    public function getFactionById(string $token, int $factionId): array
    {
        if (!$this->auth->hasPermission($token, 'read')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Fetching faction with ID: ' . $factionId);
        $faction = $this->factionRepository->getById($factionId);

        if (!$faction) {
            throw new \Exception('Faction not found', 404);
        }

        return $faction;
    }

    public function createFaction(string $token, array $data): array
    {
        $this->log->info('Attempting to create faction with token: ' . substr($token, 0, 10) . '...');
        
        if (!$this->auth->hasPermission($token, 'create')) {
            $this->log->warning('Permission denied: User lacks "create" permission for faction creation');
            throw new \Exception('Forbidden', 403);
        }
        
        $this->log->info('Permission check passed. Proceeding with faction creation.');
        
        $validationErrors = $this->factionValidator->validateCreateInput($data);

        if (!empty($validationErrors)) {
            return ['errors' => $validationErrors];
        }

        $faction = $this->factionRepository->create($data);

        if (!$faction) {
            return ['errors' => ['Failed to create faction']];
        }

        return $faction;
    }

    public function updateFaction(string $token, int $factionId, array $data): array
    {
        if (!$this->auth->hasPermission($token, 'update')) {
            throw new \Exception('Forbidden', 403);
        }

        $validationErrors = $this->factionValidator->validateUpdateInput($data);

        if (!empty($validationErrors)) {
            throw new \Exception('Invalid input: ' . implode(', ', $validationErrors), 400);
        }

        $this->log->info('Updating faction with ID: ' . $factionId);
        $updatedFaction = $this->factionRepository->update($factionId, $data);

        if (!$updatedFaction) {
            throw new \Exception('Faction not found', 404);
        }

        $this->log->info('Updated faction with ID: ' . $factionId);

        return $updatedFaction;
    }

    public function deleteFaction(string $token, int $factionId): void
    {
        if (!$this->auth->hasPermission($token, 'delete')) {
            throw new \Exception('Forbidden', 403);
        }

        $this->log->info('Deleting faction with ID: ' . $factionId);
        $result = $this->factionRepository->delete($factionId);

        if (!$result) {
            $this->log->warning('Faction not found for deletion, ID: ' . $factionId);
            throw new \Exception('Faction not found', 404);
        }

        $this->log->info('Deleted faction with ID: ' . $factionId);
    }
}
