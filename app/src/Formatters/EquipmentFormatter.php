<?php

namespace App\Formatters;

use App\Models\Equipment;

class EquipmentFormatter
{
    public static function format(array $data): array
    {
        $equipment = new Equipment($data);
        
        return [
            'id' => $equipment->getId(),
            'name' => $equipment->getName(),
            'type' => $equipment->getType(),
            'made_by' => $equipment->getMadeBy()
        ];
    }
}
