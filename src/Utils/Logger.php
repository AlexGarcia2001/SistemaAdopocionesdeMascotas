<?php

namespace App\Utils;

class Logger
{
    private static $logFile;
    private static $instance;

    // Constructor privado para el patrón Singleton
    private function __construct()
    {
        // Define la ruta del archivo de log. Se asume que la carpeta 'logs' está en la raíz del proyecto.
        // __DIR__ es el directorio actual (src/Utils), dirname(dirname(__DIR__)) sube 2 niveles para llegar a la raíz.
        self::$logFile = dirname(dirname(__DIR__)) . '/logs/app.log'; // <-- ¡CORREGIDO AQUÍ!

        // Asegúrate de que la carpeta de logs exista
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            // Intenta crear la carpeta. Los permisos 0775 son buenos para desarrollo.
            // El 'true' es para crear directorios recursivamente.
            if (!mkdir($logDir, 0775, true)) {
                // Si la creación falla, registra un error (esto irá al error_log de Apache si no puede escribir en ningún lado)
                error_log("ERROR: Could not create log directory: " . $logDir);
            }
        }
    }

    // Método para obtener la instancia Singleton del Logger
    public static function getInstance(): Logger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Escribe un mensaje en el archivo de log.
     * @param string $level Nivel del log (INFO, WARNING, ERROR, DEBUG).
     * @param string $message El mensaje a registrar.
     * @param array $context Datos adicionales para el log (opcional).
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}";

        if (!empty($context)) {
            $logEntry .= ' ' . json_encode($context);
        }

        $logEntry .= PHP_EOL; // Añade un salto de línea

        // Escribe el mensaje en el archivo de log. FILE_APPEND para añadir al final.
        // LOCK_EX para evitar que múltiples escrituras simultáneas corrompan el archivo.
        // Añadimos un try-catch aquí también, por si file_put_contents falla (ej. por permisos)
        try {
            file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        } catch (\Exception $e) {
            // Si no se puede escribir en el log de la aplicación, al menos registrar en el log de errores de PHP/Apache
            error_log("CRITICAL ERROR: Could not write to app log file: " . self::$logFile . " - " . $e->getMessage());
        }
    }

    /**
     * Métodos de conveniencia para diferentes niveles de log.
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }
}