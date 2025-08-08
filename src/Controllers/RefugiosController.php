<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\Refugio; // Asegúrate de importar el modelo Refugio
use PDOException;
use PDO;
// SEGURIDAD: Importar el AuthMiddleware
use App\Middleware\AuthMiddleware;

class RefugiosController
{
    private $db;
    private $refugioModel;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->refugioModel = new Refugio();

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
     * SEGURIDAD: Método de ayuda para verificar el rol del usuario autenticado.
     * @param int $requiredRoleId El ID del rol requerido para la acción.
     * @return bool True si el usuario tiene el rol requerido, false en caso contrario (y detiene la ejecución).
     */
    private function checkUserRole(int $requiredRoleId): bool
    {
        $user = AuthMiddleware::getAuthenticatedUser();

        if (is_null($user)) {
            // Esto no debería ocurrir si AuthMiddleware::handle() se llamó antes,
            // pero es una salvaguarda.
            http_response_code(401); // No autorizado
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Usuario no autenticado.']);
            exit();
        }

        // Asegurarse de que id_rol existe y es numérico para la comparación
        if (!isset($user['id_rol']) || (int)$user['id_rol'] !== $requiredRoleId) {
            http_response_code(403); // Prohibido
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes los permisos necesarios para realizar esta acción.']);
            exit();
        }

        return true;
    }

    // Método para crear un nuevo refugio
    // SEGURIDAD: Protegido por rol de Administrador
    public function createRefugio(?array $data = null) // Ahora recibe $data como parámetro
    {
        // SEGURIDAD: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        if (is_null($data)) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar que los campos obligatorios estén presentes y no vacíos
        // Obligatorios: nombre, ciudad, estado
        if (
            !isset($data['nombre']) || empty($data['nombre']) ||
            !isset($data['ciudad']) || empty($data['ciudad']) ||
            !isset($data['estado']) || empty($data['estado'])
        ) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios para crear el refugio (nombre, ciudad, estado).'
            ]);
            return;
        }

        // Asignar datos del cuerpo de la solicitud a las propiedades del modelo
        $this->refugioModel->nombre = $data['nombre'];
        $this->refugioModel->direccion = $data['direccion'] ?? null;
        $this->refugioModel->ciudad = $data['ciudad'];
        $this->refugioModel->pais = $data['pais'] ?? null;
        $this->refugioModel->telefono = $data['telefono'] ?? null;
        $this->refugioModel->email = $data['email'] ?? null;
        // Convertir a float si se espera un tipo numérico en la DB, de lo contrario, dejar como string
        // Asegúrate de que tu columna en la DB sea de tipo DECIMAL o FLOAT para latitud/longitud
        $this->refugioModel->latitud = $data['latitud'] ?? null; 
        $this->refugioModel->longitud = $data['longitud'] ?? null;
        $this->refugioModel->estado = $data['estado'];

        try {
            if ($this->refugioModel->create()) {
                http_response_code(201); // Creado
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Refugio creado exitosamente.'
                ]);
            } else {
                http_response_code(503); // Servicio no disponible
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear el refugio.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al crear el refugio: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener todos los refugios
    // SEGURIDAD: Público (no requiere autenticación)
    public function getAllRefugios()
    {
        // No se requiere AuthMiddleware::handle() aquí, ya que es público.
        try {
            $stmt = $this->refugioModel->getAll();
            $refugios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($refugios) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Refugios obtenidos exitosamente.',
                    'data' => $refugios
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron refugios.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener refugios: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener un refugio por ID
    // SEGURIDAD: Público (no requiere autenticación)
    public function getRefugioById($id)
    {
        // No se requiere AuthMiddleware::handle() aquí, ya que es público.
        if (!isset($id) || !is_numeric($id)) { // Validar que el ID sea numérico
            http_response_code(400); // Solicitud incorrecta
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de refugio válido.'
            ]);
            return;
        }

        try {
            $refugioData = $this->refugioModel->getById((int)$id); // Convertir a int antes de pasar al modelo

            if ($refugioData) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Refugio obtenido exitosamente.',
                    'data' => $refugioData
                ]);
            } else {
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Refugio no encontrado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener refugio: ' . $e->getMessage()
            ]);
        }
    }

    // Método para actualizar un refugio existente
    // SECURITY: Protegido por rol de Administrador
    public function updateRefugio($id, ?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        if (is_null($data)) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar que el ID es numérico y que los campos obligatorios del body estén presentes y no vacíos
        if (
            !is_numeric($id) || // ID de la URL
            !isset($data['nombre']) || empty($data['nombre']) ||
            !isset($data['ciudad']) || empty($data['ciudad']) ||
            !isset($data['estado']) || empty($data['estado'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para actualizar el refugio (ID, nombre, ciudad, estado).'
            ]);
            return;
        }

        $this->refugioModel->id_refugio = (int)$id; // Convertir a int
        $this->refugioModel->nombre = $data['nombre'];
        $this->refugioModel->direccion = $data['direccion'] ?? null;
        $this->refugioModel->ciudad = $data['ciudad'];
        $this->refugioModel->pais = $data['pais'] ?? null;
        $this->refugioModel->telefono = $data['telefono'] ?? null;
        $this->refugioModel->email = $data['email'] ?? null;
        $this->refugioModel->latitud = $data['latitud'] ?? null;
        $this->refugioModel->longitud = $data['longitud'] ?? null;
        $this->refugioModel->estado = $data['estado'];


        try {
            if ($this->refugioModel->update()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Refugio actualizado exitosamente.'
                ]);
            } else {
                // Si no se afectó ninguna fila, puede ser que el refugio no exista o no se hayan hecho cambios
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró el refugio para actualizar o no se realizaron cambios.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al actualizar el refugio: ' . $e->getMessage()
            ]);
        }
    }

    // Método para eliminar un refugio
    // SECURITY: Protegido por rol de Administrador
    public function deleteRefugio($id)
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        if (!isset($id) || !is_numeric($id)) { // Validar que el ID sea numérico
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de refugio válido para eliminar.'
            ]);
            return;
        }

        $this->refugioModel->id_refugio = (int)$id; // Convertir a int

        try {
            if ($this->refugioModel->delete()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Refugio eliminado exitosamente.'
                ]);
            } else {
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró el refugio para eliminar o ya fue eliminado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar el refugio: ' . $e->getMessage()
            ]);
        }
    }
}
