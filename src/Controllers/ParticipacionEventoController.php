<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\ParticipacionEvento; // Asegúrate de importar el modelo
use PDOException;
use PDO;
// SECURITY: Importar el AuthMiddleware
use App\Middleware\AuthMiddleware;

class ParticipacionEventoController
{
    private $db;
    private $participacionModel;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->participacionModel = new ParticipacionEvento();

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
     * SECURITY: Método de ayuda para verificar el rol del usuario autenticado.
     * @param int $requiredRoleId El ID del rol requerido para la acción.
     * @return bool True si el usuario tiene el rol requerido, false en caso contrario (y detiene la ejecución).
     */
    private function checkUserRole(int $requiredRoleId): bool
    {
        $user = AuthMiddleware::getAuthenticatedUser();

        if (is_null($user)) {
            // Esto no debería ocurrir si AuthMiddleware::handle() se llamó antes,
            // pero es una salvaguarda.
            http_response_code(401); // Unauthorized
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Usuario no autenticado.']);
            exit();
        }

        // Asegurarse de que id_rol existe y es numérico para la comparación
        if (!isset($user['id_rol']) || (int)$user['id_rol'] !== $requiredRoleId) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes los permisos necesarios para realizar esta acción.']);
            exit();
        }

        return true;
    }

    // Método para crear una nueva participación en evento
    // SECURITY: Permite a un usuario registrarse a sí mismo o a un administrador registrar a cualquier usuario.
    public function createParticipacionEvento(?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación
        AuthMiddleware::handle(); 
        $user = AuthMiddleware::getAuthenticatedUser();

        if (is_null($data)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar campos obligatorios
        if (
            !isset($data['id_usuario']) || !is_numeric($data['id_usuario']) ||
            !isset($data['id_evento']) || !is_numeric($data['id_evento']) ||
            !isset($data['fecha_registro']) || empty($data['fecha_registro']) // fecha_registro es obligatoria
        ) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para crear la participación en el evento (id_usuario, id_evento, fecha_registro).'
            ]);
            return;
        }

        // SECURITY: Lógica de autorización para la creación
        // Si el usuario no es administrador Y el id_usuario de la petición no coincide con su propio ID
        $is_admin = ((int)$user['id_rol'] === 1);
        $is_self_registration = ((int)$user['id_usuario'] === (int)$data['id_usuario']);

        if (!$is_admin && !$is_self_registration) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para registrar a otro usuario en un evento.']);
            exit(); // Importante para detener la ejecución
        }

        // Asignar datos a las propiedades del modelo
        $this->participacionModel->id_usuario = (int) $data['id_usuario'];
        $this->participacionModel->id_evento = (int) $data['id_evento'];
        $this->participacionModel->fecha_registro = $data['fecha_registro'];

        try {
            // Opcional: verificar si la participación ya existe antes de intentar crear
            // Necesitarías un método 'exists' en tu modelo ParticipacionEvento para esto
            // if ($this->participacionModel->exists($this->participacionModel->id_usuario, $this->participacionModel->id_evento)) {
            //     http_response_code(409); // Conflict
            //     echo json_encode([
            //         'status' => 'error',
            //         'message' => 'El usuario ya está registrado en este evento.'
            //     ]);
            //     return;
            // }

            if ($this->participacionModel->create()) {
                http_response_code(201); // Created
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Participación en evento registrada exitosamente.'
                ]);
            } else {
                http_response_code(503); // Service Unavailable
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo registrar la participación en el evento.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            if ($e->getCode() == '23000') { // Violación de integridad (clave foránea)
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar la participación. Asegúrese de que el ID de usuario y el ID de evento existan.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al registrar la participación: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para obtener todas las participaciones en eventos
    // SECURITY: Permite al Administrador ver todas, al usuario normal solo las suyas.
    public function getAllParticipacionesEvento()
    {
        // SECURITY: Verificar autenticación
        AuthMiddleware::handle(); 
        $user = AuthMiddleware::getAuthenticatedUser();

        try {
            if ((int)$user['id_rol'] === 1) { // Si es administrador, obtiene todas las participaciones
                $stmt = $this->participacionModel->getAll();
                $participaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else { // Si no es administrador, obtiene solo sus propias participaciones
                // Asumiendo que existe un método en el modelo para esto
                $stmt = $this->participacionModel->getParticipationsByUserId($user['id_usuario']);
                $participaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if ($participaciones) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Participaciones en eventos obtenidas exitosamente.',
                    'data' => $participaciones
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron participaciones en eventos.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener participaciones en eventos: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener una participación en evento por ID
    // SECURITY: Permite al Administrador ver cualquiera, al usuario normal solo las suyas.
    public function getParticipacionEventoById($id)
    {
        // SECURITY: Verificar autenticación
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();

        if (!isset($id) || empty($id)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID de participación.'
            ]);
            return;
        }

        try {
            $participacionData = $this->participacionModel->getById($id);

            if (!$participacionData) {
                http_response_code(404); // Not Found
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Participación en evento no encontrada.'
                ]);
                return;
            }

            // SECURITY: Lógica de autorización
            // Si el usuario no es administrador Y el id_usuario de la participación no coincide con su propio ID
            $is_admin = ((int)$user['id_rol'] === 1);
            $is_owner = ((int)$user['id_usuario'] === (int)$participacionData['id_usuario']);

            if (!$is_admin && !$is_owner) {
                http_response_code(403); // Forbidden
                echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para ver esta participación en evento.']);
                return;
            }

            http_response_code(200); // OK
            echo json_encode([
                'status' => 'success',
                'message' => 'Participación en evento obtenida exitosamente.',
                'data' => $participacionData
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener participación en evento: ' . $e->getMessage()
            ]);
        }
    }

    // Método para actualizar una participación en evento
    // SECURITY: Permite al Administrador actualizar cualquiera, al usuario normal solo las suyas.
    public function updateParticipacionEvento($id, ?array $data = null) // Nuevo método
    {
        // SECURITY: Verificar autenticación
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();

        if (is_null($data)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar que el ID de la participación es numérico
        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de participación válido para actualizar.'
            ]);
            return;
        }

        // Validar campos obligatorios para la actualización
        if (
            !isset($data['id_usuario']) || !is_numeric($data['id_usuario']) ||
            !isset($data['id_evento']) || !is_numeric($data['id_evento']) ||
            !isset($data['fecha_registro']) || empty($data['fecha_registro'])
        ) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para actualizar la participación (id_usuario, id_evento, fecha_registro).'
            ]);
            return;
        }

        // Obtener la participación existente para verificar permisos
        $existingParticipation = $this->participacionModel->getById($id);

        if (!$existingParticipation) {
            http_response_code(404); // Not Found
            echo json_encode(['status' => 'info', 'message' => 'Participación en evento no encontrada para actualizar.']);
            return;
        }

        // SECURITY: Lógica de autorización para la actualización
        // Si el usuario no es administrador Y el id_usuario de la participación no coincide con su propio ID
        $is_admin = ((int)$user['id_rol'] === 1);
        $is_owner = ((int)$user['id_usuario'] === (int)$existingParticipation['id_usuario']);

        if (!$is_admin && !$is_owner) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para actualizar esta participación en evento.']);
            return;
        }

        // Asignar datos a las propiedades del modelo
        $this->participacionModel->id_participacion = (int) $id; // Asignar el ID de la participación a actualizar
        $this->participacionModel->id_usuario = (int) $data['id_usuario'];
        $this->participacionModel->id_evento = (int) $data['id_evento'];
        $this->participacionModel->fecha_registro = $data['fecha_registro'];

        try {
            if ($this->participacionModel->update()) { // Llama al método update del modelo
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Participación en evento actualizada exitosamente.'
                ]);
            } else {
                http_response_code(503); // Service Unavailable (o 400 si la actualización no afectó filas)
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo actualizar la participación en el evento o no se realizaron cambios.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            if ($e->getCode() == '23000') { // Violación de integridad (ej. clave foránea)
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la participación. Asegúrese de que el ID de usuario y el ID de evento existan.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la participación: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para eliminar una participación en evento
    // SECURITY: Permite al Administrador eliminar cualquiera, al usuario normal solo las suyas.
    public function deleteParticipacionEvento($id)
    {
        // SECURITY: Verificar autenticación
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();

        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de participación válido para eliminar.'
            ]);
            return;
        }

        // Obtener la participación existente para verificar permisos
        $existingParticipation = $this->participacionModel->getById($id);

        if (!$existingParticipation) {
            http_response_code(404);
            echo json_encode(['status' => 'info', 'message' => 'Participación en evento no encontrada para eliminar.']);
            return;
        }

        // SECURITY: Lógica de autorización
        // Si el usuario no es administrador Y el id_usuario de la participación no coincide con su propio ID
        if ((int)$user['id_rol'] !== 1 && (int)$user['id_usuario'] !== (int)$existingParticipation['id_usuario']) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para eliminar esta participación en evento.']);
            return;
        }

        $this->participacionModel->id_participacion = (int) $id;

        try {
            if ($this->participacionModel->delete()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Participación en evento eliminada exitosamente.'
                ]);
            } else {
                http_response_code(404); // Not Found (aunque ya lo verificamos, es un fallback)
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró la participación en evento para eliminar o ya fue eliminada.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar la participación en evento: ' . $e->getMessage()
            ]);
        }
    }
}