<?php

return [
    'paths' => ['api/*', 'broadcasting/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',          // Local frontend
        'http://localhost:5174',          // Alternative local port
        'https://api-tracer.primaesemkrada.com', // Production API
        'https://tracer.primaesemkrada.com', // Production frontend
        'https://6ad2-139-228-40-37.ngrok-free.app '     // ngrok
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];


