<?php

namespace App\Repositories;

use App\Models\Equipment;
use App\Formatters\EquipmentFormatter;
use App\Interfaces\EquipmentRepositoryInterface;
use App\Services\Cache;
use PDO;

class EquipmentRepository implements EquipmentRepositoryInterface
{
    private PDO $db;
    private Equipment $equipmentModel;
    private Cache $cache;
    private array $cacheConfig;

    public function __construct(PDO $db, Equipment $equipmentModel, Cache $cache, array $cacheConfig)
    {
        $this->db = $db;
        $this->equipmentModel = $equipmentModel;
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
    }

    public function getAll(int $page, int $perPage): array
    {
        $cacheKey = "all_equipment_page_{$page}_perPage_{$perPage}";
        $cacheUsed = false;

        if ($this->cacheConfig['enable_cache']['get_all_equipment']) {
            $cachedEquipment = $this->cache->get($cacheKey);
            if ($cachedEquipment) {
                $cachedEquipment['meta']['cache_used'] = true;
                return $cachedEquipment;
            }
        }

        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM equipments LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedResults = array_map([EquipmentFormatter::class, 'format'], $results);

        $countQuery = "SELECT COUNT(*) FROM equipments";
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

        if ($this->cacheConfig['enable_cache']['get_all_equipment']) {
            $this->cache->set($cacheKey, $result, $this->cacheConfig['ttl']);
        }

        return $result;
    }

    public function getById(int $id): ?array
    {
        $cacheKey = "equipment:{$id}";
        $cacheUsed = false;

        if ($this->cacheConfig['enable_cache']['get_equipment_by_id']) {
            $cachedEquipment = $this->cache->get($cacheKey);
            if ($cachedEquipment) {
                $cacheUsed = true;
                return ['data' => $cachedEquipment, 'meta' => ['cache_used' => true]];
            }
        }

        $query = "SELECT * FROM equipments WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $formattedResult = EquipmentFormatter::format($result);
            if ($this->cacheConfig['enable_cache']['get_equipment_by_id']) {
                $this->cache->set($cacheKey, $formattedResult, $this->cacheConfig['ttl']);
            }
            return ['data' => $formattedResult, 'meta' => ['cache_used' => $cacheUsed]];
        }

        return null;
    }

    public function create(array $data): array
    {
        $equipment = new $this->equipmentModel($data);
        $stmt = $this->db->prepare("INSERT INTO equipments (name, type, made_by) VALUES (:name, :type, :made_by)");
        $stmt->execute([
            'name' => $equipment->getName(),
            'type' => $equipment->getType(),
            'made_by' => $equipment->getMadeBy()
        ]);
        $id = $this->db->lastInsertId();
        
        return $this->getById($id)['data'];
    }

    public function update(int $id, array $data): ?array
    {
        $allowedFields = ['name', 'type', 'made_by'];
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

        $updateQuery = "UPDATE equipments SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($updateQuery);
        
        $updateData['id'] = $id;
        $result = $stmt->execute($updateData);

        if ($result) {
            if ($this->cacheConfig['enable_cache']['get_equipment_by_id']) {
                $this->cache->delete("equipment:{$id}");
            }
            return $this->getById($id)['data'];
        }

        return null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM equipments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->rowCount() > 0) {
            if ($this->cacheConfig['enable_cache']['get_equipment_by_id']) {
                $this->cache->delete("equipment:{$id}");
            }
            return true;
        }
        
        return false;
    }

    public function searchEquipment(string $searchTerm, int $page = 1, int $perPage = 10): array
    {
        $cacheKey = "equipment_search:{$searchTerm}:page:{$page}:perPage:{$perPage}";
        $cacheUsed = false;

        if ($this->cacheConfig['enable_cache']['get_all_equipment']) {
            $cachedResults = $this->cache->get($cacheKey);
            if ($cachedResults) {
                $cachedResults['meta']['cache_used'] = true;
                return $cachedResults;
            }
        }

        $offset = ($page - 1) * $perPage;

        $query = "SELECT * FROM equipments WHERE name LIKE :searchTerm OR type LIKE :searchTerm OR made_by LIKE :searchTerm LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':searchTerm', "%$searchTerm%", PDO::PARAM_STR);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedResults = array_map([EquipmentFormatter::class, 'format'], $results);

        $countQuery = "SELECT COUNT(*) FROM equipments WHERE name LIKE :searchTerm OR type LIKE :searchTerm OR made_by LIKE :searchTerm";
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

        if ($this->cacheConfig['enable_cache']['get_all_equipment']) {
            $this->cache->set($cacheKey, $result, $this->cacheConfig['ttl']);
        }

        return $result;
    }
}
