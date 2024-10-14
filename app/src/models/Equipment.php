<?php namespace App\Models;

class Equipment {
    private ?int $id;
    private ?string $name;
    private ?string $type;
    private ?string $made_by;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->made_by = $data['made_by'] ?? null;
    }

    // Getter methods
    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function getType(): ?string { return $this->type; }
    public function getMadeBy(): ?string { return $this->made_by; }

    // Setter methods
    public function setName(?string $name): void { $this->name = $name; }
    public function setType(?string $type): void { $this->type = $type; }
    public function setMadeBy(?string $made_by): void { $this->made_by = $made_by; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'made_by' => $this->made_by,
        ];
    }
}
