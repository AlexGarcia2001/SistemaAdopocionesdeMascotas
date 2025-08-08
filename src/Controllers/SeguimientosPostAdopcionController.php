<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\SeguimientoPostAdopcion; // Importa el nuevo modelo
use PDOException;
use PDO;
use App\Middleware\AuthMiddleware; // Para la seguridad y verificación de rol

class SeguimientosPostAdopcionController
{
    private $db;
    private $seguimientoModel;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->seguimientoModel = new SeguimientoPostAdopcion();

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
     * Método de ayuda para verificar el rol del usuario autenticado.
     * Los seguimientos post-adopción son una función de gestión, por lo que solo los administradores (rol 1)
     * tendrán acceso a las operaciones CRUD completas.
     * @param int $requiredRoleId El ID del rol requerido para la acción.
     * @return bool True si el usuario tiene el rol requerido, false en caso contrario (y detiene la ejecución).
     */
    private function checkAdminRole(int $requiredRoleId = 1): bool
    {
        AuthMiddleware::handle(); // Asegura que el usuario esté autenticado
        $user = AuthMiddleware::getAuthenticatedUser();

        if (is_null($user)) {
            http_response_code(401); // No autorizado
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Usuario no autenticado.']);
            exit();
        }

        if (!isset($user['id_rol']) || (int)$user['id_rol'] !== $requiredRoleId) {
            http_response_code(403); // Prohibido
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes los permisos de administrador necesarios para realizar esta acción.']);
            exit();
        }

        return true;
    }

    /**
     * Crea un nuevo registro de seguimiento post-adopción.
     * Requiere rol de Administrador.
     * @param array|null $data Datos del seguimiento desde el cuerpo de la petición.
     */
    public function createSeguimiento(?array $data = null)
    {
        $this->checkAdminRole(); // Solo administradores pueden crear seguimientos

        if (is_null($data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar campos obligatorios
        if (
            !isset($data['id_mascota']) || !is_numeric($data['id_mascota']) ||
            !isset($data['id_usuario_adoptante']) || !is_numeric($data['id_usuario_adoptante']) ||
            !isset($data['fecha_seguimiento']) || empty($data['fecha_seguimiento']) ||
            !isset($data['tipo_seguimiento']) || empty($data['tipo_seguimiento']) ||
            !isset($data['estado_mascota']) || empty($data['estado_mascota']) ||
            !isset($data['creado_por_id_usuario']) || !is_numeric($data['creado_por_id_usuario'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o son inválidos para crear el seguimiento (id_mascota, id_usuario_adoptante, fecha_seguimiento, tipo_seguimiento, estado_mascota, creado_por_id_usuario).'
            ]);
            return;
        }

        // Asignar datos al modelo
        $this->seguimientoModel->id_mascota = (int)$data['id_mascota'];
        $this->seguimientoModel->id_usuario_adoptante = (int)$data['id_usuario_adoptante'];
        $this->seguimientoModel->fecha_seguimiento = $data['fecha_seguimiento'];
        $this->seguimientoModel->tipo_seguimiento = $data['tipo_seguimiento'];
        $this->seguimientoModel->observaciones = $data['observaciones'] ?? null;
        $this->seguimientoModel->estado_mascota = $data['estado_mascota'];
        $this->seguimientoModel->recomendaciones = $data['recomendaciones'] ?? null;
        $this->seguimientoModel->creado_por_id_usuario = (int)$data['creado_por_id_usuario'];

        try {
            if ($this->seguimientoModel->create()) {
                http_response_code(201); // Creado
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Seguimiento creado exitosamente.',
                    'data' => ['id_seguimiento' => $this->seguimientoModel->id_seguimiento] // Devuelve el ID del nuevo seguimiento
                ]);
            } else {
                http_response_code(503); // Servicio no disponible
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear el seguimiento.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al crear el seguimiento: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene todos los registros de seguimiento post-adopción.
     * Requiere rol de Administrador.
     */
    public function getAllSeguimientos()
    {
        $this->checkAdminRole(); // Solo administradores pueden ver todos los seguimientos

        try {
            $stmt = $this->seguimientoModel->getAll();
            $seguimientos = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            if ($seguimientos) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Seguimientos obtenidos exitosamente.',
                    'data' => $seguimientos
                ]);
            } else {
                http_response_code(200); // OK, pero sin contenido
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron seguimientos.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener seguimientos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene un registro de seguimiento post-adopción por su ID.
     * Requiere rol de Administrador.
     * @param int $id El ID del seguimiento.
     */
    public function getSeguimientoById($id)
    {
        $this->checkAdminRole(); // Solo administradores pueden ver seguimientos por ID

        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No se proporcionó un ID de seguimiento válido.']);
            return;
        }

        try {
            $seguimientoData = $this->seguimientoModel->getById((int)$id);

            if ($seguimientoData) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Seguimiento obtenido exitosamente.',
                    'data' => $seguimientoData
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Seguimiento no encontrado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener seguimiento: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene registros de seguimiento post-adopción por ID de mascota.
     * Requiere rol de Administrador.
     * @param int $mascotaId El ID de la mascota.
     */
    public function getSeguimientosByMascotaId($mascotaId)
    {
        $this->checkAdminRole(); // Solo administradores pueden ver seguimientos por ID de mascota

        if (!isset($mascotaId) || !is_numeric($mascotaId)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No se proporcionó un ID de mascota válido.']);
            return;
        }

        try {
            $stmt = $this->seguimientoModel->getByMascotaId((int)$mascotaId);
            $seguimientos = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            if ($seguimientos) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Seguimientos de la mascota obtenidos exitosamente.',
                    'data' => $seguimientos
                ]);
            } else {
                http_response_code(200);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron seguimientos para esta mascota.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener seguimientos de la mascota: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene registros de seguimiento post-adopción por ID de usuario adoptante.
     * Requiere rol de Administrador.
     * @param int $usuarioAdoptanteId El ID del usuario adoptante.
     */
    public function getSeguimientosByUsuarioAdoptanteId($usuarioAdoptanteId)
    {
        $this->checkAdminRole(); // Solo administradores pueden ver seguimientos por ID de usuario adoptante

        if (!isset($usuarioAdoptanteId) || !is_numeric($usuarioAdoptanteId)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No se proporcionó un ID de usuario adoptante válido.']);
            return;
        }

        try {
            $stmt = $this->seguimientoModel->getByUsuarioAdoptanteId((int)$usuarioAdoptanteId);
            $seguimientos = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            if ($seguimientos) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Seguimientos del usuario adoptante obtenidos exitosamente.',
                    'data' => $seguimientos
                ]);
            } else {
                http_response_code(200);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron seguimientos para este usuario adoptante.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener seguimientos del usuario adoptante: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Actualiza un registro de seguimiento post-adopción existente.
     * Requiere rol de Administrador.
     * @param int $id El ID del seguimiento a actualizar.
     * @param array|null $data Datos del seguimiento desde el cuerpo de la petición.
     */
    public function updateSeguimiento($id, ?array $data = null)
    {
        $this->checkAdminRole(); // Solo administradores pueden actualizar seguimientos

        if (!is_numeric($id) || is_null($data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID de seguimiento inválido o JSON ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar campos obligatorios (los mismos que en create, ya que una actualización completa los requiere)
        if (
            !isset($data['id_mascota']) || !is_numeric($data['id_mascota']) ||
            !isset($data['id_usuario_adoptante']) || !is_numeric($data['id_usuario_adoptante']) ||
            !isset($data['fecha_seguimiento']) || empty($data['fecha_seguimiento']) ||
            !isset($data['tipo_seguimiento']) || empty($data['tipo_seguimiento']) ||
            !isset($data['estado_mascota']) || empty($data['estado_mascota']) ||
            !isset($data['creado_por_id_usuario']) || !is_numeric($data['creado_por_id_usuario'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o son inválidos para actualizar el seguimiento (id_mascota, id_usuario_adoptante, fecha_seguimiento, tipo_seguimiento, estado_mascota, creado_por_id_usuario).'
            ]);
            return;
        }

        // Asignar datos al modelo, incluyendo el ID
        $this->seguimientoModel->id_seguimiento = (int)$id;
        $this->seguimientoModel->id_mascota = (int)$data['id_mascota'];
        $this->seguimientoModel->id_usuario_adoptante = (int)$data['id_usuario_adoptante'];
        $this->seguimientoModel->fecha_seguimiento = $data['fecha_seguimiento'];
        $this->seguimientoModel->tipo_seguimiento = $data['tipo_seguimiento'];
        $this->seguimientoModel->observaciones = $data['observaciones'] ?? null;
        $this->seguimientoModel->estado_mascota = $data['estado_mascota'];
        $this->seguimientoModel->recomendaciones = $data['recomendaciones'] ?? null;
        $this->seguimientoModel->creado_por_id_usuario = (int)$data['creado_por_id_usuario'];

        try {
            if ($this->seguimientoModel->update()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Seguimiento actualizado exitosamente.'
                ]);
            } else {
                http_response_code(404); // No encontrado o sin cambios
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró el seguimiento para actualizar o no se realizaron cambios.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al actualizar el seguimiento: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Elimina un registro de seguimiento post-adopción.
     * Requiere rol de Administrador.
     * @param int $id El ID del seguimiento a eliminar.
     */
    public function deleteSeguimiento($id)
    {
        $this->checkAdminRole(); // Solo administradores pueden eliminar seguimientos

        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'No se proporcionó un ID de seguimiento válido para eliminar.']);
            return;
        }

        try {
            if ($this->seguimientoModel->delete((int)$id)) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Seguimiento eliminado exitosamente.'
                ]);
            } else {
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró el seguimiento para eliminar o ya fue eliminado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar el seguimiento: ' . $e->getMessage()
            ]);
        }
    }
}
