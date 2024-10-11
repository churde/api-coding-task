<?php

return [
    'enable_cache' => [
        'get_all_characters' => true,
        'get_character_by_id' => true,
        // Add more route-specific cache flags as needed
    ],
    'cache_ttl' => 3600 // Default cache time-to-live in seconds (1 hour)
];