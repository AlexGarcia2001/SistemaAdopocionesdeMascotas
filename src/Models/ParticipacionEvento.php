<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class ParticipacionEvento
{
    private $db;
    private $table_name = "participacionevento";

    // Propiedades de la clase (columnas de la tabla participacionevento)
    public $id_participacion;
    public $id_usuario;
    public $id_evento;
    public $fecha_registro; // Se espera que venga del controlador

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para registrar la participación de un usuario en un evento
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                    (id_usuario, id_evento, fecha_registro)
                    VALUES
                    (:id_usuario, :id_evento, :fecha_registro)";

        $stmt = $this->db->prepare($query);

        // Vincular datos (los IDs ya vienen como enteros del controlador)
        $stmt->bindParam(":id_usuario", $this->id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_evento", $this->id_evento, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_registro", $this->fecha_registro); // fecha_registro es un string

        try {
            if ($stmt->execute()) {
                // Opcional: Obtener el ID del último insertado si es necesario
                // $this->id_participacion = $this->db->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            // Error Code 23000 es para violaciones de integridad, incluyendo duplicados
            if ($e->getCode() == '23000') {
                throw new PDOException("El usuario ya está registrado en este evento.", 23000);
            }
            throw $e; // Re-throw para ser capturado en el controlador
        }
        return false;
    }

    // Método para obtener todas las participaciones (con info de usuario y evento)
    public function getAll()
    {
        $query = "SELECT pe.id_participacion, pe.id_usuario, pe.id_evento, pe.fecha_registro,
                            u.nombre_usuario as nombre_usuario, u.apellido as apellido_usuario,
                            e.nombre_evento, e.fecha_hora as fecha_hora_evento, e.ubicacion as ubicacion_evento
                    FROM " . $this->table_name . " pe
                    LEFT JOIN usuarios u ON pe.id_usuario = u.id_usuario
                    LEFT JOIN eventos e ON pe.id_evento = e.id_evento
                    ORDER BY pe.fecha_registro DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Método para obtener participaciones de eventos por ID de usuario.
     * @param int $userId El ID del usuario cuyas participaciones se desean obtener.
     * @return PDOStatement Retorna un objeto PDOStatement con los resultados.
     */
    public function getParticipationsByUserId(int $userId)
    {
        $query = "SELECT pe.id_participacion, pe.id_usuario, pe.id_evento, pe.fecha_registro,
                            u.nombre_usuario as nombre_usuario, u.apellido as apellido_usuario,
                            e.nombre_evento, e.fecha_hora as fecha_hora_evento, e.ubicacion as ubicacion_evento
                    FROM " . $this->table_name . " pe
                    LEFT JOIN usuarios u ON pe.id_usuario = u.id_usuario
                    LEFT JOIN eventos e ON pe.id_evento = e.id_evento
                    WHERE pe.id_usuario = :id_usuario
                    ORDER BY pe.fecha_registro DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_usuario", $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener una sola participación por ID
    public function getById($id)
    {
        $query = "SELECT pe.id_participacion, pe.id_usuario, pe.id_evento, pe.fecha_registro,
                            u.nombre_usuario as nombre_usuario, u.apellido as apellido_usuario,
                            e.nombre_evento, e.fecha_hora as fecha_hora_evento, e.ubicacion as ubicacion_evento
                    FROM " . $this->table_name . " pe
                    LEFT JOIN usuarios u ON pe.id_usuario = u.id_usuario
                    LEFT JOIN eventos e ON pe.id_evento = e.id_evento
                    WHERE pe.id_participacion = :id_participacion LIMIT 0,1"; // Usar named parameter

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_participacion", $id, PDO::PARAM_INT); // Vincular con named parameter
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    // Método para actualizar una participación existente
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                    SET
                        id_usuario = :id_usuario,
                        id_evento = :id_evento,
                        fecha_registro = :fecha_registro
                    WHERE id_participacion = :id_participacion";

        $stmt = $this->db->prepare($query);

        // Vincular datos (los IDs ya vienen como enteros del controlador)
        $stmt->bindParam(":id_usuario", $this->id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_evento", $this->id_evento, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_registro", $this->fecha_registro);
        $stmt->bindParam(":id_participacion", $this->id_participacion, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0; // Retorna true si se afectó alguna fila
            }
        } catch (PDOException $e) {
            // Error Code 23000 es para violaciones de integridad
            throw $e; // Re-throw para ser capturado en el controlador
        }
        return false;
    }

    // Método para eliminar una participación
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_participacion = :id_participacion";
        $stmt = $this->db->prepare($query);

        // Vincular ID (ya viene como entero del controlador)
        $stmt->bindParam(':id_participacion', $this->id_participacion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    // Método para verificar si una participación ya existe (útil para la creación)
    public function exists($id_usuario, $id_evento)
    {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " WHERE id_usuario = :id_usuario AND id_evento = :id_evento";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_evento", $id_evento, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}