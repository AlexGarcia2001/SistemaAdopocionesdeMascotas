<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\User;
use PDOException;
use PDO;
use Dotenv\Dotenv;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
// SEGURIDAD: Importar el AuthMiddleware
use App\Middleware\AuthMiddleware;
use App\Utils\Logger; // Importar la clase Logger

class UsersController
{
    private $db;
    private $userModel;
    private $logger; // Propiedad para la instancia del logger

    public function __construct()
    {
        // Obtener la instancia del Logger
        $this->logger = Logger::getInstance();

        // Cargar variables de entorno (necesario para JWT_SECRET_KEY y APP_URL)
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $this->db = Connection::getInstance()->getConnection();
        $this->userModel = new User();

        // Configuración de encabezados CORS y Content-Type para todas las respuestas de este controlador
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); // Permite acceso desde cualquier origen (para desarrollo)
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Manejo de peticiones OPTIONS (preflight requests de CORS)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit(); // Termina la ejecución para peticiones OPTIONS
        }
    }

    /**
     * Este método ya no se usará internamente en los métodos que reciben $data del enrutador.
     * Se mantiene aquí si hay otros métodos en el controlador que aún necesiten leer JSON directamente.
     *
     * Lee y decodifica la entrada JSON del cuerpo de la petición.
     * Solo debe usarse para métodos que esperan un cuerpo JSON (POST, PUT, PATCH).
     *
     * @return array|null Retorna un array asociativo con los datos JSON o null si hay un error o no hay JSON válido.
     */
    private function readJSONInput(): ?array
    {
        $input = file_get_contents("php://input");
        $data = json_decode($input, true);

        if ($input !== false && $input !== '' && json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error("Fallo al decodificar JSON: " . json_last_error_msg(), ['input' => $input]);
            return null;
        }
        return $data;
    }

    /**
     * SEGURIDAD: Método auxiliar para verificar el rol del usuario autenticado.
     * @param int $requiredRoleId El ID de rol requerido para la acción.
     * @return bool Verdadero si el usuario tiene el rol requerido, falso en caso contrario (y detiene la ejecución).
     */
    private function checkUserRole(int $requiredRoleId): bool
    {
        $user = AuthMiddleware::getAuthenticatedUser();

        if (is_null($user)) {
            // Esto no debería ocurrir si AuthMiddleware::handle() fue llamado antes,
            // pero es una salvaguardia.
            http_response_code(401); // No autorizado
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Usuario no autenticado.']);
            exit();
        }

        // Asegurarse de que id_rol existe y es numérico para la comparación
        if (!isset($user['id_rol']) || (int)$user['id_rol'] !== $requiredRoleId) {
            $this->logger->warning("Acceso denegado: Usuario " . ($user['id_usuario'] ?? 'N/A') . " con rol " . ($user['id_rol'] ?? 'N/A') . " intentó acceder a recurso que requiere el rol " . $requiredRoleId);
            http_response_code(403); // Prohibido
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes los permisos necesarios para realizar esta acción.']);
            exit();
        }

        return true;
    }

    // Método para crear un nuevo usuario
    // SEGURIDAD: Este método NO debe estar protegido por rol para el registro de usuarios
    public function createUser(?array $data = null)
    {
        $this->logger->debug("Entrando en UsersController->createUser()");

        if (is_null($data)) {
            $this->logger->error("JSON inválido o faltante en el cuerpo de la petición para createUser.");
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o faltante en el cuerpo de la petición.']);
            return;
        }
        $this->logger->debug("Datos recibidos para createUser: " . print_r($data, true));

        // Validar que los campos obligatorios estén presentes y no vacíos
        // Obligatorios: nombre_usuario, apellido, email, password, id_rol, estado
        if (
            !isset($data['nombre_usuario']) || empty($data['nombre_usuario']) ||
            !isset($data['apellido']) || empty($data['apellido']) ||
            !isset($data['email']) || empty($data['email']) ||
            !isset($data['password']) || empty($data['password']) || // Contraseña en texto plano
            !isset($data['id_rol']) || empty($data['id_rol']) ||
            !isset($data['estado']) || empty($data['estado'])
        ) {
            $this->logger->warning("Datos obligatorios faltantes o inválidos para createUser.", ['data' => $data]);
            http_response_code(400); // Solicitud incorrecta
            echo json_encode([
                'status' => 'error',
                'message' => 'Datos obligatorios faltantes para crear el usuario (nombre_usuario, apellido, email, password, id_rol, estado).'
            ]);
            return;
        }

        // Asignar los datos del cuerpo de la petición a las propiedades del modelo
        $this->userModel->nombre_usuario = $data['nombre_usuario'];
        $this->userModel->apellido = $data['apellido'];
        $this->userModel->email = $data['email'];
        // Hashear la contraseña antes de asignarla al modelo
        $this->userModel->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $this->userModel->telefono = $data['telefono'] ?? null;
        $this->userModel->direccion = $data['direccion'] ?? null;
        $this->userModel->id_rol = $data['id_rol'];
        $this->userModel->estado = $data['estado'];

        try {
            if ($this->userModel->create()) {
                $this->logger->info("Usuario creado exitosamente: " . $data['email']);
                http_response_code(201); // Creado
                echo json_encode([
                    'status' => 'éxito',
                    'message' => 'Usuario creado exitosamente.'
                ]);
            } else {
                $this->logger->warning("Fallo al crear usuario: " . $data['email']);
                http_response_code(503); // Servicio no disponible
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear el usuario.'
                ]);
            }
        } catch (PDOException $e) {
            $this->logger->error("Error PDO en createUser: " . $e->getMessage(), ['email' => $data['email'] ?? 'N/A', 'code' => $e->getCode()]);
            http_response_code(500);
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear el usuario: El ID de rol proporcionado no existe.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear el usuario: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para obtener todos los usuarios (NO NECESITA LEER JSON)
    // SEGURIDAD: Protegido por el rol de Administrador
    public function getAllUsers()
    {
        // SEGURIDAD: Autenticar y autorizar
        AuthMiddleware::handle(); // Primero autenticar
        $this->checkUserRole(1); // Luego autorizar (solo Administradores, id_rol = 1)

        $this->logger->debug("Entrando en UsersController->getAllUsers()");
        try {
            $stmt = $this->userModel->getAll();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($users) {
                $this->logger->info("Usuarios obtenidos exitosamente.");
                echo json_encode([
                    'status' => 'éxito',
                    'message' => 'Usuarios obtenidos exitosamente.',
                    'data' => $users
                ]);
            } else {
                $this->logger->info("No se encontraron usuarios.");
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron usuarios.'
                ]);
            }
        } catch (PDOException $e) {
            $this->logger->error("Error PDO en getAllUsers: " . $e->getMessage(), ['code' => $e->getCode()]);
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener usuarios: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener un usuario por ID (NO NECESITA LEER JSON)
    // SEGURIDAD: No protegido por rol (público o se puede añadir lógica para "solo el propio usuario o administrador")
    public function getUserById($id)
    {
        // SEGURIDAD: Autenticar (no se requiere un rol específico para ver un perfil)
        AuthMiddleware::handle();
        // Lógica de autorización más fina aquí:
        $authenticatedUser = AuthMiddleware::getAuthenticatedUser();
        if ((int)$authenticatedUser['id_usuario'] !== (int)$id && (int)$authenticatedUser['id_rol'] !== 1) {
            $this->logger->warning("Acceso denegado: Usuario " . ($authenticatedUser['id_usuario'] ?? 'N/A') . " intentó ver el perfil del usuario " . $id . " sin permiso.");
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permiso para ver este perfil.']);
            exit();
        }

        $this->logger->debug("Entrando en UsersController->getUserById con ID: " . $id);
        try {
            $userData = $this->userModel->getById($id);

            if ($userData) {
                http_response_code(200); // OK
                // No devolver el hash de la contraseña
                unset($userData['password']); // Asumiendo que la columna se llama 'password'
                $this->logger->info("Usuario obtenido exitosamente por ID: " . $id);
                echo json_encode([
                    'status' => 'éxito',
                    'message' => 'Usuario obtenido exitosamente.',
                    'data' => $userData
                ]);
            } else {
                $this->logger->info("Usuario no encontrado para ID: " . $id);
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Usuario no encontrado.'
                ]);
            }
        } catch (PDOException $e) {
            $this->logger->error("Error PDO en getUserById: " . $e->getMessage(), ['user_id' => $id, 'code' => $e->getCode()]);
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener usuario: ' . $e->getMessage()
            ]);
        }
    }

    // Método para actualizar un usuario
    // SEGURIDAD: Protegido por el rol de Administrador
    public function updateUser($id, ?array $data = null)
    {
        // SEGURIDAD: Autenticar y autorizar
        AuthMiddleware::handle(); // Primero autenticar
        $this->checkUserRole(1); // Luego autorizar (solo Administradores, id_rol = 1)

        $this->logger->debug("Entrando en UsersController->updateUser con ID: " . $id);

        if (is_null($data) || !isset($id) || empty($id)) {
            $this->logger->error("JSON inválido o ID de usuario faltante para updateUser.");
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ID de usuario faltante.']);
            return;
        }
        $this->logger->debug("Datos recibidos para updateUser: " . print_r($data, true));

        // Validar que los campos obligatorios estén presentes y no vacíos
        // (Solo aquellos esperados en una actualización, la contraseña se maneja por separado)
        if (
            !isset($data['nombre_usuario']) || empty($data['nombre_usuario']) ||
            !isset($data['apellido']) || empty($data['apellido']) ||
            !isset($data['email']) || empty($data['email']) ||
            !isset($data['id_rol']) || empty($data['id_rol']) ||
            !isset($data['estado']) || empty($data['estado'])
        ) {
            $this->logger->warning("Datos obligatorios faltantes para updateUser.", ['user_id' => $id, 'data' => $data]);
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Datos obligatorios faltantes para actualizar el usuario (nombre_usuario, apellido, email, id_rol, estado).'
            ]);
            return;
        }

        $this->userModel->id_usuario = $id;
        $this->userModel->nombre_usuario = $data['nombre_usuario'];
        $this->userModel->apellido = $data['apellido'];
        $this->userModel->email = $data['email'];
        // La contraseña no se actualiza a través de este endpoint, se usa updateUserPassword
        // Asegúrate de que tu modelo maneje que la contraseña puede ser nula o no enviada en la actualización general
        $this->userModel->password = $data['password'] ?? null; // Podría ser nulo si no se envía

        $this->userModel->telefono = $data['telefono'] ?? null;
        $this->userModel->direccion = $data['direccion'] ?? null;

        $this->userModel->id_rol = $data['id_rol'];
        $this->userModel->estado = $data['estado'];

        try {
            if ($this->userModel->update()) {
                $this->logger->info("Usuario actualizado exitosamente: " . $id);
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'éxito',
                    'message' => 'Usuario actualizado exitosamente.'
                ]);
            } else {
                $this->logger->warning("Fallo al actualizar usuario: " . $id . ". Usuario no encontrado o no se realizaron cambios.");
                http_response_code(503); // Servicio no disponible
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo actualizar el usuario. Asegúrese de que el ID exista y los datos sean válidos.'
                ]);
            }
        } catch (PDOException $e) {
            $this->logger->error("Error PDO en updateUser: " . $e->getMessage(), ['user_id' => $id, 'code' => $e->getCode()]);
            http_response_code(500);
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar el usuario: El ID de rol proporcionado no existe.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar el usuario: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para eliminar un usuario
    // SEGURIDAD: Protegido por el rol de Administrador
    public function deleteUser($id)
    {
        // SEGURIDAD: Autenticar y autorizar
        AuthMiddleware::handle(); // Primero autenticar
        $this->checkUserRole(1); // Luego autorizar (solo Administradores, id_rol = 1)

        $this->logger->debug("Entrando en UsersController->deleteUser con ID: " . $id);
        // Los métodos DELETE usualmente no tienen un cuerpo JSON, así que no llamamos a readJSONInput()
        // No es necesario verificar $data, ya que $id proviene de la URL.

        if (!isset($id) || empty($id)) {
            $this->logger->error("ID de usuario faltante para deleteUser.");
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó ID de usuario para la eliminación.'
            ]);
            return;
        }

        $this->userModel->id_usuario = $id;

        try {
            if ($this->userModel->delete()) {
                $this->logger->info("Usuario eliminado exitosamente: " . $id);
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'éxito',
                    'message' => 'Usuario eliminado exitosamente.'
                ]);
            } else {
                $this->logger->warning("Usuario no encontrado para eliminación o ya eliminado: " . $id);
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Usuario no encontrado para eliminación o ya eliminado.'
                ]);
            }
        } catch (PDOException $e) {
            $this->logger->error("Error PDO en deleteUser: " . $e->getMessage(), ['user_id' => $id, 'code' => $e->getCode()]);
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar usuario: ' . $e->getMessage()
                // Aquí podrías añadir un mensaje más específico si hay una FK que lo impide
            ]);
        }
    }

    // Método para iniciar sesión de un usuario
    // SEGURIDAD: Esta ruta NO está protegida, es para obtener el token
    public function loginUser(?array $requestData = null)
    {
        $this->logger->debug("Entrando en UsersController->loginUser()");

        if (is_null($requestData)) {
            $this->logger->error("JSON inválido o faltante en el cuerpo de la petición para loginUser.");
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o faltante en el cuerpo de la petición.']);
            return;
        }
        $this->logger->debug("Datos recibidos para inicio de sesión: " . print_r($requestData, true));

        // *** CORRECCIÓN CLAVE AQUÍ: Acceder a las claves que el frontend realmente envía ***
        // El frontend envía 'email' y 'password' para el login.
        $emailFromFrontend = $requestData['email'] ?? ''; 
        $passwordFromFrontend = $requestData['password'] ?? ''; 

        $this->logger->debug("Intento de inicio de sesión para email (desde frontend): '" . $emailFromFrontend . "'");

        // Validar que los campos no estén vacíos
        if (empty($emailFromFrontend) || empty($passwordFromFrontend)) {
            $this->logger->warning("Email o contraseña faltantes para el intento de inicio de sesión.");
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(['status' => 'error', 'message' => 'El email y la contraseña son obligatorios.']);
            return;
        }

        // Buscar al usuario en la base de datos usando el campo 'email'
        $user = $this->userModel->findByEmail($emailFromFrontend); 

        $this->logger->debug("Resultado de findByEmail (usando email del frontend): " . print_r($user, true));

        if ($user && password_verify($passwordFromFrontend, $user['password'])) {
            $this->logger->info("Usuario autenticado exitosamente: " . $user['email']);
            // --- INICIO: Lógica de generación de JWT ---

            // Cargar variables de entorno (ya cargadas en el constructor, pero reafirmado para mayor claridad)
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
            $secretKey = $_ENV['JWT_SECRET_KEY']; // Obtener la clave secreta de tu .env

            // Datos a incluir en el token (payload)
            $issuer = $_ENV['APP_URL'] ?? 'http://localhost/adopciones-api';
            $audience = $_ENV['APP_URL'] ?? 'http://localhost/adopciones-api';
            $issuedAt = time();
            $expirationTime = $issuedAt + 3600; // Token válido por 1 hora (3600 segundos)

            $payload = [
                'iss' => $issuer,           // Emisor del token
                'aud' => $audience,         // Audiencia (para quien es el token)
                'iat' => $issuedAt,         // Hora en que el token fue emitido
                'exp' => $expirationTime,   // Hora de expiración del token
                'data' => [                 // Datos específicos del usuario
                    'id_usuario' => $user['id_usuario'],
                    'nombre_usuario' => $user['nombre_usuario'], // Usar el nombre_usuario de la DB
                    'email' => $user['email'],
                    'id_rol' => $user['id_rol'] // Asegurarse de que id_rol esté en el payload
                ]
            ];

            // Generar el token JWT
            $jwt = JWT::encode($payload, $secretKey, 'HS256');
            $this->logger->debug("JWT generado exitosamente.");

            // --- FIN: Lógica de generación de JWT ---

            http_response_code(200);
            echo json_encode([
                'status' => 'éxito',
                'message' => 'Inicio de sesión exitoso.',
                'user' => [
                    'id_usuario' => $user['id_usuario'],
                    'nombre_usuario' => $user['nombre_usuario'],
                    'email' => $user['email'],
                    'id_rol' => $user['id_rol'],
                    'estado' => $user['estado']
                ],
                'token' => $jwt // ¡Añadir el token a la respuesta!
            ]);
        } else {
            $this->logger->warning("Fallo de inicio de sesión para email: '" . $emailFromFrontend . "'. Credenciales inválidas.");
            http_response_code(401); // No autorizado
            echo json_encode([
                'status' => 'error',
                'message' => 'Email o contraseña inválidos.' // Mensaje más genérico por seguridad
            ]);
        }
    }

    // Método para actualizar la contraseña de un usuario
    // SEGURIDAD: Protegido por el rol de Administrador
    public function updateUserPassword($id, ?array $data = null)
    {
        // SEGURIDAD: Autenticar y autorizar
        AuthMiddleware::handle(); // Primero autenticar
        $this->checkUserRole(1); // Luego autorizar (solo Administradores, id_rol = 1)

        $this->logger->debug("Entrando en UsersController->updateUserPassword con ID: " . $id);

        if (is_null($data) || !isset($id) || empty($id) || !isset($data['new_password']) || empty($data['new_password'])) {
            $this->logger->error("Datos obligatorios faltantes para updateUserPassword (ID de usuario y nueva contraseña).", ['user_id' => $id, 'data' => $data]);
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Datos obligatorios faltantes para actualizar la contraseña (ID de usuario y nueva contraseña).'
            ]);
            return;
        }
        $this->logger->debug("Datos recibidos para updateUserPassword: " . print_r($data, true));

        $newPasswordHash = password_hash($data['new_password'], PASSWORD_DEFAULT);

        try {
            if ($this->userModel->updatePassword($id, $newPasswordHash)) {
                $this->logger->info("Contraseña actualizada exitosamente para el usuario: " . $id);
                http_response_code(200);
                echo json_encode([
                    'status' => 'éxito',
                    'message' => 'Contraseña actualizada exitosamente.'
                ]);
            } else {
                $this->logger->warning("Usuario no encontrado para actualización de contraseña: " . $id);
                http_response_code(404);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Usuario no encontrado para actualizar contraseña.'
                ]);
            }
        } catch (PDOException $e) {
            $this->logger->error("Error PDO en updateUserPassword: " . $e->getMessage(), ['user_id' => $id, 'code' => $e->getCode()]);
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al actualizar contraseña: ' . $e->getMessage()
            ]);
        }
    }
}