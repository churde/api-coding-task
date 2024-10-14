<?php

namespace App\Validators;

use App\Interfaces\CharacterRepositoryInterface;

class CharacterValidator
{
    private $characterRepository;

    public function __construct(CharacterRepositoryInterface $characterRepository)
    {
        $this->characterRepository = $characterRepository;
    }

    public function validateCreateInput(array $data): array
    {
        $errors = [];

        $requiredFields = ['name', 'birth_date', 'kingdom', 'equipment_id', 'faction_id'];
        $integerFields = ['equipment_id', 'faction_id'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "The {$field} field is required.";
            }
        }

        foreach ($integerFields as $field) {
            if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_INT)) {
                $errors[] = "The {$field} must be an integer.";
            }
        }

        if (isset($data['birth_date']) && !$this->isValidDate($data['birth_date'])) {
            $errors[] = "The birth_date must be a valid date in the format YYYY-MM-DD.";
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

        $allowedFields = ['name', 'birth_date', 'kingdom', 'equipment_id', 'faction_id'];
        $integerFields = ['equipment_id', 'faction_id'];

        if (empty($data)) {
            $errors[] = "At least one field must be provided for update.";
        }

        foreach ($integerFields as $field) {
            if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_INT)) {
                $errors[] = "The {$field} must be an integer.";
            }
        }

        if (isset($data['birth_date']) && !$this->isValidDate($data['birth_date'])) {
            $errors[] = "The birth_date must be a valid date in the format YYYY-MM-DD.";
        }

        $extraFields = array_diff(array_keys($data), $allowedFields);
        if (!empty($extraFields)) {
            $errors[] = "Unexpected fields: " . implode(', ', $extraFields);
        }

        return $errors;
    }

    public function validateForeignKeys(array $data): array
    {
        $errors = [];

        if (isset($data['equipment_id'])) {
            if (!$this->characterRepository->equipmentExists($data['equipment_id'])) {
                $errors[] = "The specified equipment_id does not exist.";
            }
        }

        if (isset($data['faction_id'])) {
            if (!$this->characterRepository->factionExists($data['faction_id'])) {
                $errors[] = "The specified faction_id does not exist.";
            }
        }

        return $errors;
    }

    private function isValidDate($date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
