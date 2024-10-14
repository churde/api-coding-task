<?php namespace App\Models;

class Faction {
    private $id;
    private $faction_name;
    private $description;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->faction_name = $data['faction_name'] ?? null;
        $this->description = $data['description'] ?? null;
    }

    // Getter methods
    public function getId() { return $this->id; }
    public function getFactionName() { return $this->faction_name; }
    public function getDescription() { return $this->description; }

    // Setter methods
    public function setFactionName($faction_name) { $this->faction_name = $faction_name; }
    public function setDescription($description) { $this->description = $description; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'faction_name' => $this->faction_name,
            'description' => $this->description,
        ];
    }
}
