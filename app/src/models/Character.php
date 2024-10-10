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
        return $stmt->execute($data);
    }

    public function updateCharacter($id, $data) {
        $data['id'] = $id;
        $stmt = $this->db->prepare("UPDATE characters SET name = :name, birth_date = :birth_date, kingdom = :kingdom, equipment_id = :equipment_id, faction_id = :faction_id WHERE id = :id");
        return $stmt->execute($data);
    }

    public function deleteCharacter($id) {
        $stmt = $this->db->prepare("DELETE FROM characters WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}