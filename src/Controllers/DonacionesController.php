<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\Donacion; // Asegúrate de importar el modelo Donacion
use PDOException;
use PDO;
// SECURITY: Importar el AuthMiddleware
use App\Middleware\AuthMiddleware;

class DonacionesController
{
    private $db;
    private $donacionModel;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->donacionModel = new Donacion();

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

    // Método para crear una nueva donación
    // SECURITY: Protegido por autenticación (cualquier usuario logueado puede donar)
    public function createDonacion(?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación
        AuthMiddleware::handle(); 

        if (is_null($data)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar que los campos obligatorios estén presentes y no vacíos
        // (id_usuario, cantidad, moneda)
        if (
            !isset($data['id_usuario']) || !is_numeric($data['id_usuario']) ||
            !isset($data['cantidad']) || !is_numeric($data['cantidad']) ||
            !isset($data['moneda']) || empty($data['moneda'])
        ) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios para crear la donación (id_usuario, cantidad, moneda).'
            ]);
            return;
        }

        // Asignar datos del cuerpo de la solicitud a las propiedades del modelo
        $this->donacionModel->id_usuario = (int) $data['id_usuario'];
        // Asegurarse de que id_refugio sea null si no está presente o es vacío
        $this->donacionModel->id_refugio = isset($data['id_refugio']) && $data['id_refugio'] !== '' ? (int)$data['id_refugio'] : null;
        $this->donacionModel->cantidad = (float) $data['cantidad'];
        $this->donacionModel->moneda = $data['moneda'];
        // Si fecha_donacion no se proporciona, el modelo usará el DEFAULT de la DB
        $this->donacionModel->fecha_donacion = $data['fecha_donacion'] ?? null;
        // Asegurarse de que metodo_pago sea null si no está presente o es vacío
        $this->donacionModel->metodo_pago = isset($data['metodo_pago']) && $data['metodo_pago'] !== '' ? $data['metodo_pago'] : null;

        try {
            if ($this->donacionModel->create()) {
                http_response_code(201); // Created
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Donación creada exitosamente.'
                ]);
            } else {
                http_response_code(503); // Service Unavailable
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear la donación.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            // Si el error es por una clave foránea (id_usuario o id_refugio no existe)
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
                // Determinar cuál clave foránea falló para dar un mensaje más específico
                if (strpos($e->getMessage(), 'usuarios') !== false) {
                    $errorMessage = 'Error al crear la donación: El ID de usuario proporcionado no existe.';
                } elseif (strpos($e->getMessage(), 'refugios') !== false) {
                    $errorMessage = 'Error al crear la donación: El ID de refugio proporcionado no existe.';
                } else {
                    $errorMessage = 'Error de clave foránea al crear la donación.';
                }
                echo json_encode([
                    'status' => 'error',
                    'message' => $errorMessage,
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear la donación: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para obtener todas las donaciones
    // SECURITY: Protegido por rol de Administrador
    public function getAllDonaciones()
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        try {
            $stmt = $this->donacionModel->getAll();
            $donaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($donaciones) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Donaciones obtenidas exitosamente.',
                    'data' => $donaciones
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron donaciones.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener las donaciones: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener una donación por ID
    // SECURITY: Protegido por rol de Administrador (o el propio usuario que la creó)
    public function getDonacionById($id)
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $user = AuthMiddleware::getAuthenticatedUser(); // Obtener el usuario autenticado

        if (!isset($id) || empty($id)) {
            http_response_code(400); // Bad Request
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID de la donación.'
            ]);
            return;
        }

        try {
            $donacionData = $this->donacionModel->getById($id);

            if ($donacionData) {
                // Si el usuario no es administrador, verificar si es el dueño de la donación
                if ((int)$user['id_rol'] !== 1 && (int)$user['id_usuario'] !== (int)$donacionData['id_usuario']) {
                    http_response_code(403); // Forbidden
                    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para ver esta donación.']);
                    return;
                }

                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Donación obtenida exitosamente.',
                    'data' => $donacionData
                ]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Donación no encontrada.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener la donación: ' . $e->getMessage()
            ]);
        }
    }

    // Método para actualizar una donación existente
    // SECURITY: Protegido por rol de Administrador
    public function updateDonacion($id, ?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        if (is_null($data)) {
            http_response_code(400); // Bad Request
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        if (
            !isset($id) || empty($id) ||
            !isset($data['id_usuario']) || !is_numeric($data['id_usuario']) ||
            !isset($data['cantidad']) || !is_numeric($data['cantidad']) ||
            !isset($data['moneda']) || empty($data['moneda'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios para actualizar la donación (id, id_usuario, cantidad, moneda).'
            ]);
            return;
        }

        $this->donacionModel->id_donacion = $id;
        $this->donacionModel->id_usuario = (int) $data['id_usuario'];
        $this->donacionModel->id_refugio = isset($data['id_refugio']) && $data['id_refugio'] !== '' ? (int)$data['id_refugio'] : null;
        $this->donacionModel->cantidad = (float) $data['cantidad'];
        $this->donacionModel->moneda = $data['moneda'];
        
        // Modificación aquí: solo se asigna si el campo existe en el JSON de entrada
        $this->donacionModel->fecha_donacion = $data['fecha_donacion'] ?? null;
        $this->donacionModel->metodo_pago = $data['metodo_pago'] ?? null;


        try {
            if ($this->donacionModel->update()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Donación actualizada exitosamente.'
                ]);
            } else {
                http_response_code(503); // Service Unavailable
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo actualizar la donación. Asegúrese de que el ID exista y los datos sean válidos.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            // Si el error es por una clave foránea (id_usuario o id_refugio no existe)
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
                if (strpos($e->getMessage(), 'usuarios') !== false) {
                    $errorMessage = 'Error al actualizar la donación: El ID de usuario proporcionado no existe.';
                } elseif (strpos($e->getMessage(), 'refugios') !== false) {
                    $errorMessage = 'Error al actualizar la donación: El ID de refugio proporcionado no existe.';
                } else {
                    $errorMessage = 'Error de clave foránea al actualizar la donación.';
                }
                echo json_encode([
                    'status' => 'error',
                    'message' => $errorMessage,
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la donación: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para eliminar una donación
    // SECURITY: Protegido por rol de Administrador
    public function deleteDonacion($id)
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        if (!isset($id) || empty($id)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID de la donación para eliminar.'
            ]);
            return;
        }

        $this->donacionModel->id_donacion = $id;

        try {
            if ($this->donacionModel->delete()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Donación eliminada exitosamente.'
                ]);
            } else {
                http_response_code(404); // Not Found
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró la donación para eliminar o ya fue eliminada.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar la donación: ' . $e->getMessage()
            ]);
        }
    }
}