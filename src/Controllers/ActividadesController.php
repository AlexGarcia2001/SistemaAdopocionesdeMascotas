<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\Actividad; // Asegúrate de importar el modelo Actividad
use PDOException;
use PDO;
// SECURITY: Importar el AuthMiddleware
use App\Middleware\AuthMiddleware;

class ActividadesController
{
    private $db;
    private $actividadModel;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->actividadModel = new Actividad();

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

    // Método para crear una nueva actividad
    // SECURITY: Protegido por rol de Administrador
    public function createActividad(?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        if (is_null($data)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar que los campos obligatorios estén presentes y no vacíos
        if (
            !isset($data['nombre_actividad']) || empty($data['nombre_actividad']) ||
            !isset($data['fecha_hora']) || empty($data['fecha_hora']) ||
            !isset($data['tipo_actividad']) || empty($data['tipo_actividad']) ||
            !isset($data['estado']) || empty($data['estado'])
        ) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios para crear la actividad (nombre_actividad, fecha_hora, tipo_actividad, estado).'
            ]);
            return;
        }

        // Asignar datos del cuerpo de la solicitud a las propiedades del modelo
        $this->actividadModel->nombre_actividad = $data['nombre_actividad'];
        $this->actividadModel->descripcion = $data['descripcion'] ?? null;
        $this->actividadModel->fecha_hora = $data['fecha_hora'];
        // Asegurarse de que id_refugio sea null si no está presente o es vacío
        $this->actividadModel->id_refugio = isset($data['id_refugio']) && $data['id_refugio'] !== '' ? $data['id_refugio'] : null;
        $this->actividadModel->tipo_actividad = $data['tipo_actividad'];
        $this->actividadModel->estado = $data['estado'];

        try {
            if ($this->actividadModel->create()) {
                http_response_code(201); // Created
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Actividad creada exitosamente.'
                ]);
            } else {
                http_response_code(503); // Service Unavailable
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear la actividad.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear la actividad: El ID de refugio proporcionado no existe.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear la actividad: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para obtener todas las actividades
    // SECURITY: Protegido por autenticación (cualquier usuario logueado puede ver)
    public function getAllActividades()
    {
        // SECURITY: Verificar autenticación
        AuthMiddleware::handle(); 

        try {
            $stmt = $this->actividadModel->getAll();
            $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($actividades) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Actividades obtenidas exitosamente.',
                    'data' => $actividades
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron actividades.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener las actividades: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener una actividad por ID
    // SECURITY: Protegido por autenticación (cualquier usuario logueado puede ver)
    public function getActividadById($id)
    {
        // SECURITY: Verificar autenticación
        AuthMiddleware::handle(); 
        
        if (!isset($id) || empty($id)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID de la actividad.'
            ]);
            return;
        }

        try {
            // Ahora getById del modelo devuelve el array de datos directamente
            $actividadData = $this->actividadModel->getById($id);

            if ($actividadData) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Actividad obtenida exitosamente.',
                    'data' => $actividadData // Enviamos el array completo devuelto por el modelo
                ]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Actividad no encontrada.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener la actividad: ' . $e->getMessage()
            ]);
        }
    }

    // Método para actualizar una actividad existente
    // SECURITY: Protegido por rol de Administrador
    public function updateActividad($id, ?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        if (is_null($data)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        if (
            !isset($id) || empty($id) ||
            !isset($data['nombre_actividad']) || empty($data['nombre_actividad']) ||
            !isset($data['fecha_hora']) || empty($data['fecha_hora']) ||
            !isset($data['tipo_actividad']) || empty($data['tipo_actividad']) ||
            !isset($data['estado']) || empty($data['estado'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios para actualizar la actividad (id, nombre_actividad, fecha_hora, tipo_actividad, estado).'
            ]);
            return;
        }

        $this->actividadModel->id_actividad = $id;
        $this->actividadModel->nombre_actividad = $data['nombre_actividad'];
        $this->actividadModel->descripcion = $data['descripcion'] ?? null;
        $this->actividadModel->fecha_hora = $data['fecha_hora'];
        // Asegurarse de que id_refugio sea null si no está presente o es vacío
        $this->actividadModel->id_refugio = isset($data['id_refugio']) && $data['id_refugio'] !== '' ? $data['id_refugio'] : null;
        $this->actividadModel->tipo_actividad = $data['tipo_actividad'];
        $this->actividadModel->estado = $data['estado'];

        try {
            if ($this->actividadModel->update()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Actividad actualizada exitosamente.'
                ]);
            } else {
                http_response_code(503); // Service Unavailable
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo actualizar la actividad. Asegúrese de que el ID exista y los datos sean válidos.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la actividad: El ID de refugio proporcionado no existe.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la actividad: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para eliminar una actividad
    // SECURITY: Protegido por rol de Administrador
    public function deleteActividad($id)
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        if (!isset($id) || empty($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID de la actividad para eliminar.'
            ]);
            return;
        }

        $this->actividadModel->id_actividad = $id;

        try {
            if ($this->actividadModel->delete()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Actividad eliminada exitosamente.'
                ]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró la actividad para eliminar o ya fue eliminada.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar la actividad: ' . $e->getMessage()
            ]);
        }
    }
}