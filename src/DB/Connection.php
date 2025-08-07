<?php

namespace App\DB;

use PDO;
use PDOException;
use Dotenv\Dotenv;
use App\Utils\Logger; // Importar la clase Logger

class Connection
{
    private static $instance = null;
    private $connection;
    private $logger; // Propiedad para la instancia del logger

    private function __construct()
    {
        // Obtener la instancia del Logger
        $this->logger = Logger::getInstance();

        // Cargar variables de entorno aquí, justo antes de intentar la conexión
        // La ruta es __DIR__ (src/DB) + /../../ para llegar a la raíz del proyecto.
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $dbHost = $_ENV['DB_HOST'];
        $dbName = $_ENV['DB_NAME'];
        $dbUser = $_ENV['DB_USER'];
        $dbPass = $_ENV['DB_PASS'];

        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->connection = new PDO($dsn, $dbUser, $dbPass, $options);
            // Registrar éxito de la conexión
            $this->logger->info("Database connection established successfully.");
        } catch (PDOException $e) {
            // Registrar el error de conexión usando el Logger
            $this->logger->error("Database connection error: " . $e->getMessage(), ['code' => $e->getCode()]);
            // Lanzar la excepción para que sea manejada en un nivel superior si es necesario
            throw new PDOException("Database connection error: " . $e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance(): Connection
    {
        if (self::$instance === null) {
            self::$instance = new Connection();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}