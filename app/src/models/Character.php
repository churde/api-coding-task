<?php namespace App\Models;

class Character {
    private $id;
    private $name;
    private $birthDate;
    private $kingdom;
    private $equipmentId;
    private $factionId;

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

    public static function fromArray(array $data): self
    {
        $character = new self();
        $character->id = $data['id'] ?? null;
        $character->name = $data['name'];
        $character->birthDate = $data['birth_date'];
        $character->kingdom = $data['kingdom'];
        $character->equipmentId = $data['equipment_id'] ?? null;
        $character->factionId = $data['faction_id'] ?? null;
        return $character;
    }
}
