<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',          // Local frontend
        'http://localhost:5174',          // Alternative local port
        'https://api-tracer.primaesemkrada.com', // Production API
        'https://tracer.primaesemkrada.com',     // Production frontend
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];