<?php
// src/Controllers/SolicitudAdopcionController.php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\SolicitudAdopcion;
use App\Models\Notificacion; 
use PDOException;
use PDO;
use App\Middleware\AuthMiddleware;

class SolicitudAdopcionController
{
    private $db;
    private $solicitudModel;
    private $notificacionModel; 

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->solicitudModel = new SolicitudAdopcion();
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

    public function getSolicitudesByUsuario($userId) {
        AuthMiddleware::handle();
        $authenticatedUser = AuthMiddleware::getAuthenticatedUser();

        if ((int)$authenticatedUser['id_usuario'] !== (int)$userId && (int)$authenticatedUser['id_rol'] !== 1) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para ver las solicitudes de este usuario.']);
            exit();
        }

        try {
            $stmt = $this->db->prepare("
                SELECT
                    sa.id_solicitud,
                    sa.id_usuario,
                    sa.id_mascota,
                    sa.fecha_solicitud,
                    sa.estado_solicitud,
                    sa.motivo,
                    sa.fecha_aprobacion_rechazo,
                    sa.observaciones,
                    m.nombre AS mascota_nombre,
                    u.nombre_usuario AS solicitante_nombre,
                    u.apellido AS solicitante_apellido
                FROM
                    solicitudesadopcion sa
                LEFT JOIN mascotas m ON sa.id_mascota = m.id_mascota
                JOIN
                    usuarios u ON sa.id_usuario = u.id_usuario
                WHERE sa.id_usuario = :userId
                ORDER BY sa.fecha_solicitud DESC
            ");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($solicitudes) {
                foreach ($solicitudes as &$solicitud) {
                    $solicitud['solicitante_nombre_completo'] = trim($solicitud['solicitante_nombre'] . ' ' . $solicitud['solicitante_apellido']);
                    unset($solicitud['solicitante_apellido']);
                }
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Solicitudes de adopción obtenidas exitosamente.',
                    'data' => $solicitudes,
                    'debug_data' => $solicitudes // ¡NUEVO! Añade los datos de solicitudes para depuración
                ]);
            } else {
                http_response_code(200);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron solicitudes de adopción para este usuario.',
                    'data' => [],
                    'debug_data' => [] // ¡NUEVO! Añade los datos de solicitudes para depuración
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener solicitudes de adopción por usuario: ' . $e->getMessage()
            ]);
        }
    }

    public function createSolicitudAdopcion(?array $data = null)
    {
        AuthMiddleware::handle();

        if (is_null($data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        if (
            !isset($data['id_usuario']) || !is_numeric($data['id_usuario']) ||
            !isset($data['id_mascota']) || !is_numeric($data['id_mascota'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para crear la solicitud de adopción (id_usuario, id_mascota).'
            ]);
            return;
        }

        $this->solicitudModel->id_usuario = (int) $data['id_usuario'];
        $this->solicitudModel->id_mascota = (int) $data['id_mascota'];
        $this->solicitudModel->fecha_solicitud = $data['fecha_solicitud'] ?? date('Y-m-d H:i:s');
        $this->solicitudModel->estado_solicitud = $data['estado_solicitud'] ?? 'Pendiente';
        $this->solicitudModel->motivo = $data['motivo'] ?? null; 
        $this->solicitudModel->fecha_aprobacion_rechazo = $data['fecha_aprobacion_rechazo'] ?? null;
        $this->solicitudModel->observaciones = $data['observaciones'] ?? null;

        try {
            if ($this->solicitudModel->create()) {
                http_response_code(201);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Solicitud de adopción creada exitosamente.'
                ]);
            } else {
                http_response_code(503);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear la solicitud de adopción.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            if ($e->getCode() == '23000') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear la solicitud. Asegúrese de que el ID de usuario y el ID de mascota existan.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear la solicitud: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function getAllSolicitudesAdopcion()
    {
        AuthMiddleware::handle();
        $this->checkUserRole(1);

        try {
            $stmt = $this->db->prepare("
                SELECT
                    sa.id_solicitud,
                    sa.id_usuario,
                    sa.id_mascota,
                    sa.fecha_solicitud,
                    sa.estado_solicitud,
                    sa.motivo,
                    sa.fecha_aprobacion_rechazo,
                    sa.observaciones,
                    m.nombre AS mascota_nombre,
                    u.nombre_usuario AS solicitante_nombre,
                    u.apellido AS solicitante_apellido
                FROM
                    solicitudesadopcion sa
                LEFT JOIN mascotas m ON sa.id_mascota = m.id_mascota
                JOIN
                    usuarios u ON sa.id_usuario = u.id_usuario
                ORDER BY sa.fecha_solicitud DESC
            ");
            $stmt->execute();
            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($solicitudes) {
                foreach ($solicitudes as &$solicitud) {
                    $solicitud['solicitante_nombre'] = trim($solicitud['solicitante_nombre'] . ' ' . $solicitud['solicitante_apellido']);
                    unset($solicitud['solicitante_apellido']);
                }
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Solicitudes de adopción obtenidas exitosamente.',
                    'data' => $solicitudes
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron solicitudes de adopción.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener solicitudes de adopción: ' . $e->getMessage()
            ]);
        }
    }

    public function getSolicitudAdopcionById($id)
    {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getAuthenticatedUser();

        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de solicitud válido.'
            ]);
            return;
        }

        try {
            $stmt = $this->db->prepare("
                SELECT
                    sa.id_solicitud,
                    sa.id_usuario,
                    sa.id_mascota,
                    sa.fecha_solicitud,
                    sa.estado_solicitud,
                    sa.motivo,
                    sa.fecha_aprobacion_rechazo,
                    sa.observaciones,
                    m.nombre AS mascota_nombre,
                    m.especie AS mascota_especie,
                    m.raza AS mascota_raza,
                    m.edad AS mascota_edad,
                    m.sexo AS mascota_sexo,
                    m.tamano AS mascota_tamano,
                    u.nombre_usuario AS solicitante_nombre,
                    u.apellido AS solicitante_apellido,
                    u.email AS solicitante_email,
                    u.telefono AS solicitante_telefono,
                    u.direccion AS solicitante_direccion
                FROM
                    solicitudesadopcion sa
                LEFT JOIN mascotas m ON sa.id_mascota = m.id_mascota
                JOIN
                    usuarios u ON sa.id_usuario = u.id_usuario
                WHERE sa.id_solicitud = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $solicitudData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($solicitudData) {
                if ((int)$user['id_rol'] !== 1 && (int)$user['id_usuario'] !== (int)$solicitudData['id_usuario']) {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para ver esta solicitud.']);
                    return;
                }
                
                $solicitudData['solicitante_nombre_completo'] = trim($solicitudData['solicitante_nombre'] . ' ' . $solicitudData['solicitante_apellido']);

                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Solicitud de adopción obtenida exitosamente.',
                    'data' => $solicitudData
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Solicitud de adopción no encontrada.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener solicitud de adopción: ' . $e->getMessage()
            ]);
        }
    }

    public function updateSolicitudAdopcion($id, ?array $data = null)
    {
        AuthMiddleware::handle();
        $this->checkUserRole(1);

        if (is_null($data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        if (!is_numeric($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'ID de solicitud no válido.'
            ]);
            return;
        }

        if (
            !isset($data['id_usuario']) || !is_numeric($data['id_usuario']) ||
            !isset($data['id_mascota']) || !is_numeric($data['id_mascota']) ||
            !isset($data['fecha_solicitud']) || empty($data['fecha_solicitud']) ||
            !isset($data['estado_solicitud']) || !in_array($data['estado_solicitud'], ['Pendiente', 'Aprobada', 'Rechazada', 'Cancelada'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para actualizar la solicitud (id_usuario, id_mascota, fecha_solicitud, estado_solicitud). Estado debe ser Pendiente, Aprobada, Rechazada o Cancelada.'
            ]);
            return;
        }

        $this->solicitudModel->id_solicitud = (int) $id;
        $this->solicitudModel->id_usuario = (int) $data['id_usuario'];
        $this->solicitudModel->id_mascota = (int) $data['id_mascota'];
        $this->solicitudModel->fecha_solicitud = $data['fecha_solicitud'];
        $this->solicitudModel->estado_solicitud = $data['estado_solicitud'];
        $this->solicitudModel->motivo = $data['motivo'] ?? null;
        $this->solicitudModel->fecha_aprobacion_rechazo = $data['fecha_aprobacion_rechazo'] ?? null;
        $this->solicitudModel->observaciones = $data['observaciones'] ?? null;

        try {
            if ($this->solicitudModel->update()) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Solicitud de adopción actualizada exitosamente.'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró la solicitud para actualizar o no se realizaron cambios.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            if ($e->getCode() == '23000') {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la solicitud. Asegúrese de que el ID de usuario y el ID de mascota existan.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la solicitud: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function updateEstadoSolicitud($id, ?array $data = null)
    {
        AuthMiddleware::handle();
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1) pueden cambiar el estado

        if (is_null($data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        if (!is_numeric($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'ID de solicitud no válido.'
            ]);
            return;
        }

        if (
            !isset($data['estado_solicitud']) || !in_array($data['estado_solicitud'], ['Pendiente', 'Aprobada', 'Rechazada', 'Cancelada'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Estado de solicitud no válido. Debe ser Pendiente, Aprobada, Rechazada o Cancelada.'
            ]);
            return;
        }

        $estado_solicitud = $data['estado_solicitud'];
        $observaciones = $data['comentarios'] ?? null;
        $fecha_aprobacion_rechazo = null;

        if ($estado_solicitud === 'Aprobada' || $estado_solicitud === 'Rechazada') {
            $fecha_aprobacion_rechazo = date('Y-m-d H:i:s');
        }

        try {
            // Primero, obtener los detalles de la solicitud ANTES de actualizarla
            // Esto es crucial para poder crear la notificación con los datos correctos del usuario y mascota
            $stmt = $this->db->prepare("
                SELECT
                    sa.id_usuario,
                    m.nombre AS mascota_nombre,
                    u.nombre_usuario,
                    u.email AS user_email
                FROM
                    solicitudesadopcion sa
                LEFT JOIN  mascotas m ON sa.id_mascota = m.id_mascota 
                JOIN
                    usuarios u ON sa.id_usuario = u.id_usuario
                WHERE sa.id_solicitud = :id
            ");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $solicitudDetails = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$solicitudDetails) {
                http_response_code(404);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró la solicitud para actualizar el estado.'
                ]);
                return;
            }

            // Ahora sí, actualizar el estado de la solicitud
            $this->solicitudModel->id_solicitud = (int) $id;
            $this->solicitudModel->estado_solicitud = $estado_solicitud;
            $this->solicitudModel->observaciones = $observaciones;
            $this->solicitudModel->fecha_aprobacion_rechazo = $fecha_aprobacion_rechazo;

            if ($this->solicitudModel->updateEstado()) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Estado de la solicitud de adopción actualizado exitosamente.'
                ]);

                // --- ¡NUEVO! Lógica para crear la notificación en la base de datos ---
                $id_usuario_notificar = $solicitudDetails['id_usuario'];
                $mascotaNombre = $solicitudDetails['mascota_nombre'];
                $nombre_usuario_notificar = $solicitudDetails['nombre_usuario'];
                $mensaje_notificacion = '';
                $tipo_notificacion = 'estado_solicitud'; 

                if ($estado_solicitud === 'Aprobada') {
                    $mensaje_notificacion = "¡Felicitaciones, {$nombre_usuario_notificar}! Tu solicitud de adopción para '{$mascotaNombre}' ha sido APROBADA.";
                } elseif ($estado_solicitud === 'Rechazada') {
                    $mensaje_notificacion = "Lamentamos informarte, {$nombre_usuario_notificar}, que tu solicitud de adopción para '{$mascotaNombre}' ha sido RECHAZADA.";
                } elseif ($estado_solicitud === 'Cancelada') {
                    $mensaje_notificacion = "Tu solicitud de adopción para '{$mascotaNombre}' ha sido CANCELADA.";
                } elseif ($estado_solicitud === 'Pendiente') {
                    $mensaje_notificacion = "El estado de tu solicitud de adopción para '{$mascotaNombre}' ha sido actualizado a PENDIENTE.";
                }

                if (!empty($mensaje_notificacion)) {
                    $this->notificacionModel->id_usuario = $id_usuario_notificar;
                    $this->notificacionModel->mensaje = $mensaje_notificacion;
                    $this->notificacionModel->leida = 0; 
                    $this->notificacionModel->tipo_notificacion = $tipo_notificacion;

                    try {
                        if ($this->notificacionModel->create()) {
                            error_log("INFO: Notificación creada para usuario ID {$id_usuario_notificar} sobre solicitud ID {$id}.");
                        } else {
                            error_log("ADVERTENCIA: Falló la creación de la notificación en la DB para usuario ID {$id_usuario_notificar}.");
                        }
                    } catch (PDOException $e) {
                        error_log("ERROR: Excepción al crear notificación en DB para usuario ID {$id_usuario_notificar}: " . $e->getMessage());
                    }
                }

            } else {
                http_response_code(503); 
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se pudo actualizar el estado de la solicitud o no se realizaron cambios.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al actualizar el estado de la solicitud: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteSolicitudAdopcion($id)
    {
        AuthMiddleware::handle();
        $this->checkUserRole(1);

        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de solicitud de adopción válido para eliminar.'
            ]);
            return;
        }

        $this->solicitudModel->id_solicitud = (int) $id;

        try {
            if ($this->solicitudModel->delete()) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Solicitud de adopción eliminada exitosamente.'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró la solicitud de adopción para eliminar o ya fue eliminada.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar la solicitud de adopción: ' . $e->getMessage()
            ]);
        }
    }
}
