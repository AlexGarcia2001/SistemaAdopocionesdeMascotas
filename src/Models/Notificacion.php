<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class Notificacion
{
    private $db;
    private $table_name = "notificaciones";

    public $id_notificacion;
    public $id_usuario;
    public $mensaje;
    public $fecha_hora;
    public $leida;
    public $tipo_notificacion;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                    (id_usuario, mensaje, leida, tipo_notificacion)
                    VALUES
                    (:id_usuario, :mensaje, :leida, :tipo_notificacion)";

        $stmt = $this->db->prepare($query);

        $this->mensaje = htmlspecialchars(strip_tags($this->mensaje));

        $stmt->bindParam(":id_usuario", $this->id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":mensaje", $this->mensaje);
        $stmt->bindParam(":leida", $this->leida, PDO::PARAM_INT);
        $stmt->bindParam(":tipo_notificacion", $this->tipo_notificacion, PDO::PARAM_STR);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    public function getAll()
    {
        $query = "SELECT n.id_notificacion, n.id_usuario, n.mensaje, n.fecha_hora,
                            n.leida, n.tipo_notificacion,
                            u.nombre_usuario as nombre_usuario_destinatario, u.apellido as apellido_usuario_destinatario
                    FROM " . $this->table_name . " n
                    LEFT JOIN usuarios u ON n.id_usuario = u.id_usuario
                    ORDER BY n.fecha_hora DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getNotificationsByUserId(int $userId)
    {
        $query = "SELECT n.id_notificacion, n.id_usuario, n.mensaje, n.fecha_hora,
                            n.leida, n.tipo_notificacion,
                            u.nombre_usuario as nombre_usuario_destinatario, u.apellido as apellido_usuario_destinatario
                    FROM " . $this->table_name . " n
                    LEFT JOIN usuarios u ON n.id_usuario = u.id_usuario
                    WHERE n.id_usuario = :id_usuario
                    ORDER BY n.fecha_hora DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_usuario", $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getById($id)
    {
        $query = "SELECT n.id_notificacion, n.id_usuario, n.mensaje, n.fecha_hora,
                            n.leida, n.tipo_notificacion,
                            u.nombre_usuario as nombre_usuario_destinatario, u.apellido as apellido_usuario_destinatario
                    FROM " . $this->table_name . " n
                    LEFT JOIN usuarios u ON n.id_usuario = u.id_usuario
                    WHERE n.id_notificacion = :id_notificacion LIMIT 0,1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_notificacion", $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                    SET
                        id_usuario = :id_usuario,
                        mensaje = :mensaje,
                        leida = :leida,
                        tipo_notificacion = :tipo_notificacion
                    WHERE
                        id_notificacion = :id_notificacion";

        $stmt = $this->db->prepare($query);

        $this->mensaje = htmlspecialchars(strip_tags($this->mensaje));

        $stmt->bindParam(":id_usuario", $this->id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":mensaje", $this->mensaje);
        $stmt->bindParam(":leida", $this->leida, PDO::PARAM_INT);
        $stmt->bindParam(":tipo_notificacion", $this->tipo_notificacion, PDO::PARAM_STR);
        $stmt->bindParam(':id_notificacion', $this->id_notificacion, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_notificacion = :id_notificacion";
        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':id_notificacion', $this->id_notificacion, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }
    
    public function markAsRead($id)
    {
        $query = "UPDATE " . $this->table_name . " SET leida = 1 WHERE id_notificacion = :id_notificacion AND leida = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_notificacion", $id, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    /**
     * Marca todas las notificaciones no leídas de un usuario como leídas.
     * @param int $userId El ID del usuario.
     * @return bool True si se actualizó al menos una notificación, false en caso contrario.
     */
    public function markAllAsReadByUserId(int $userId): bool
    {
        $query = "UPDATE " . $this->table_name . " SET leida = 1 WHERE id_usuario = :id_usuario AND leida = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_usuario", $userId, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0; // Retorna true si se actualizó al menos una fila
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }
}
