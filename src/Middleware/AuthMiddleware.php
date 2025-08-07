<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

class AuthMiddleware
{
    /**
     * @var array|null Almacena los datos del usuario autenticado después de la verificación del token.
     */
    public static $authenticatedUser = null;

    /**
     * Verifica la autenticación del usuario a través de un token JWT.
     * Si el token es válido, la ejecución continúa y los datos del usuario se almacenan.
     * Si no es válido o está ausente, detiene la ejecución y envía una respuesta de error.
     */
    public static function handle()
    {
        error_log("DEBUG AUTH_MIDDLEWARE: Iniciando verificación de autenticación.");

        // Cargar variables de entorno
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        // Obtener la clave secreta del archivo .env
        $secretKey = $_ENV['JWT_SECRET_KEY'] ?? '';

        if (empty($secretKey)) {
            error_log("ERROR AUTH_MIDDLEWARE: JWT_SECRET_KEY no está configurada en el archivo .env. Ruta: " . __DIR__ . '/../../');
            http_response_code(500); // Internal Server Error
            echo json_encode([
                'status' => 'error',
                'message' => 'Error de configuración del servidor: Clave JWT no definida.'
            ]);
            exit();
        }
        error_log("DEBUG AUTH_MIDDLEWARE: JWT_SECRET_KEY cargada.");

        // --- INICIO DE LAS LÍNEAS AÑADIDAS PARA PERMITIR RUTAS PÚBLICAS ---
        // Este bloque se asegura de que ciertas rutas GET no requieran autenticación.

        // Define las rutas GET que NO necesitan autenticación (son públicas).
        // Las rutas que terminan con '/' se tratan como prefijos para rutas con IDs (ej. /api/mascotas/123).
        $publicGetRoutes = [
            '/api/mascotas',         // Para obtener todas las mascotas
            '/api/mascotas/',        // Para obtener una mascota por ID (ej. /api/mascotas/123)
            '/api/refugios',         // Para obtener todos los refugios
            '/api/refugios/',        // Para obtener un refugio por ID
            '/api/galeriamultimedia', // Para obtener toda la galería
            '/api/galeriamultimedia/mascota/', // Para obtener galería por ID de mascota
            '/api/login',            // La ruta de login (aunque es POST, la incluimos aquí para claridad)
            '/api/usuarios/register', // La ruta de registro (aunque es POST, la incluimos aquí)
            // Puedes añadir más rutas públicas aquí si las necesitas en el futuro
            // Por ejemplo:
            // '/api/eventos',
            // '/api/eventos/',
            // '/api/historialmedico/mascota/',
        ];

        // Obtener la URI de la solicitud actual
        $requestUri = $_SERVER['REQUEST_URI'];
        // Define la base de tu URL. Si tu proyecto está en http://localhost/adopciones-api/, entonces es '/adopciones-api'
        $basePath = '/adopciones-api'; 

        // Limpiar la URI para compararla con nuestras rutas definidas
        if (strpos($requestUri, $basePath) === 0) {
            $requestUri = substr($requestUri, strlen($basePath));
        }
        // Asegurarse de que la URI limpia siempre comience con '/'
        if (empty($requestUri)) {
            $requestUri = '/';
        } elseif ($requestUri[0] !== '/') {
            $requestUri = '/' . $requestUri;
        }

        // Si la solicitud es un GET y la URI está en nuestra lista de rutas públicas, SALTAR la autenticación
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            foreach ($publicGetRoutes as $publicRoute) {
                // Manejar rutas con ID (ej. /api/mascotas/123)
                if (substr($publicRoute, -1) === '/') { 
                    if (str_starts_with($requestUri, $publicRoute)) {
                        $idPart = substr($requestUri, strlen($publicRoute));
                        if (is_numeric($idPart)) {
                            error_log("DEBUG AUTH_MIDDLEWARE: Ruta GET pública con ID detectada y omitida: " . $requestUri);
                            return; // ¡IMPORTANTE! Salta el resto de la función (no se requiere token)
                        }
                    }
                } 
                // Manejar rutas exactas (ej. /api/mascotas)
                else if ($requestUri === $publicRoute) { 
                    error_log("DEBUG AUTH_MIDDLEWARE: Ruta GET pública exacta detectada y omitida: " . $requestUri);
                    return; // ¡IMPORTANTE! Salta el resto de la función (no se requiere token)
                }
            }
        }
        // --- FIN DE LAS LÍNEAS AÑADIDAS ---


        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        error_log("DEBUG AUTH_MIDDLEWARE: Cabecera Authorization recibida: " . ($authHeader ? 'Presente' : 'Ausente'));


        if (empty($authHeader)) {
            http_response_code(401); // Unauthorized
            echo json_encode([
                'status' => 'error',
                'message' => 'Acceso denegado. No se proporcionó token de autenticación.'
            ]);
            error_log("ERROR AUTH_MIDDLEWARE: Token de autenticación ausente.");
            exit();
        }

        list($jwt) = sscanf($authHeader, 'Bearer %s');
        error_log("DEBUG AUTH_MIDDLEWARE: Token extraído: " . ($jwt ? 'Sí' : 'No') . " (primeros 20 caracteres: " . substr($jwt, 0, 20) . "...)");


        if (!$jwt) {
            http_response_code(401); // Unauthorized
            echo json_encode([
                'status' => 'error',
                'message' => 'Acceso denegado. Formato de token inválido.'
            ]);
            error_log("ERROR AUTH_MIDDLEWARE: Formato de token inválido (no se pudo extraer 'Bearer').");
            exit();
        }

        try {
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));
            error_log("DEBUG AUTH_MIDDLEWARE: Token decodificado exitosamente.");

            // Almacenar los datos del usuario autenticado en la propiedad estática
            // Asegurarse de que 'data' existe en el payload del token
            if (isset($decoded->data) && is_object($decoded->data)) {
                self::$authenticatedUser = (array) $decoded->data; // Convertir a array asociativo
                error_log("DEBUG AUTH_MIDDLEWARE: Token JWT decodificado y válido. Usuario ID: " . self::$authenticatedUser['id_usuario'] . " | Rol ID: " . self::$authenticatedUser['id_rol']);
            } else {
                // Si el payload no tiene la estructura esperada, es un token inválido
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Acceso denegado. Estructura de token inválida (payload de usuario ausente).',
                    'details' => 'Missing user data in token payload.'
                ]);
                error_log("ERROR AUTH_MIDDLEWARE: Estructura de token inválida: 'data' no encontrada o no es un objeto.");
                exit();
            }

        } catch (\Firebase\JWT\ExpiredException $e) {
            http_response_code(401); // Unauthorized
            echo json_encode([
                'status' => 'error',
                'message' => 'Acceso denegado. Token expirado.',
                'details' => $e->getMessage()
            ]);
            error_log("ERROR AUTH_MIDDLEWARE: Token expirado: " . $e->getMessage());
            exit();
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            http_response_code(401); // Unauthorized
            echo json_encode([
                'status' => 'error',
                'message' => 'Acceso denegado. Firma de token inválida.',
                'details' => $e->getMessage()
            ]);
            error_log("ERROR AUTH_MIDDLEWARE: Firma de token inválida: " . $e->getMessage());
            exit();
        } catch (\Firebase\JWT\BeforeValidException $e) {
            http_response_code(401); // Unauthorized
            echo json_encode([
                'status' => 'error',
                'message' => 'Acceso denegado. Token no válido aún.',
                'details' => $e->getMessage()
            ]);
            error_log("ERROR AUTH_MIDDLEWARE: Token no válido aún: " . $e->getMessage());
            exit();
        } catch (\Exception $e) {
            http_response_code(401); // Unauthorized
            echo json_encode([
                'status' => 'error',
                'message' => 'Acceso denegado. Token inválido o error desconocido.',
                'details' => $e->getMessage()
            ]);
            error_log("ERROR AUTH_MIDDLEWARE: Error desconocido al decodificar token: " . $e->getMessage());
            exit();
        }
        error_log("DEBUG AUTH_MIDDLEWARE: Verificación de autenticación completada exitosamente.");
    }

    /**
     * Obtiene los datos del usuario autenticado.
     * @return array|null Los datos del usuario o null si no hay usuario autenticado.
     */
    public static function getAuthenticatedUser()
    {
        return self::$authenticatedUser;
    }
}
