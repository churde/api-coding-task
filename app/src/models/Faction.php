<?php namespace App\Models;

class Faction {
    private ?int $id;
    private ?string $faction_name;
    private ?string $description;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->faction_name = $data['faction_name'] ?? null;
        $this->description = $data['description'] ?? null;
    }

    // Getter methods
    public function getId(): ?int { return $this->id; }
    public function getFactionName(): ?string { return $this->faction_name; }
    public function getDescription(): ?string { return $this->description; }

    // Setter methods
    public function setFactionName(?string $faction_name): void { $this->faction_name = $faction_name; }
    public function setDescription(?string $description): void { $this->description = $description; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'faction_name' => $this->faction_name,
            'description' => $this->description,
        ];
    }
}
