<?php namespace App\Models;

class Character {
    private ?int $id;
    private ?string $name;
    private ?string $birthDate;
    private ?string $kingdom;
    private ?int $equipmentId;
    private ?int $factionId;
    
    

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->birthDate = $data['birth_date'] ?? null;
        $this->kingdom = $data['kingdom'] ?? null;
        $this->equipmentId = $data['equipment_id'] ?? null;
        $this->factionId = $data['faction_id'] ?? null;
    }

    // Getter methods
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function getBirthDate(): ?string { return $this->birthDate; }
    public function getKingdom(): ?string { return $this->kingdom; }
    public function getEquipmentId(): ?int { return $this->equipmentId; }
    public function getFactionId(): ?int { return $this->factionId; }

    // Setter methods
    public function setName(?string $name): void { $this->name = $name; }
    public function setBirthDate(?string $birthDate): void { $this->birthDate = $birthDate; }
    public function setKingdom(?string $kingdom): void { $this->kingdom = $kingdom; }
    public function setEquipmentId(?int $equipmentId): void { $this->equipmentId = $equipmentId; }
    public function setFactionId(?int $factionId): void { $this->factionId = $factionId; }

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
