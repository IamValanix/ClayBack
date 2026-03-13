<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí configuramos qué dominios externos pueden hacer peticiones a tu API.
    |
    */

    // Permitimos todas las rutas que empiecen con /api/
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Permitimos todos los métodos (GET, POST, PUT, DELETE, etc.)
    'allowed_methods' => ['*'],

    // IMPORTANTE: Solo tus dominios de producción pueden acceder
    'allowed_origins' => [
        'https://eventscye.com',
        'https://www.eventscye.com',
    ],

    'allowed_origins_patterns' => [],

    // Permitimos todos los headers (Content-Type, Authorization, etc.)
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    | Debe estar en TRUE porque en el frontend (React/Vue) 
    | configuramos axios con 'withCredentials: true'
    */
    'supports_credentials' => true,

];
