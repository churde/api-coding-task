<?php

namespace App\Formatters;

use App\Models\Faction;

class FactionFormatter
{
    public static function format(array $data): array
    {
        $faction = new Faction($data);
        
        return [
            'id' => $faction->getId(),
            'faction_name' => $faction->getFactionName(),
            'description' => $faction->getDescription()
        ];
    }
}
