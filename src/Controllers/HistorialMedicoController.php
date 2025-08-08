<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\HistorialMedico; // Asegúrate de importar el modelo HistorialMedico
use PDOException;
use PDO;
// SECURITY: Importar el AuthMiddleware
use App\Middleware\AuthMiddleware;

class HistorialMedicoController
{
    private $db;
    private $historialMedicoModel;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->historialMedicoModel = new HistorialMedico();

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

    // Método para crear un nuevo registro de historial médico
    // SECURITY: Protegido por rol de Administrador
    public function createHistorialMedico(?array $data = null) // Ahora recibe $data como parámetro
    {
        error_log("DEBUG CONTROLLER: createHistorialMedico - Iniciando método.");
        error_log("DEBUG CONTROLLER: Datos recibidos para creación: " . json_encode($data));

        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        error_log("DEBUG CONTROLLER: AuthMiddleware::handle() ejecutado.");
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)
        error_log("DEBUG CONTROLLER: checkUserRole(1) ejecutado. Usuario es Administrador.");

        if (is_null($data)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            error_log("DEBUG CONTROLLER: Error 400 - JSON inválido o ausente.");
            return;
        }

        // Validar campos obligatorios
        if (
            !isset($data['id_mascota']) || !is_numeric($data['id_mascota']) ||
            !isset($data['fecha_visita']) || empty($data['fecha_visita']) ||
            !isset($data['diagnostico']) || empty($data['diagnostico']) ||
            !isset($data['id_veterinario']) || !is_numeric($data['id_veterinario'])
        ) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para crear el historial médico (id_mascota, fecha_visita, diagnostico, id_veterinario).'
            ]);
            error_log("DEBUG CONTROLLER: Error 400 - Faltan datos obligatorios. Datos: " . json_encode($data));
            return;
        }

        // Asignar datos a las propiedades del modelo
        $this->historialMedicoModel->id_mascota = (int) $data['id_mascota'];
        $this->historialMedicoModel->fecha_visita = $data['fecha_visita'];
        $this->historialMedicoModel->tipo_visita = $data['tipo_visita'] ?? null; // ¡CORRECCIÓN CLAVE AÑADIDA AQUÍ!
        $this->historialMedicoModel->diagnostico = $data['diagnostico'];
        $this->historialMedicoModel->tratamiento = $data['tratamiento'] ?? null;
        $this->historialMedicoModel->observaciones = $data['observaciones'] ?? null;
        $this->historialMedicoModel->id_veterinario = (int) $data['id_veterinario'];
        $this->historialMedicoModel->vacunas_aplicadas = $data['vacunas_aplicadas'] ?? null;
        $this->historialMedicoModel->proxima_cita = $data['proxima_cita'] ?? null;

        error_log("DEBUG CONTROLLER: Propiedades del modelo asignadas. Datos del modelo: " . json_encode([
            'id_mascota' => $this->historialMedicoModel->id_mascota,
            'fecha_visita' => $this->historialMedicoModel->fecha_visita,
            'tipo_visita' => $this->historialMedicoModel->tipo_visita, // Asegurarse de que se loguea
            'diagnostico' => $this->historialMedicoModel->diagnostico,
            'tratamiento' => $this->historialMedicoModel->tratamiento,
            'observaciones' => $this->historialMedicoModel->observaciones,
            'id_veterinario' => $this->historialMedicoModel->id_veterinario,
            'vacunas_aplicadas' => $this->historialMedicoModel->vacunas_aplicadas,
            'proxima_cita' => $this->historialMedicoModel->proxima_cita
        ]));

        try {
            error_log("DEBUG CONTROLLER: Intentando llamar a historialMedicoModel->create().");
            if ($this->historialMedicoModel->create()) {
                http_response_code(201); // Created
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Historial médico creado exitosamente.'
                ]);
                error_log("DEBUG CONTROLLER: Historial médico creado exitosamente.");
            } else {
                http_response_code(503); // Service Unavailable
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear el historial médico.'
                ]);
                error_log("DEBUG CONTROLLER: Error 503 - No se pudo crear el historial médico (modelo retornó false).");
            }
        } catch (PDOException $e) {
            http_response_code(500);
            $errorMessage = 'Error al crear el historial médico: ' . $e->getMessage();
            if ($e->getCode() == '23000') { // Violación de integridad (ej. clave foránea)
                $errorMessage = 'Error al crear el historial médico. Asegúrese de que el ID de mascota y el ID de veterinario existan.';
            }
            echo json_encode([
                'status' => 'error',
                'message' => $errorMessage,
                'details' => $e->getMessage()
            ]);
            error_log("ERROR CONTROLLER: PDOException al crear historial médico: " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
        } catch (\Exception $e) { // Captura cualquier otra excepción general
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Ocurrió un error inesperado al crear el historial médico: ' . $e->getMessage(),
                'details' => $e->getMessage()
            ]);
            error_log("ERROR CONTROLLER: Excepción general al crear historial médico: " . $e->getMessage());
        }
    }

    // Método para obtener todos los registros de historial médico
    // SECURITY: Protegido por rol de Administrador
    public function getAllHistorialMedico()
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        try {
            $stmt = $this->historialMedicoModel->getAll();
            $historiales = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($historiales) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Historiales médicos obtenidos exitosamente.',
                    'data' => $historiales
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron historiales médicos.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener historiales médicos: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener un registro de historial médico por ID
    // SECURITY: Protegido por rol de Administrador (o el propietario de la mascota)
    public function getHistorialMedicoById($id)
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $user = AuthMiddleware::getAuthenticatedUser(); // Obtener el usuario autenticado

        if (!isset($id) || empty($id)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID de historial médico.'
            ]);
            return;
        }

        try {
            $historialData = $this->historialMedicoModel->getById($id);

            if ($historialData) {
                // Si el usuario no es administrador, verificar si es el propietario de la mascota
                // NOTA: Para que esto funcione, el método getById() del modelo HistorialMedico
                // debe incluir el 'id_usuario_propietario' de la mascota asociada en los datos devueltos.
                if ((int)$user['id_rol'] !== 1 && (int)$user['id_usuario'] !== (int)$historialData['id_usuario_propietario']) {
                    http_response_code(403); // Forbidden
                    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para ver este historial médico.']);
                    return;
                }

                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Historial médico obtenido exitosamente.',
                    'data' => $historialData
                ]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Historial médico no encontrado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener historial médico: ' . $e->getMessage()
            ]);
        }
    }

    // Método para actualizar un registro de historial médico existente
    // SECURITY: Protegido por rol de Administrador
    public function updateHistorialMedico($id, ?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        if (is_null($data)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar campos obligatorios para la actualización
        if (
            !isset($id) || !is_numeric($id) ||
            !isset($data['id_mascota']) || !is_numeric($data['id_mascota']) ||
            !isset($data['fecha_visita']) || empty($data['fecha_visita']) ||
            !isset($data['diagnostico']) || empty($data['diagnostico']) ||
            !isset($data['id_veterinario']) || !is_numeric($data['id_veterinario'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para actualizar el historial médico (id, id_mascota, fecha_visita, diagnostico, id_veterinario).'
            ]);
            return;
        }

        $this->historialMedicoModel->id_historial = (int) $id;
        $this->historialMedicoModel->id_mascota = (int) $data['id_mascota'];
        $this->historialMedicoModel->fecha_visita = $data['fecha_visita'];
        $this->historialMedicoModel->tipo_visita = $data['tipo_visita'] ?? null; // ¡CORRECCIÓN CLAVE AÑADIDA AQUÍ!
        $this->historialMedicoModel->diagnostico = $data['diagnostico'];
        $this->historialMedicoModel->tratamiento = $data['tratamiento'] ?? null;
        $this->historialMedicoModel->observaciones = $data['observaciones'] ?? null;
        $this->historialMedicoModel->id_veterinario = (int) $data['id_veterinario'];
        $this->historialMedicoModel->vacunas_aplicadas = $data['vacunas_aplicadas'] ?? null;
        $this->historialMedicoModel->proxima_cita = $data['proxima_cita'] ?? null;

        try {
            if ($this->historialMedicoModel->update()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Historial médico actualizado exitosamente.'
                ]);
            } else {
                http_response_code(503); // Service Unavailable
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo actualizar el historial médico. Asegúrese de que el ID exista y los datos sean válidos.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            if ($e->getCode() == '23000') { // Violación de integridad (ej. clave foránea)
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar el historial médico. Asegúrese de que el ID de mascota y el ID de veterinario existan.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar el historial médico: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para eliminar un registro de historial médico
    // SECURITY: Protegido por rol de Administrador
    public function deleteHistorialMedico($id)
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de historial médico válido para eliminar.'
            ]);
            return;
        }

        $this->historialMedicoModel->id_historial = (int) $id;

        try {
            if ($this->historialMedicoModel->delete()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Historial médico eliminado exitosamente.'
                ]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró el historial médico para eliminar o ya fue eliminado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar el historial médico: ' . $e->getMessage()
            ]);
        }
    }
}
