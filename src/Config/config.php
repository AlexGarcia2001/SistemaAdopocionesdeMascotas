<?php
// src/Config/config.php

// Cargar las variables de entorno si aún no se han cargado
// Esto asegura que Dotenv solo se cargue una vez
if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
    $dotenv->load();
}

// Definir constantes o configuraciones generales de la aplicación
// Podrías agregar más configuraciones aquí en el futuro
define('APP_NAME', 'Sistema de Gestión de Adopciones de Mascotas');
define('APP_VERSION', '1.0.0');

// Retornar un array con la configuración si prefieres accederla como array
return [
    'database' => [
        'host' => $_ENV['DB_HOST'],
        'name' => $_ENV['DB_NAME'],
        'user' => $_ENV['DB_USER'],
        'pass' => $_ENV['DB_PASS'],
    ],
    'app' => [
        'name' => APP_NAME,
        'version' => APP_VERSION,
    ],
    // Otras configuraciones como rutas de uploads, claves de API externas, etc.
];