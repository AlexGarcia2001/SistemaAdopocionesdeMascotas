<?php
// public/index.php

// Mostrar errores para depuración (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- AÑADIDO: Configurar la ruta del log de errores de PHP ---
// Esto forzará a PHP a escribir todos los errores en este archivo.
// Asegúrate de que la carpeta 'logs' exista en la raíz de tu proyecto (adopciones-api/logs/)
$phpErrorLogPath = __DIR__ . '/../logs/php_errors.log';
ini_set('error_log', $phpErrorLogPath);
error_log("DEBUG-INDEX: PHP error log path set to: " . $phpErrorLogPath);
// --- FIN AÑADIDO ---


// Autocargar las clases del proyecto
require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno (asegurarse de que Dotenv se cargue al inicio)
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// === INICIO DE CAMBIOS NECESARIOS PARA EL FUNCIONAMIENTO DE LA API ===
// Importar las clases de conexión y logger
use App\DB\Connection;
use App\Utils\Logger; // Asegúrate de que esta clase exista en src/Utils/Logger.php

// Inicializar la conexión a la base de datos y el logger al inicio de la aplicación
// Esto asegura que estén disponibles para el router y los controladores.
Connection::getInstance();
// Solo inicializa Logger si estás seguro de que el archivo src/Utils/Logger.php existe
// Si no existe, esta línea causará un error fatal que ahora se registrará en php_errors.log
Logger::getInstance();
// === FIN DE CAMBIOS NECESARIOS PARA EL FUNCIONAMIENTO DE LA API ===


// Obtener la URI de la solicitud y el método HTTP
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// --- INICIO DE LA LÓGICA PARA LIMPIAR LA URI ---
// Asumiendo que tu base de la URL es /adopciones-api/
// Si tu proyecto está directamente en htdocs (ej. http://localhost/), entonces $basePath sería '/' o vacío.
$basePath = '/adopciones-api'; // <-- AJUSTA ESTO SI TU CARPETA ES DIFERENTE O ESTÁS EN LA RAÍZ

// Log de la URI ORIGINAL (Va al log de errores de PHP por defecto)
error_log("DEBUG-INDEX: URI ORIGINAL en index.php: " . $requestUri);

// Eliminar la base de la URL de la requestUri
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Asegurarse de que la URI limpia siempre comience con '/'
if (empty($requestUri)) {
    $requestUri = '/';
} elseif ($requestUri[0] !== '/') {
    $requestUri = '/' . $requestUri;
}
// --- FIN DE LA LÓGICA PARA LIMPIAR LA URI ---


// Log de la URI LIMPIA para depuración (Va al log de errores de PHP por defecto)
error_log("DEBUG-INDEX: URI LIMPIA FINAL en index.php: " . $requestUri . " | Método: " . $requestMethod);

// DEBUG-PATH: Ruta raíz calculada (desde index.php) (Va al log de errores de PHP por defecto)
error_log("DEBUG-PATH: Ruta raíz calculada (desde index.php): " . dirname(__DIR__));


// Parsear el cuerpo de la solicitud JSON para POST, PUT, PATCH
$requestData = null;
if (in_array($requestMethod, ['POST', 'PUT', 'PATCH'])) {
    $input = file_get_contents("php://input");
    // Solo intentar decodificar si hay contenido y no está vacío
    if ($input !== false && $input !== '') {
        $requestData = json_decode($input, true); // true para obtener un array asociativo
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("ERROR: Fallo al decodificar JSON en index.php: " . json_last_error_msg() . " | Input: " . $input);
            $requestData = null; // Asegurarse de que sea null en caso de error
        }
    }
    // Este log ya existía en tu versión, no lo he añadido yo.
    error_log("DEBUG-INDEX: Request Data en index.php: " . print_r($requestData, true));
}

// Incluir el archivo de rutas de la API
require_once __DIR__ . '/../src/Routes/api.php';

?>
