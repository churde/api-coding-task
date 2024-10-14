<?php

namespace App\Repositories;

use App\Models\Faction;
use App\Formatters\FactionFormatter;
use App\Interfaces\FactionRepositoryInterface;
use App\Services\Cache;
use PDO;

class FactionRepository implements FactionRepositoryInterface
{
    private $db;
    private $factionModel;
    private $cache;
    private $cacheConfig;

    public function __construct(PDO $db, Faction $factionModel, Cache $cache, array $cacheConfig)
    {
        $this->db = $db;
        $this->factionModel = $factionModel;
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
    }

    public function getAll(int $page, int $perPage): array
    {
        $cacheKey = "all_factions_page_{$page}_perPage_{$perPage}";
        $cacheUsed = false;

        if ($this->cacheConfig['enable_cache']['get_all_factions']) {
            $cachedFactions = $this->cache->get($cacheKey);
            if ($cachedFactions) {
                $cachedFactions['meta']['cache_used'] = true;
                return $cachedFactions;
            }
        }

        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM factions LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedResults = array_map([FactionFormatter::class, 'format'], $results);

        $countQuery = "SELECT COUNT(*) FROM factions";
        $countStmt = $this->db->query($countQuery);
        $totalCount = $countStmt->fetchColumn();

        $result = [
            'data' => $formattedResults,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_count' => $totalCount,
                'total_pages' => ceil($totalCount / $perPage),
                'cache_used' => $cacheUsed
            ]
        ];

        if ($this->cacheConfig['enable_cache']['get_all_factions']) {
            $this->cache->set($cacheKey, $result, $this->cacheConfig['ttl']);
        }

        return $result;
    }

    public function getById(int $id): ?array
    {
        $cacheKey = "faction:{$id}";
        $cacheUsed = false;

        if ($this->cacheConfig['enable_cache']['get_faction_by_id']) {
            $cachedFaction = $this->cache->get($cacheKey);
            if ($cachedFaction) {
                $cacheUsed = true;
                return ['data' => $cachedFaction, 'meta' => ['cache_used' => true]];
            }
        }

        $query = "SELECT * FROM factions WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $formattedResult = FactionFormatter::format($result);
            if ($this->cacheConfig['enable_cache']['get_faction_by_id']) {
                $this->cache->set($cacheKey, $formattedResult, $this->cacheConfig['ttl']);
            }
            return ['data' => $formattedResult, 'meta' => ['cache_used' => $cacheUsed]];
        }

        return null;
    }

    public function create(array $data): array
    {
        $faction = new $this->factionModel($data);
        $stmt = $this->db->prepare("INSERT INTO factions (faction_name, description) VALUES (:faction_name, :description)");
        $stmt->execute([
            'faction_name' => $faction->getFactionName(),
            'description' => $faction->getDescription()
        ]);
        $id = $this->db->lastInsertId();
        
        return $this->getById($id)['data'];
    }

    public function update(int $id, array $data): ?array
    {
        $allowedFields = ['faction_name', 'description'];
        $updateData = [];
        $updateFields = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
                $updateFields[] = "$field = :$field";
            }
        }

        if (empty($updateData)) {
            return $this->getById($id);
        }

        $updateQuery = "UPDATE factions SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($updateQuery);
        
        $updateData['id'] = $id;
        $result = $stmt->execute($updateData);

        if ($result) {
            if ($this->cacheConfig['enable_cache']['get_faction_by_id']) {
                $this->cache->delete("faction:{$id}");
            }
            return $this->getById($id)['data'];
        }

        return null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM factions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->rowCount() > 0) {
            if ($this->cacheConfig['enable_cache']['get_faction_by_id']) {
                $this->cache->delete("faction:{$id}");
            }
            return true;
        }
        
        return false;
    }

    public function searchFactions(string $searchTerm, int $page = 1, int $perPage = 10): array
    {
        $cacheKey = "faction_search:{$searchTerm}:page:{$page}:perPage:{$perPage}";
        $cacheUsed = false;

        if ($this->cacheConfig['enable_cache']['get_all_factions']) {
            $cachedResults = $this->cache->get($cacheKey);
            if ($cachedResults) {
                $cachedResults['meta']['cache_used'] = true;
                return $cachedResults;
            }
        }

        $offset = ($page - 1) * $perPage;

        $query = "SELECT * FROM factions WHERE faction_name LIKE :searchTerm OR description LIKE :searchTerm LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':searchTerm', "%$searchTerm%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedResults = array_map([FactionFormatter::class, 'format'], $results);

        $countQuery = "SELECT COUNT(*) FROM factions WHERE faction_name LIKE :searchTerm OR description LIKE :searchTerm";
        $countStmt = $this->db->prepare($countQuery);
        $countStmt->bindValue(':searchTerm', "%$searchTerm%", PDO::PARAM_STR);
        $countStmt->execute();
        $totalCount = $countStmt->fetchColumn();

        $result = [
            'data' => $formattedResults,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_count' => $totalCount,
                'total_pages' => ceil($totalCount / $perPage),
                'cache_used' => $cacheUsed
            ]
        ];

        if ($this->cacheConfig['enable_cache']['get_all_factions']) {
            $this->cache->set($cacheKey, $result, $this->cacheConfig['ttl']);
        }

        return $result;
    }

    public function isFactionNameUnique(string $factionName, ?int $excludeId = null): bool
    {
        $query = "SELECT COUNT(*) FROM factions WHERE faction_name = :faction_name";
        $params = ['faction_name' => $factionName];

        if ($excludeId !== null) {
            $query .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();

        return $count === 0;
    }
}
