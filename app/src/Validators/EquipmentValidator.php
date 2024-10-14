<?php

namespace App\Validators;

use App\Interfaces\EquipmentRepositoryInterface;

class EquipmentValidator
{
    private EquipmentRepositoryInterface $equipmentRepository;

    public function __construct(EquipmentRepositoryInterface $equipmentRepository)
    {
        $this->equipmentRepository = $equipmentRepository;
    }

    public function validateCreateInput(array $data): array
    {
        $errors = [];

        $requiredFields = ['name', 'type', 'made_by'];
        $stringFields = ['name', 'type', 'made_by'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "The {$field} field is required.";
            }
        }

        foreach ($stringFields as $field) {
            if (isset($data[$field]) && !is_string($data[$field])) {
                $errors[] = "The {$field} must be a string.";
            }
        }

        $allowedFields = $requiredFields;
        $extraFields = array_diff(array_keys($data), $allowedFields);
        if (!empty($extraFields)) {
            $errors[] = "Unexpected fields: " . implode(', ', $extraFields);
        }

        return $errors;
    }

    public function validateUpdateInput(array $data): array
    {
        $errors = [];

        $allowedFields = ['name', 'type', 'made_by'];
        $stringFields = ['name', 'type', 'made_by'];

        if (empty($data)) {
            $errors[] = "At least one field must be provided for update.";
        }

        foreach ($stringFields as $field) {
            if (isset($data[$field]) && !is_string($data[$field])) {
                $errors[] = "The {$field} must be a string.";
            }
        }

        $extraFields = array_diff(array_keys($data), $allowedFields);
        if (!empty($extraFields)) {
            $errors[] = "Unexpected fields: " . implode(', ', $extraFields);
        }

        return $errors;
    }

    // Note: We don't need a validateForeignKeys method for Equipment as it doesn't have foreign keys in this context.
    // If you add foreign keys to Equipment in the future, you can add a similar method here.

    // You might want to add additional validation methods specific to Equipment if needed.
    // For example, validating the 'type' field against a list of allowed types:

    private function isValidType(string $type): bool
    {
        $allowedTypes = ['weapon', 'armor', 'accessory']; // Add or modify as needed
        return in_array(strtolower($type), $allowedTypes);
    }

    // You can then use this method in validateCreateInput and validateUpdateInput if needed:
    // if (isset($data['type']) && !$this->isValidType($data['type'])) {
    //     $errors[] = "The type must be one of: " . implode(', ', $allowedTypes);
    // }
}
