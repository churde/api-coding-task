<?php namespace App\Models;

class Character {
    private $id;
    private $name;
    private $birthDate;
    private $kingdom;
    private $equipmentId;
    private $factionId;
    
    

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->birthDate = $data['birth_date'] ?? null;
        $this->kingdom = $data['kingdom'] ?? null;
        $this->equipmentId = $data['equipment_id'] ?? null;
        $this->factionId = $data['faction_id'] ?? null;
    }

    // Getter methods
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getBirthDate() { return $this->birthDate; }
    public function getKingdom() { return $this->kingdom; }
    public function getEquipmentId() { return $this->equipmentId; }
    public function getFactionId() { return $this->factionId; }

    // Setter methods
    public function setName($name) { $this->name = $name; }
    public function setBirthDate($birthDate) { $this->birthDate = $birthDate; }
    public function setKingdom($kingdom) { $this->kingdom = $kingdom; }
    public function setEquipmentId($equipmentId) { $this->equipmentId = $equipmentId; }
    public function setFactionId($factionId) { $this->factionId = $factionId; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'birth_date' => $this->birthDate,
            'kingdom' => $this->kingdom,
            'equipment_id' => $this->equipmentId,
            'faction_id' => $this->factionId,
        ];
    }
}
