<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\Role;
use PDO;
use PDOException;
// SECURITY: Importar el AuthMiddleware
use App\Middleware\AuthMiddleware;

class RolesController
{
    private $db;
    private $roleModel;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->roleModel = new Role();

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

    // Método para obtener todos los roles (con nombre de rol)
    // SECURITY: Protegido por rol de Administrador
    public function getAllRoles()
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        try {
            // Usar el modelo para obtener todos los roles
            $stmt = $this->roleModel->getAll();
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($roles) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Roles obtenidos exitosamente.',
                    'data' => $roles
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron roles.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); // Internal Server Error
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener los roles: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener un rol por su ID
    // SECURITY: Protegido por rol de Administrador
    public function getRoleById($id)
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        try {
            $this->roleModel->id_rol = $id; // Asignar el ID al modelo
            if ($this->roleModel->getById($id)) { // Usar el método del modelo
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Rol obtenido exitosamente.',
                    'data' => [
                        'id_rol' => $this->roleModel->id_rol,
                        'nombre_rol' => $this->roleModel->nombre_rol,
                        'descripcion' => $this->roleModel->descripcion,
                        'estado' => $this->roleModel->estado
                    ]
                ]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Rol no encontrado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener el rol: ' . $e->getMessage()
            ]);
        }
    }

    // Método para crear un nuevo rol
    // SECURITY: Protegido por rol de Administrador
    public function createRole(?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        // La validación de $data nulo es crucial aquí
        if (is_null($data)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        if (
            !isset($data['nombre_rol']) || empty($data['nombre_rol']) ||
            !isset($data['descripcion']) || empty($data['descripcion']) ||
            !isset($data['estado']) || empty($data['estado'])
        ) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionaron todos los datos necesarios (nombre_rol, descripcion, estado).'
            ]);
            return;
        }

        // Asignar los datos del cuerpo de la solicitud a las propiedades del modelo
        $this->roleModel->nombre_rol = $data['nombre_rol'];
        $this->roleModel->descripcion = $data['descripcion'];
        $this->roleModel->estado = $data['estado'];

        try {
            if ($this->roleModel->create()) {
                http_response_code(201); // Created
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Rol creado exitosamente.'
                ]);
            } else {
                http_response_code(503); // Service Unavailable
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear el rol.'
                ]);
            }
        } catch (PDOException $e) {
            // Error de clave duplicada (UNIQUE constraint)
            if ($e->getCode() == '23000') { // Código SQLSTATE para violación de integridad
                http_response_code(409); // Conflict
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El nombre de rol ya existe. Por favor, elija un nombre diferente.'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear el rol: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para actualizar un rol existente
    // SECURITY: Protegido por rol de Administrador
    public function updateRole($id, ?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        // Validar que el ID de la URL y los datos del cuerpo son correctos
        if (
            !isset($id) || empty($id) ||
            is_null($data) || // Validar que $data no sea nulo
            !isset($data['nombre_rol']) || empty($data['nombre_rol']) ||
            !isset($data['descripcion']) || empty($data['descripcion']) ||
            !isset($data['estado']) || empty($data['estado'])
        ) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionaron todos los datos necesarios (ID en URL, nombre_rol, descripcion, estado en cuerpo).'
            ]);
            return;
        }

        // Asignar los datos del cuerpo y el ID de la URL a las propiedades del modelo
        $this->roleModel->id_rol = $id; // El ID para actualizar
        $this->roleModel->nombre_rol = $data['nombre_rol'];
        $this->roleModel->descripcion = $data['descripcion'];
        $this->roleModel->estado = $data['estado'];

        try {
            if ($this->roleModel->update()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Rol actualizado exitosamente.'
                ]);
            } else {
                http_response_code(503); // Service Unavailable
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo actualizar el rol. Asegúrese de que el ID exista y los datos sean válidos.'
                ]);
            }
        } catch (PDOException $e) {
            // Error de clave duplicada (UNIQUE constraint)
            if ($e->getCode() == '23000') { // Código SQLSTATE para violación de integridad
                http_response_code(409); // Conflict
                echo json_encode([
                    'status' => 'error',
                    'message' => 'El nombre de rol ya existe. Por favor, elija un nombre diferente.'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar el rol: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para eliminar un rol
    // SECURITY: Protegido por rol de Administrador
    public function deleteRole($id)
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); // Primero autentica
        $this->checkUserRole(1); // Luego autoriza (solo Administradores, id_rol = 1)

        if (!isset($id) || empty($id)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID del rol para eliminar.'
            ]);
            return;
        }

        $this->roleModel->id_rol = $id; // Asignar el ID al modelo

        try {
            if ($this->roleModel->delete()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Rol eliminado exitosamente.'
                ]);
            } else {
                http_response_code(404); // Not Found (si no se encontró el rol para eliminar)
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró el rol para eliminar o ya fue eliminado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar el rol: ' . $e->getMessage()
            ]);
        }
    }
}