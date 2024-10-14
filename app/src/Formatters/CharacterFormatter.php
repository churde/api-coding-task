<?php

namespace App\Formatters;

class CharacterFormatter
{
    public static function formatWithRelations(array $data): array
    {
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'birth_date' => $data['birth_date'],
            'kingdom' => $data['kingdom'],
            'equipment' => [
                'id' => $data['equipment_id'],
                'name' => $data['equipment_name'],
                'type' => $data['equipment_type'],
                'made_by' => $data['equipment_made_by']
            ],
            'faction' => [
                'id' => $data['faction_id'],
                'name' => $data['faction_name'],
                'description' => $data['faction_description']
            ]
        ];
    }
}
