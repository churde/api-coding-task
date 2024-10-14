<?php

return [
    'cache' => [
        'ttl' => 3600, // Cache TTL in seconds (1 hour)
        'enable_cache' => [
            'get_all_characters' => false,
            'get_character_by_id' => false,
            'get_all_equipment' => true,
            'get_equipment_by_id' => true,
            'get_all_factions' => false,
            'get_faction_by_id' => false,
            // Add more route-specific cache flags as needed
        ],
    ],
    'rate_limit' => [
        'requests' => 60,
        'per_minutes' => 1,
    ],
    'pagination' => [
        'per_page' => 20,
    ],
    'character' => [
        'max_name_length' => 50,
        'min_level' => 1,
        'max_level' => 100,
    ],
    'database' => [
        'connection_timeout' => 5, // in seconds
        'query_timeout' => 30, // in seconds
    ],
    'api' => [
        'version' => 'v1',
        'default_format' => 'json',
    ],
    'logging' => [
        'level' => 'info',
        'file' => 'app/logs/app.log',
    ],
];
