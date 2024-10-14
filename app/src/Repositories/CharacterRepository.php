<?php

namespace App\Repositories;

use App\Models\Character;
use App\Formatters\CharacterFormatter;
use App\Services\Cache;
use PDO;
use InvalidArgumentException;
use App\Models\Equipment;
use App\Models\Faction;

class CharacterRepository implements CharacterRepositoryInterface
{
    private $db;
    private $characterModel;
    private $cache;
    private $cacheConfig;

    public function __construct(PDO $db, Character $characterModel, Cache $cache, array $cacheConfig)
    {
        $this->db = $db;
        $this->characterModel = $characterModel;
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
    }

    public function getAllWithRelations(int $page, int $perPage): array
    {
        $cacheKey = "all_characters_page_{$page}_perPage_{$perPage}";
        $cacheUsed = false;

        if ($this->cacheConfig['enable_cache']['get_all_characters']) {
            $cachedCharacters = $this->cache->get($cacheKey);
            if ($cachedCharacters) {
                $cacheUsed = true;
                $cachedCharacters['meta']['cache_used'] = true;
                return $cachedCharacters;
            }
        }

        $offset = ($page - 1) * $perPage;
        $query = "SELECT c.*, 
                         e.id as equipment_id, e.name as equipment_name, e.type as equipment_type, e.made_by as equipment_made_by,
                         f.id as faction_id, f.faction_name, f.description as faction_description
                  FROM characters c
                  LEFT JOIN equipments e ON c.equipment_id = e.id
                  LEFT JOIN factions f ON c.faction_id = f.id
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formattedResults = array_map([CharacterFormatter::class, 'formatWithRelations'], $results);

        $countQuery = "SELECT COUNT(*) FROM characters";
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

        if ($this->cacheConfig['enable_cache']['get_all_characters']) {
            $this->cache->set($cacheKey, $result, $this->cacheConfig['cache_ttl']);
        }

        return $result;
    }

    public function getByIdWithRelations(int $id): ?array
    {
        $cacheKey = "character:{$id}";
        $cacheUsed = false;

        if ($this->cacheConfig['enable_cache']['get_character_by_id']) {
            $cachedCharacter = $this->cache->get($cacheKey);
            if ($cachedCharacter) {
                $cacheUsed = true;
                return ['data' => $cachedCharacter, 'meta' => ['cache_used' => true]];
            }
        }

        $query = "SELECT c.*, 
                         e.id as equipment_id, e.name as equipment_name, e.type as equipment_type, e.made_by as equipment_made_by,
                         f.id as faction_id, f.faction_name, f.description as faction_description
                  FROM characters c
                  LEFT JOIN equipments e ON c.equipment_id = e.id
                  LEFT JOIN factions f ON c.faction_id = f.id
                  WHERE c.id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $formattedResult = CharacterFormatter::formatWithRelations($result);
            if ($this->cacheConfig['enable_cache']['get_character_by_id']) {
                $this->cache->set($cacheKey, $formattedResult, $this->cacheConfig['cache_ttl']);
            }
            return ['data' => $formattedResult, 'meta' => ['cache_used' => $cacheUsed]];
        }

        return null;
    }

    public function create(array $data): array
    {
        $character = $this->characterModel::fromArray($data);
        $stmt = $this->db->prepare("INSERT INTO characters (name, birth_date, kingdom, equipment_id, faction_id) VALUES (:name, :birth_date, :kingdom, :equipment_id, :faction_id)");
        $stmt->execute(array_diff_key($character->toArray(), ['id' => null]));
        $id = $this->db->lastInsertId();
        
        return $this->getByIdWithRelations($id)['data'];
    }

    public function update(int $id, array $data): ?array
    {
        $allowedFields = ['name', 'birth_date', 'kingdom', 'equipment_id', 'faction_id'];
        $updateData = [];
        $updateFields = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
                $updateFields[] = "$field = :$field";
            }
        }

        if (empty($updateData)) {
            return $this->getByIdWithRelations($id);
        }

        $updateQuery = "UPDATE characters SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($updateQuery);
        
        $updateData['id'] = $id;
        $result = $stmt->execute($updateData);

        if ($result) {
            if ($this->cacheConfig['enable_cache']['get_character_by_id']) {
                $this->cache->delete("character:{$id}");
            }
            return $this->getByIdWithRelations($id)['data'];
        }

        return null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM characters WHERE id = :id");
        $stmt->execute(['id' => $id]);
        
        if ($stmt->rowCount() > 0) {
            if ($this->cacheConfig['enable_cache']['get_character_by_id']) {
                $this->cache->delete("character:{$id}");
            }
            return true;
        }
        
        return false;
    }

    public function equipmentExists(int $equipmentId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM equipments WHERE id = :id");
        $stmt->execute(['id' => $equipmentId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function factionExists(int $factionId): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM factions WHERE id = :id");
        $stmt->execute(['id' => $factionId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
