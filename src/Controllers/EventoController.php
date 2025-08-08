<?php
// src/Controllers/EventoController.php

namespace App\Controllers;

use App\DB\Connection;
use App\Models\Evento; // Asegúrate de importar el modelo Evento
use PDOException;
use PDO;
// SEGURIDAD: Importar el AuthMiddleware
use App\Middleware\AuthMiddleware;

class EventoController
{
    private $db;
    private $eventoModel;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
        $this->eventoModel = new Evento();

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

    // Método para crear un nuevo evento
    // SEGURIDAD: Protegido por rol de Administrador
    public function createEvento(?array $data = null) // Ahora recibe $data como parámetro
    {
        // SEGURIDAD: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        if (is_null($data)) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode(['status' => 'error', 'message' => 'JSON inválido o ausente en el cuerpo de la petición.']);
            return;
        }

        // Validar campos obligatorios
        if (
            !isset($data['nombre_evento']) || empty($data['nombre_evento']) ||
            !isset($data['fecha_hora']) || empty($data['fecha_hora']) ||
            !isset($data['ubicacion']) || empty($data['ubicacion']) ||
            !isset($data['estado']) || empty($data['estado'])
        ) {
            http_response_code(400); // Solicitud incorrecta
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para crear el evento (nombre_evento, fecha_hora, ubicacion, estado).'
            ]);
            return;
        }

        // Asignar datos a las propiedades del modelo
        $this->eventoModel->nombre_evento = $data['nombre_evento'];
        $this->eventoModel->descripcion = $data['descripcion'] ?? null;
        $this->eventoModel->fecha_hora = $data['fecha_hora'];
        $this->eventoModel->ubicacion = $data['ubicacion'];
        $this->eventoModel->organizador = $data['organizador'] ?? null;
        $this->eventoModel->cupo_maximo = $data['cupo_maximo'] ?? null;
        $this->eventoModel->estado = $data['estado'];

        try {
            if ($this->eventoModel->create()) {
                http_response_code(201); // Creado
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Evento creado exitosamente.'
                ]);
            } else {
                http_response_code(503); // Servicio no disponible
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No se pudo crear el evento.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al crear el evento: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener todos los eventos
    // SEGURIDAD: Público (no requiere autenticación)
    public function getAllEventos()
    {
        // No se requiere AuthMiddleware::handle() ni checkUserRole() para esta ruta pública
        try {
            $stmt = $this->eventoModel->getAll();
            $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($eventos) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Eventos obtenidos exitosamente.',
                    'data' => $eventos
                ]);
            } else {
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontraron eventos.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener eventos: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener un evento por ID
    // SEGURIDAD: Público (no requiere autenticación)
    public function getEventoById($id)
    {
        // No se requiere AuthMiddleware::handle() ni checkUserRole() para esta ruta pública
        if (!isset($id) || !is_numeric($id)) { // Validar que el ID sea numérico
            http_response_code(400); // Solicitud incorrecta
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de evento válido.'
            ]);
            return;
        }

        try {
            $eventoData = $this->eventoModel->getById((int)$id); // Convertir a int antes de pasar al modelo

            if ($eventoData) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Evento obtenido exitosamente.',
                    'data' => $eventoData
                ]);
            } else {
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'Evento no encontrado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al obtener evento: ' . $e->getMessage()
            ]);
        }
    }

    // Método para actualizar un evento existente
    // SECURITY: Protegido por rol de Administrador
    public function updateEvento($id, ?array $data = null) // Ahora recibe $data como parámetro
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
            !is_numeric($id) || // ID de la URL
            !isset($data['nombre_evento']) || empty($data['nombre_evento']) ||
            !isset($data['fecha_hora']) || empty($data['fecha_hora']) ||
            !isset($data['ubicacion']) || empty($data['ubicacion']) ||
            !isset($data['estado']) || empty($data['estado'])
        ) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Faltan datos obligatorios o no son válidos para actualizar el evento (ID, nombre_evento, fecha_hora, ubicacion, estado).'
            ]);
            return;
        }

        $this->eventoModel->id_evento = (int) $id;
        $this->eventoModel->nombre_evento = $data['nombre_evento'];
        $this->eventoModel->descripcion = $data['descripcion'] ?? null;
        $this->eventoModel->fecha_hora = $data['fecha_hora'];
        $this->eventoModel->ubicacion = $data['ubicacion'];
        $this->eventoModel->organizador = $data['organizador'] ?? null;
        $this->eventoModel->cupo_maximo = $data['cupo_maximo'] ?? null;
        $this->eventoModel->estado = $data['estado'];

        try {
            if ($this->eventoModel->update()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Evento actualizado exitosamente.'
                ]);
            } else {
                // Si el modelo devuelve false, significa que la consulta se ejecutó, pero no hubo filas afectadas.
                // Esto ocurre si el ID no existe O si los datos enviados son idénticos a los existentes.
                // Cambiamos el código HTTP a 200 (OK) y el status a 'info' para indicar que no es un error fatal.
                http_response_code(200); // Cambiado de 404 a 200
                echo json_encode([
                    'status' => 'info', // Cambiado de 'error' a 'info'
                    'message' => 'No se realizaron cambios en el evento o el evento no fue encontrado.' // Mensaje más claro
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al actualizar el evento: ' . $e->getMessage()
            ]);
        }
    }

    // Método para eliminar un evento
    // SECURITY: Protegido por rol de Administrador
    public function deleteEvento($id)
    {
        // SECURITY: Verificar autenticación y autorización
        AuthMiddleware::handle(); 
        $this->checkUserRole(1); // Solo Administradores (id_rol = 1)

        if (!isset($id) || !is_numeric($id)) { // Validar que el ID sea numérico
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'No se proporcionó un ID de evento válido para eliminar.'
            ]);
            return;
        }

        $this->eventoModel->id_evento = (int) $id;

        try {
            if ($this->eventoModel->delete()) {
                http_response_code(200); // OK
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Evento eliminado exitosamente.'
                ]);
            } else {
                http_response_code(404); // No encontrado
                echo json_encode([
                    'status' => 'info',
                    'message' => 'No se encontró el evento para eliminar o ya fue eliminado.'
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al eliminar el evento: ' . $e->getMessage()
            ]);
        }
    }
}
