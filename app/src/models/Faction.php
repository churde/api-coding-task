<?php

class Faction {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllFactions() {
        $stmt = $this->db->query("SELECT * FROM factions");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFactionById($id) {
        $stmt = $this->db->prepare("SELECT * FROM factions WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createFaction($data) {
        $stmt = $this->db->prepare("INSERT INTO factions (faction_name, description) VALUES (:faction_name, :description)");
        return $stmt->execute($data);
    }

    public function updateFaction($id, $data) {
        $data['id'] = $id;
        $stmt = $this->db->prepare("UPDATE factions SET faction_name = :faction_name, description = :description WHERE id = :id");
        return $stmt->execute($data);
    }

    public function deleteFaction($id) {
        $stmt = $this->db->prepare("DELETE FROM factions WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}