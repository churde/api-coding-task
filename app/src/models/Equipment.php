<?php

class Equipment {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllEquipments() {
        $stmt = $this->db->query("SELECT * FROM equipments");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEquipmentById($id) {
        $stmt = $this->db->prepare("SELECT * FROM equipments WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createEquipment($data) {
        $stmt = $this->db->prepare("INSERT INTO equipments (name, type, made_by) VALUES (:name, :type, :made_by)");
        return $stmt->execute($data);
    }

    public function updateEquipment($id, $data) {
        $data['id'] = $id;
        $stmt = $this->db->prepare("UPDATE equipments SET name = :name, type = :type, made_by = :made_by WHERE id = :id");
        return $stmt->execute($data);
    }

    public function deleteEquipment($id) {
        $stmt = $this->db->prepare("DELETE FROM equipments WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}