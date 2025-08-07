<?php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\Mascota; 
use App\Models\GaleriaMultimedia; 
use PDOException;
use PDO;
use App\Middleware\AuthMiddleware;

class MascotasController
{
    private $db;
    private $mascotaModel;
    private $galeriaMultimediaModel; 

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->mascotaModel = new Mascota();
        $this->galeriaMultimediaModel = new GaleriaMultimedia(); 

        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); 
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit(); 
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

    // Método para crear una nueva mascota
    // SEGURIDAD: Protegido por rol de Administrador
    public function createMascota(?array $data = null)
    {
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); 

        if (is_null($data)) {
            http_response_code(400); 
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        if (
            !isset($data['nombre']) || empty($data['nombre']) ||
            !isset($data['especie']) || empty($data['especie']) ||
            !isset($data['sexo']) || empty($data['sexo']) ||
            !isset($data['estado_adopcion']) || empty($data['estado_adopcion']) ||
            !isset($data['estado']) || empty($data['estado']) ||
            !isset($data['id_usuario']) || empty($data['id_usuario']) || 
            !isset($data['id_refugio']) || empty($data['id_refugio']) 
        ) {
            http_response_code(400); 
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios para crear la mascota (nombre, especie, sexo, estado_adopcion, estado, id_usuario, id_refugio).'
            ]);
            return;
        }

        $this->mascotaModel->nombre = $data['nombre'];
        $this->mascotaModel->especie = $data['especie'];
        $this->mascotaModel->raza = $data['raza'] ?? null; 
        $this->mascotaModel->edad = $data['edad'] ?? null;
        $this->mascotaModel->sexo = $data['sexo'];
        $this->mascotaModel->tamano = $data['tamano'] ?? null;
        $this->mascotaModel->descripcion = $data['descripcion'] ?? null;
        $this->mascotaModel->fecha_rescate = $data['fecha_rescate'] ?? date('Y-m-d'); 
        $this->mascotaModel->estado_adopcion = $data['estado_adopcion'];
        $this->mascotaModel->id_usuario = $data['id_usuario']; 
        $this->mascotaModel->id_refugio = $data['id_refugio']; 
        $this->mascotaModel->estado = $data['estado'];

        try {
            if ($this->mascotaModel->create()) {
                $newMascotaId = $this->mascotaModel->id_mascota; 

                if (isset($data['foto_url']) && !empty($data['foto_url'])) {
                    $this->galeriaMultimediaModel->id_mascota = $newMascotaId;
                    $this->galeriaMultimediaModel->url_archivo = $data['foto_url'];
                    $this->galeriaMultimediaModel->tipo_archivo = $data['tipo_archivo'] ?? 'imagen'; 
                    $this->galeriaMultimediaModel->descripcion = $data['descripcion_foto'] ?? null;

                    if (!$this->galeriaMultimediaModel->create()) {
                        error_log("ADVERTENCIA: Mascota creada, pero falló la creación del registro de galería multimedia para ID: " . $newMascotaId);
                    }
                }

                http_response_code(201); 
                echo json_encode([
                    'status' => 'éxito', 
                    'message' => 'Mascota creada exitosamente.',
                    'id_mascota' => $newMascotaId 
                ]);
            } else {
                http_response_code(503); 
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear la mascota.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); 
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear la mascota: Verifique que los IDs de usuario y refugio existan.',
                    'details' => $e->getMessage() 
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al crear la mascota: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para obtener todas las mascotas
    // SEGURIDAD: Ahora es público (sin AuthMiddleware::handle())
    public function getAllMascotas()
    {
        try {
            $stmt = $this->mascotaModel->getAll();
            $mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($mascotas) {
                echo json_encode([
                    'status' => 'éxito',
                    'message' => 'Mascotas obtenidas exitosamente.',
                    'data' => $mascotas
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron mascotas.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); 
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener las mascotas: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener una mascota por ID (ahora incluye un array de fotos)
    // SEGURIDAD: Protegido por autenticación (cualquier usuario logueado puede ver)
    public function getMascotaById($id)
    {
        AuthMiddleware::handle(); 
        
        if (!isset($id) || empty($id)) {
            http_response_code(400); 
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID de la mascota.'
            ]);
            return;
        }

        try {
            // El modelo ahora devuelve un PDOStatement que puede contener múltiples filas
            $stmt = $this->mascotaModel->getById($id);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                http_response_code(404); 
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Mascota no encontrada.'
                ]);
                return;
            }

            $mascotaData = [];
            $fotos = [];

            foreach ($rows as $index => $row) {
                // La primera fila contiene los datos principales de la mascota
                if ($index === 0) {
                    $mascotaData = [
                        'id_mascota' => $row['id_mascota'],
                        'nombre' => $row['nombre'],
                        'especie' => $row['especie'],
                        'raza' => $row['raza'],
                        'edad' => $row['edad'],
                        'sexo' => $row['sexo'],
                        'tamano' => $row['tamano'],
                        'descripcion' => $row['descripcion'],
                        'fecha_rescate' => $row['fecha_rescate'],
                        'estado_adopcion' => $row['estado_adopcion'],
                        'id_refugio' => $row['id_refugio'],
                        'estado' => $row['estado'],
                        'id_usuario' => $row['id_usuario'],
                        'fotos' => [] // Inicializar el array de fotos
                    ];
                }

                // Si hay datos de galería multimedia en la fila actual, añadirlos al array de fotos
                // Asegurarse de que id_multimedia no sea nulo, ya que un LEFT JOIN puede devolver nulos
                if (!is_null($row['id_multimedia'])) {
                    $fotos[] = [
                        'id_multimedia' => $row['id_multimedia'],
                        'tipo_archivo' => $row['tipo_archivo'],
                        'url_archivo' => $row['url_archivo'],
                        'descripcion' => $row['foto_descripcion'],
                        'fecha_subida' => $row['foto_fecha_subida']
                    ];
                }
            }

            // Asignar el array de fotos a la mascota
            $mascotaData['fotos'] = $fotos;
            // Para la vista de lista, si solo se necesita una foto, toma la primera o null
            $mascotaData['foto_url'] = !empty($fotos) ? $fotos[0]['url_archivo'] : null;


            http_response_code(200); 
            echo json_encode([
                'status' => 'éxito',
                'message' => 'Mascota obtenida exitosamente.',
                'data' => $mascotaData 
            ]);

        } catch (PDOException $e) {
            http_response_code(500); 
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener la mascota: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene las mascotas adoptadas por un usuario específico.
     * Endpoint: GET /mascotas/adoptadas/usuario/{id_usuario}
     * SEGURIDAD: Protegido por autenticación. Solo el propio usuario o un administrador puede ver sus mascotas adoptadas.
     * @param int $userId El ID del usuario.
     */
    public function getAdoptadasByUsuario($userId) {
        AuthMiddleware::handle(); // Asegurarse de que el usuario esté autenticado
        $authenticatedUser = AuthMiddleware::getAuthenticatedUser();

        // Verificar si el usuario autenticado es el mismo que el ID solicitado o si es un administrador
        if ((int)$authenticatedUser['id_usuario'] !== (int)$userId && (int)$authenticatedUser['id_rol'] !== 1) {
            http_response_code(403); // Prohibido
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. No tienes permisos para ver las mascotas adoptadas de este usuario.']);
            exit();
        }

        try {
            // Prepara la consulta SQL para obtener mascotas que han sido adoptadas por un usuario
            // AHORA USANDO EL NOMBRE DE TABLA CORRECTO: 'solicitudesadopcion' y 'galeriamultimedia'
            $stmt = $this->db->prepare("
                SELECT
                    m.id_mascota,
                    m.nombre,
                    m.especie,
                    m.raza,
                    m.edad,
                    m.sexo,
                    m.tamano,
                    m.descripcion,
                    m.fecha_rescate,
                    m.estado_adopcion,
                    m.id_refugio,
                    m.estado,
                    sa.fecha_aprobacion_rechazo AS fecha_adopcion,
                    (SELECT gm.url_archivo FROM galeriamultimedia gm WHERE gm.id_mascota = m.id_mascota ORDER BY gm.id_multimedia LIMIT 1) AS foto_principal_url
                FROM
                    mascotas m
                JOIN
                    solicitudesadopcion sa ON m.id_mascota = sa.id_mascota
                WHERE sa.id_usuario = :userId AND sa.estado_solicitud = 'Aprobada'
                ORDER BY sa.fecha_aprobacion_rechazo DESC
            ");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $mascotasAdoptadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($mascotasAdoptadas) {
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Mascotas adoptadas obtenidas exitosamente.',
                    'data' => $mascotasAdoptadas
                ]);
            } else {
                http_response_code(200); // OK, pero sin datos
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron mascotas adoptadas para este usuario.',
                    'data' => [] // Devolver un array vacío para indicar que no hay datos
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener mascotas adoptadas por usuario: ' . $e->getMessage()
            ]);
        }
    }


    // Método para actualizar una mascota existente
    // SEGURIDAD: Protegido por rol de Administrador
    public function updateMascota($id, ?array $data = null) 
    {
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); 

        if (is_null($data)) {
            http_response_code(400); 
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        if (
            !isset($id) || empty($id) ||
            !isset($data['nombre']) || empty($data['nombre']) ||
            !isset($data['especie']) || empty($data['especie']) ||
            !isset($data['sexo']) || empty($data['sexo']) ||
            !isset($data['estado_adopcion']) || empty($data['estado_adopcion']) ||
            !isset($data['estado']) || empty($data['estado']) ||
            !isset($data['id_usuario']) || empty($data['id_usuario']) || 
            !isset($data['id_refugio']) || empty($data['id_refugio']) 
        ) {
            http_response_code(400); 
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios para actualizar la mascota (id, nombre, especie, sexo, estado_adopcion, estado, id_usuario, id_refugio).'
            ]);
            return;
        }

        $this->mascotaModel->id_mascota = $id;
        $this->mascotaModel->nombre = $data['nombre'];
        $this->mascotaModel->especie = $data['especie'];
        $this->mascotaModel->raza = $data['raza'] ?? null;
        $this->mascotaModel->edad = $data['edad'] ?? null;
        $this->mascotaModel->sexo = $data['sexo'];
        $this->mascotaModel->tamano = $data['tamano'] ?? null;
        $this->mascotaModel->descripcion = $data['descripcion'] ?? null;
        $this->mascotaModel->fecha_rescate = $data['fecha_rescate'] ?? null;
        $this->mascotaModel->estado_adopcion = $data['estado_adopcion'];
        
        $this->mascotaModel->id_usuario = $data['id_usuario']; 
        
        $this->mascotaModel->id_refugio = $data['id_refugio']; 
        $this->mascotaModel->estado = $data['estado'];

        try {
            if ($this->mascotaModel->update()) {
                error_log("DEBUG: Mascota ID " . $id . " actualizada en la tabla mascotas.");

                // Lógica para actualizar la foto de la mascota
                if (isset($data['foto_url']) && !empty($data['foto_url'])) {
                    error_log("DEBUG: Intentando actualizar foto para mascota ID: " . $id . ". Nueva URL: " . $data['foto_url']);
                    // 1. Eliminar todas las fotos existentes para esta mascota
                    try {
                        // AHORA USANDO EL NOMBRE DE TABLA CORRECTO: 'galeriamultimedia'
                        $deleted = $this->galeriaMultimediaModel->deleteByMascotaId((int)$id);
                        if ($deleted) {
                            error_log("DEBUG: Fotos existentes para mascota ID " . $id . " eliminadas exitosamente (" . $deleted . " filas afectadas).");
                        } else {
                            error_log("DEBUG: No se encontraron fotos existentes para mascota ID " . $id . " para eliminar.");
                        }
                    } catch (PDOException $e) {
                        error_log("ADVERTENCIA: Falló la eliminación de fotos existentes para mascota ID " . $id . ": " . $e->getMessage());
                        // No abortar la operación si falla la eliminación de fotos antiguas, solo loguear.
                    }

                    // 2. Crear el nuevo registro de galería multimedia
                    $this->galeriaMultimediaModel->id_mascota = (int)$id; 
                    $this->galeriaMultimediaModel->url_archivo = $data['foto_url'];
                    $this->galeriaMultimediaModel->tipo_archivo = $data['tipo_archivo'] ?? 'imagen';
                    $this->galeriaMultimediaModel->descripcion = $data['descripcion_foto'] ?? null;

                    if ($this->galeriaMultimediaModel->create()) {
                        error_log("DEBUG: Nuevo registro de galería multimedia creado para mascota ID: " . $id);
                    } else {
                        error_log("ADVERTENCIA: Mascota actualizada, pero falló la creación del nuevo registro de galería multimedia para ID: " . $id . ". Verifique logs de GaleriaMultimedia::create().");
                    }
                } else {
                    // Si no se proporciona una foto_url en la actualización, puedes decidir si quieres:
                    // a) No hacer nada con la galería (mantener las fotos existentes).
                    // b) Eliminar todas las fotos existentes para esa mascota.
                    // Por ahora, no haremos nada si no se envía una nueva URL.
                    error_log("DEBUG: No se proporcionó 'foto_url' en la actualización para mascota ID: " . $id . ". No se modificó la galería multimedia.");
                }

                http_response_code(200); 
                echo json_encode([
                    'status' => 'éxito',
                    'message' => 'Mascota actualizada exitosamente.'
                ]);
            } else {
                http_response_code(503); 
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo actualizar la mascota. Asegúrese de que el ID exista y los datos sean válidos.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); 
            if ($e->getCode() == '23000' && strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la mascota: Verifique que los IDs de usuario y refugio existan.',
                    'details' => $e->getMessage() 
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al actualizar la mascota: ' . $e->getMessage()
                ]);
            }
        }
    }

    // Método para eliminar una mascota
    // SEGURIDAD: Protegido por rol de Administrador
    public function deleteMascota($id)
    {
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); 

        if (!isset($id) || empty($id)) {
            http_response_code(400); 
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó el ID de la mascota para eliminar.'
            ]);
            return;
        }

        $this->mascotaModel->id_mascota = $id;

        try {
            if ($this->mascotaModel->delete()) { // Este método ahora realiza la eliminación física y de multimedia
                http_response_code(200); 
                echo json_encode([
                    'status' => 'éxito',
                    'message' => 'Mascota eliminada exitosamente.'
                ]);
            } else {
                http_response_code(404); 
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró la mascota para eliminar o ya fue eliminada.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500); 
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar la mascota: ' . $e->getMessage()
            ]);
        }
    }
}
