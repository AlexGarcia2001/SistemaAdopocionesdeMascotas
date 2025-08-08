<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\Notificacion;
use PDOException;
use PDO;
use App\Middleware\AuthMiddleware;

class NotificacionController
{
    private $db;
    private $notificacionModel;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->notificacionModel = new Notificacion();

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    private function checkUserRole(int $requiredRoleId): bool
    {
        $user = AuthMiddleware::getAuthenticatedUser();

        if (is_null($user)) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Usuario no autenticado.']);
            exit();
        }

        if (!isset($user['id_rol']) || (int)$user['id_rol'] !== $requiredRoleId) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes los permisos necesarios para realizar esta acción.']);
            exit();
        }

        return true;
    }

    public function createNotificacion(?array $data = null)
    {
        AuthMiddleware::handle();
        $this->checkUserRole(1);

        if (is_null($data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        if (
            !isset($data['id_usuario']) || !is_numeric($data['id_usuario']) ||
            !isset($data['mensaje']) || empty($data['mensaje']) ||
            !isset($data['leida']) || !is_numeric($data['leida']) || !in_array($data['leida'], [0, 1])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para crear la notificación (id_usuario, mensaje, leida). "leida" debe ser 0 o 1.'
            ]);
            return;
        }

        $this->notificacionModel->id_usuario = (int) $data['id_usuario'];
        $this->notificacionModel->mensaje = $data['mensaje'];
        $this->notificacionModel->leida = (int) $data['leida'];
        $this->notificacionModel->tipo_notificacion = $data['tipo_notificacion'] ?? null;

        try {
            if ($this->notificacionModel->create()) {
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Notificación creada exitosamente.'
                ]);
            } else {
                http_response_code(503);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear la notificación.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            if ($e->getCode() == '23000') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear la notificación. Asegúrese de que el ID de usuario exista.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear la notificación: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function getAllNotificaciones()
    {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();

        try {
            if ((int)$user['id_rol'] === 1) {
                $stmt = $this->notificacionModel->getAll();
                $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->notificacionModel->getNotificationsByUserId($user['id_usuario']);
                $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            if ($notificaciones) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Notificaciones obtenidas exitosamente.',
                    'data' => $notificaciones
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron notificaciones.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener notificaciones: ' . $e->getMessage()
            ]);
        }
    }

    public function getNotificacionById($id)
    {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();

        if (!isset($id) || empty($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID de notificación.'
            ]);
            return;
        }

        try {
            $notificacionData = $this->notificacionModel->getById($id);

            if (!$notificacionData) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Notificación no encontrada.'
                ]);
                return;
            }

            if ((int)$user['id_rol'] === 1 || (int)$user['id_usuario'] === (int)$notificacionData['id_usuario']) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Notificación obtenida exitosamente.',
                    'data' => $notificacionData
                ]);
            } else {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para ver esta notificación.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener notificación: ' . $e->getMessage()
            ]);
        }
    }

    public function updateNotificacion($id, ?array $data = null)
    {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();

        if (is_null($data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        if (
            !isset($id) || !is_numeric($id) ||
            !isset($data['id_usuario']) || !is_numeric($data['id_usuario']) ||
            !isset($data['mensaje']) || empty($data['mensaje']) ||
            !isset($data['leida']) || !is_numeric($data['leida']) || !in_array($data['leida'], [0, 1])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para actualizar la notificación (id, id_usuario, mensaje, leida). "leida" debe ser 0 o 1.'
            ]);
            return;
        }

        $existingNotification = $this->notificacionModel->getById($id);

        if (!$existingNotification) {
            http_response_code(404);
            echo json_encode(['status' => 'info', 'message' => 'Notificación no encontrada para actualizar.']);
            return;
        }

        if ((int)$user['id_rol'] !== 1 && (int)$user['id_usuario'] !== (int)$existingNotification['id_usuario']) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para actualizar esta notificación.']);
            return;
        }

        $this->notificacionModel->id_notificacion = (int) $id;
        $this->notificacionModel->id_usuario = (int) $data['id_usuario'];
        $this->notificacionModel->mensaje = $data['mensaje'];
        $this->notificacionModel->leida = (int) $data['leida'];
        $this->notificacionModel->tipo_notificacion = $data['tipo_notificacion'] ?? null;

        try {
            if ($this->notificacionModel->update()) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Notificación actualizada exitosamente.'
                ]);
            } else {
                http_response_code(503);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo actualizar la notificación. Asegúrese de que el ID exista y los datos sean válidos.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            if ($e->getCode() == '23000') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la notificación. Asegúrese de que el ID de usuario exista.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la notificación: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function deleteNotificacion($id)
    {
        AuthMiddleware::handle();
        // REMOVIDO: $this->checkUserRole(1); // Esta línea causaba el 403 para usuarios no administradores

        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de notificación válido para eliminar.'
            ]);
            return;
        }

        $user = AuthMiddleware::getAuthenticatedUser(); // Obtener el usuario autenticado para la verificación de propiedad

        $existingNotification = $this->notificacionModel->getById($id);

        if (!$existingNotification) {
            http_response_code(404);
            echo json_encode(['status' => 'info', 'message' => 'Notificación no encontrada para eliminar.']);
            return;
        }

        // Verificar si el usuario es administrador O es el propietario de la notificación
        if ((int)$user['id_rol'] !== 1 && (int)$user['id_usuario'] !== (int)$existingNotification['id_usuario']) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para eliminar esta notificación.']);
            return;
        }

        $this->notificacionModel->id_notificacion = (int) $id;

        try {
            if ($this->notificacionModel->delete()) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Notificación eliminada exitosamente.'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró la notificación para eliminar o ya fue eliminada.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar el registro multimedia: ' . $e->getMessage()
            ]);
        }
    }

    // Método para marcar todas las notificaciones de un usuario como leídas
    public function markAllAsReadByUserId($userId)
    {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();

        if (!isset($userId) || !is_numeric($userId)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de usuario válido para marcar notificaciones como leídas.'
            ]);
            return;
        }

        // Verificar permisos: Administrador o el propio usuario
        if ((int)$user['id_rol'] !== 1 && (int)$user['id_usuario'] !== (int)$userId) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para marcar estas notificaciones como leídas.']);
            return;
        }

        try {
            if ($this->notificacionModel->markAllAsReadByUserId((int) $userId)) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Todas las notificaciones han sido marcadas como leídas.'
                ]);
            } else {
                http_response_code(200); // OK, pero sin cambios
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron notificaciones no leídas para este usuario o ya estaban todas leídas.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al marcar todas las notificaciones como leídas: ' . $e->getMessage()
            ]);
        }
    }

    public function markNotificacionAsRead($id)
    {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();

        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de notificación válido para marcar como leída.'
            ]);
            return;
        }

        $existingNotification = $this->notificacionModel->getById($id);

        if (!$existingNotification) {
            http_response_code(404);
            echo json_encode(['status' => 'info', 'message' => 'Notificación no encontrada para marcar como leída.']);
            return;
        }

        if ((int)$user['id_rol'] !== 1 && (int)$user['id_usuario'] !== (int)$existingNotification['id_usuario']) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para marcar esta notificación como leída.']);
            return;
        }

        try {
            if ($this->notificacionModel->markAsRead((int) $id)) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Notificación marcada como leída exitosamente.'
                ]);
            } else {
                http_response_code(200);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se pudo marcar la notificación como leída o ya estaba marcada.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al marcar notificación como leída: ' . $e->getMessage()
            ]);
        }
    }
}
