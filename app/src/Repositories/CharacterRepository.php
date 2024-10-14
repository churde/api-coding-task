<?php

namespace App\Repositories;

use App\Models\Character;
use PDO;
use InvalidArgumentException;
use App\Models\Equipment;
use App\Models\Faction;

class CharacterRepository implements CharacterRepositoryInterface
{
    private $db;
    private $characterModel;

    public function __construct(PDO $db, Character $characterModel)
    {
        $this->db = $db;
        $this->characterModel = $characterModel;
    }

    public function getAllWithRelations(int $page, int $perPage): array
    {
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

        $formattedResults = array_map([$this, 'formatCharacterWithRelations'], $results);

        $countQuery = "SELECT COUNT(*) FROM characters";
        $countStmt = $this->db->query($countQuery);
        $totalCount = $countStmt->fetchColumn();

        return [
            'data' => $formattedResults,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_count' => $totalCount,
                'total_pages' => ceil($totalCount / $perPage)
            ]
        ];
    }

    public function getByIdWithRelations(int $id): ?array
    {
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

        return $result ? $this->formatCharacterWithRelations($result) : null;
    }

    public function create(array $data): array
    {

        $character = $this->characterModel::fromArray($data);
        $stmt = $this->db->prepare("INSERT INTO characters (name, birth_date, kingdom, equipment_id, faction_id) VALUES (:name, :birth_date, :kingdom, :equipment_id, :faction_id)");
        $stmt->execute(array_diff_key($character->toArray(), ['id' => null]));
        $id = $this->db->lastInsertId();
        return $this->getByIdWithRelations($id);
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

        return $result ? $this->getByIdWithRelations($id) : null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM characters WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function formatCharacterWithRelations($data): array
    {
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'birth_date' => $data['birth_date'],
            'kingdom' => $data['kingdom'],
            'equipment' => [
                'id' => $data['equipment_id'],
                'name' => $data['equipment_name'],
                'type' => $data['equipment_type'],
                'made_by' => $data['equipment_made_by']
            ],
            'faction' => [
                'id' => $data['faction_id'],
                'name' => $data['faction_name'],
                'description' => $data['faction_description']
            ]
        ];
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
