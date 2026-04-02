<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',          // Local frontend
        'http://localhost:5174',          // Alternative local port
        'https://2830-139-228-40-7.ngrok-free.app',
        'https://laevo-frequently-pinkie.ngrok-free.dev', // Production/ngrok frontendfad07fefe70f44ea35a325c54446dcd5b7db2139
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];