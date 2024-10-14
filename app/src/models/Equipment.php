<?php namespace App\Models;

class Equipment {
    private $id;
    private $name;
    private $type;
    private $made_by;

    public function __construct(array $data = []) {
        $this->id = $data['id'] ?? null;
        $this->name = $data['name'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->made_by = $data['made_by'] ?? null;
    }

    // Getter methods
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getType() { return $this->type; }
    public function getMadeBy() { return $this->made_by; }

    // Setter methods
    public function setName($name) { $this->name = $name; }
    public function setType($type) { $this->type = $type; }
    public function setMadeBy($made_by) { $this->made_by = $made_by; }

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
