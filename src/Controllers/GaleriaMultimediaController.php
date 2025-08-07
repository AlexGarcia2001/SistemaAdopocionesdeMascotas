<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\GaleriaMultimedia; // Asegúrate de importar el modelo
use PDOException;
use PDO;
// SECURITY: Importar el AuthMiddleware
use App\Middleware\AuthMiddleware;

class GaleriaMultimediaController
{
    private $db;
    private $galeriaModel;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->galeriaModel = new GaleriaMultimedia();

        // Configuración de encabezados CORS y Content-Type para todas las respuestas de este controlador
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); // Permite acceso desde cualquier origen (para desarrollo)
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        // Manejo de peticiones OPTIONS (preflight requests de CORS)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200); // OK
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

    // Método para crear un nuevo registro de galería multimedia
    // SECURITY: Protegido por rol de Administrador
    public function createGaleriaMultimedia(?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        if (is_null($data)) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // --- INICIO DE DEPURACIÓN ---
        error_log("DEBUG-GALERIA-CREATE: Datos recibidos: " . json_encode($data));
        error_log("DEBUG-GALERIA-CREATE: id_mascota: " . ($data['id_mascota'] ?? 'NO_SET'));
        error_log("DEBUG-GALERIA-CREATE: tipo_archivo: " . ($data['tipo_archivo'] ?? 'NO_SET'));
        error_log("DEBUG-GALERIA-CREATE: url_archivo: " . ($data['url_archivo'] ?? 'NO_SET'));
        // --- FIN DE DEPURACIÓN ---

        // Validar campos obligatorios
        if (
            !isset($data['id_mascota']) || !is_numeric($data['id_mascota']) ||
            !isset($data['tipo_archivo']) || !in_array($data['tipo_archivo'], ['imagen', 'video']) ||
            !isset($data['url_archivo']) || empty($data['url_archivo'])
        ) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para crear el registro multimedia (id_mascota, tipo_archivo, url_archivo). tipo_archivo debe ser "imagen" o "video".'
            ]);
            return;
        }

        // Asignar datos a las propiedades del modelo
        $this->galeriaModel->id_mascota = (int) $data['id_mascota'];
        $this->galeriaModel->tipo_archivo = $data['tipo_archivo'];
        $this->galeriaModel->url_archivo = $data['url_archivo'];
        $this->galeriaModel->descripcion = $data['descripcion'] ?? null;
        // fecha_subida no se asigna aquí, se asume que la DB la genera automáticamente

        try {
            if ($this->galeriaModel->create()) {
                http_response_code(201); // Creado
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Registro multimedia creado exitosamente.'
                ]);
            } else {
                http_response_code(503); // Servicio no disponible
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear el registro multimedia.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); // Error interno del servidor
            if ($e->getCode() == '23000') { // Violación de integridad (ej. clave foránea)
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear el registro multimedia. Asegúrese de que el ID de mascota exista.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear el registro multimedia: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para obtener todos los registros de galería multimedia
    // SECURITY: Público (no requiere autenticación)
    public function getAllGaleriaMultimedia()
    {
        // No se requiere AuthMiddleware::handle() ni checkUserRole() para esta ruta pública
        try {
            $stmt = $this->galeriaModel->getAll();
            $media = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($media) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Registros multimedia obtenidos exitosamente.',
                    'data' => $media
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron registros multimedia.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); // Error interno del servidor
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener registros multimedia: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Método para obtener registros de galería multimedia por ID de Mascota.
     * SECURITY: Público (no requiere autenticación)
     * @param int $mascotaId El ID de la mascota para la cual se buscan los registros multimedia.
     */
    public function getGaleriaMultimediaByMascotaId($mascotaId)
    {
        // No se requiere AuthMiddleware::handle() ni checkUserRole() para esta ruta pública
        if (!isset($mascotaId) || !is_numeric($mascotaId)) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de mascota válido.'
            ]);
            return;
        }

        try {
            // Asumiendo que tu modelo GaleriaMultimedia tiene un método getByMascotaId
            // Si no lo tiene, necesitarás agregarlo a App\Models\GaleriaMultimedia.php
            $mediaData = $this->galeriaModel->getByMascotaId($mascotaId);

            if ($mediaData) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Registros multimedia obtenidos exitosamente para la mascota.',
                    'data' => $mediaData
                ]);
            } else {
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron registros multimedia para esta mascota.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); // Error interno del servidor
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener registros multimedia por ID de mascota: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener un registro de galería multimedia por ID
    // SECURITY: Público (no requiere autenticación)
    public function getGaleriaMultimediaById($id)
    {
        // No se requiere AuthMiddleware::handle() ni checkUserRole() para esta ruta pública
        if (!isset($id) || empty($id)) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID de registro multimedia.'
            ]);
            return;
        }

        try {
            $mediaData = $this->galeriaModel->getById($id);

            if ($mediaData) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Registro multimedia obtenido exitosamente.',
                    'data' => $mediaData
                ]);
            } else {
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Registro multimedia no encontrado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); // Error interno del servidor
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener registro multimedia: ' . $e->getMessage()
            ]);
        }
    }

    // Método para actualizar un registro de galería multimedia existente
    // SECURITY: Protegido por rol de Administrador
    public function updateGaleriaMultimedia($id, ?array $data = null) // Ahora recibe $data como parámetro
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        if (is_null($data)) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar campos obligatorios para la actualización
        if (
            !isset($id) || !is_numeric($id) ||
            !isset($data['id_mascota']) || !is_numeric($data['id_mascota']) ||
            !isset($data['tipo_archivo']) || !in_array($data['tipo_archivo'], ['imagen', 'video']) ||
            !isset($data['url_archivo']) || empty($data['url_archivo'])
        ) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para actualizar el registro multimedia (id, id_mascota, tipo_archivo, url_archivo). tipo_archivo debe ser "imagen" o "video".'
            ]);
            return;
        }

        $this->galeriaModel->id_multimedia = (int) $id;
        $this->galeriaModel->id_mascota = (int) $data['id_mascota'];
        $this->galeriaModel->tipo_archivo = $data['tipo_archivo'];
        $this->galeriaModel->url_archivo = $data['url_archivo'];
        $this->galeriaModel->descripcion = $data['descripcion'] ?? null;

        try {
            if ($this->galeriaModel->update()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Registro multimedia actualizado exitosamente.'
                ]);
            } else {
                http_response_code(503); // Servicio no disponible
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo actualizar el registro multimedia. Asegúrese de que el ID exista y los datos sean válidos.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); // Error interno del servidor
            if ($e->getCode() == '23000') { // Violación de integridad (ej. clave foránea)
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar el registro multimedia. Asegúrese de que el ID de mascota exista.',
                    'details' => $e->getMessage()
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar el registro multimedia: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para eliminar un registro de galería multimedia
    // SECURITY: Protegido por rol de Administrador
    public function deleteGaleriaMultimedia($id)
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        if (!isset($id) || !is_numeric($id)) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de registro multimedia válido para eliminar.'
            ]);
            return;
        }

        $this->galeriaModel->id_multimedia = (int) $id;

        try {
            if ($this->galeriaModel->delete()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Registro multimedia eliminado exitosamente.'
                ]);
            } else {
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró el registro multimedia para eliminar o ya fue eliminado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); // Error interno del servidor
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar el registro multimedia: ' . $e->getMessage()
            ]);
        }
    }
}
