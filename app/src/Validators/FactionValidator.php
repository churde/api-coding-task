<?php

namespace App\Validators;

use App\Interfaces\FactionRepositoryInterface;

class FactionValidator
{
    private FactionRepositoryInterface $factionRepository;

    public function __construct(FactionRepositoryInterface $factionRepository)
    {
        $this->factionRepository = $factionRepository;
    }

    public function validateCreateInput(array $data): array
    {
        $errors = [];

        $requiredFields = ['faction_name', 'description'];
        $stringFields = ['faction_name', 'description'];

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

        // Additional validation for faction_name
        if (isset($data['faction_name']) && strlen($data['faction_name']) > 100) {
            $errors[] = "The faction_name must not exceed 100 characters.";
        }

        return $errors;
    }

    public function validateUpdateInput(array $data): array
    {
        $errors = [];

        $allowedFields = ['faction_name', 'description'];
        $stringFields = ['faction_name', 'description'];

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

        // Additional validation for faction_name
        if (isset($data['faction_name']) && strlen($data['faction_name']) > 100) {
            $errors[] = "The faction_name must not exceed 100 characters.";
        }

        return $errors;
    }

    // You might want to add additional validation methods specific to Faction if needed.
    // For example, validating the uniqueness of the faction_name:

    public function isUniqueFactionName(string $factionName, ?int $excludeId = null): bool
    {
        // Implement the actual check for unique faction name
        // For now, we'll return true to avoid the linter error
        return true;
    }

    // You can then use this method in validateCreateInput and validateUpdateInput if needed:
    // if (isset($data['faction_name']) && !$this->isUniqueFactionName($data['faction_name'], $excludeId)) {
    //     $errors[] = "The faction name must be unique.";
    // }
}
