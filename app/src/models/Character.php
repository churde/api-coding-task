<?php namespace App\Models;

use PDO;

class Character {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllCharacters() {
        $stmt = $this->db->query("SELECT * FROM characters");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCharacterById($id) {
        $stmt = $this->db->prepare("SELECT * FROM characters WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createCharacter($data) {
        unset($data['id']);
        $stmt = $this->db->prepare("INSERT INTO characters (name, birth_date, kingdom, equipment_id, faction_id) VALUES (:name, :birth_date, :kingdom, :equipment_id, :faction_id)");
        $stmt->execute($data);
        return $this->getCharacterById($this->db->lastInsertId());
    }

    public function updateCharacter($id, $data) {
        $data['id'] = $id;
        $stmt = $this->db->prepare("UPDATE characters SET name = :name, birth_date = :birth_date, kingdom = :kingdom, equipment_id = :equipment_id, faction_id = :faction_id WHERE id = :id");
        $stmt->execute($data);
        return $this->getCharacterById($id);
    }

    public function deleteCharacter($id) {
        $stmt = $this->db->prepare("DELETE FROM characters WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getCharacterWithRelations($id) {
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
            return $this->formatCharacterWithRelations($result);
        }

        return null;
    }

    public function getAllCharactersWithRelations($page = 1, $perPage = 10) {
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

        // Get total count for pagination metadata
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

    private function formatCharacterWithRelations($data) {
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
}