<?php

return [
	'paths' => ['api/*', 'sanctum/csrf-cookie'],
	'allowed_origins' => ['http://localhost:5173'], // Sesuaikan port frontend
	'supports_credentials' => true,
];