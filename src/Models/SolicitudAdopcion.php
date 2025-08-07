<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class SolicitudAdopcion
{
    private $db;
    private $table_name = "solicitudesadopcion";

    // Propiedades de la clase (columnas de la tabla solicitudesadopcion)
    public $id_solicitud;
    public $id_usuario;
    public $id_mascota;
    public $fecha_solicitud; // Se espera que venga del controlador o se autogenere en DB
    public $estado_solicitud; // Enum: 'Pendiente', 'Aprobada', 'Rechazada', 'Cancelada'
    public $motivo; // Puede ser NULL
    public $fecha_aprobacion_rechazo; // Puede ser NULL
    public $observaciones; // Puede ser NULL

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para crear una nueva solicitud de adopción
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                    (id_usuario, id_mascota, fecha_solicitud, estado_solicitud, motivo, fecha_aprobacion_rechazo, observaciones)
                    VALUES
                    (:id_usuario, :id_mascota, :fecha_solicitud, :estado_solicitud, :motivo, :fecha_aprobacion_rechazo, :observaciones)";

        $stmt = $this->db->prepare($query);

        // Limpiar y vincular datos
        // Los IDs son enteros, no necesitan strip_tags si ya vienen limpios del controlador
        $this->id_usuario = (int) $this->id_usuario;
        $this->id_mascota = (int) $this->id_mascota;
        
        // Limpiar campos de texto que pueden ser null
        $this->fecha_solicitud = htmlspecialchars(strip_tags($this->fecha_solicitud));
        $this->estado_solicitud = htmlspecialchars(strip_tags($this->estado_solicitud));
        $this->motivo = $this->motivo === null ? null : htmlspecialchars(strip_tags($this->motivo));
        $this->fecha_aprobacion_rechazo = $this->fecha_aprobacion_rechazo === null ? null : htmlspecialchars(strip_tags($this->fecha_aprobacion_rechazo));
        $this->observaciones = $this->observaciones === null ? null : htmlspecialchars(strip_tags($this->observaciones));

        $stmt->bindParam(":id_usuario", $this->id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_mascota", $this->id_mascota, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_solicitud", $this->fecha_solicitud);
        $stmt->bindParam(":estado_solicitud", $this->estado_solicitud);
        // Usar PDO::PARAM_NULL para los campos que pueden ser NULL
        $stmt->bindParam(":motivo", $this->motivo, ($this->motivo === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":fecha_aprobacion_rechazo", $this->fecha_aprobacion_rechazo, ($this->fecha_aprobacion_rechazo === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":observaciones", $this->observaciones, ($this->observaciones === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            // Error Code 23000 es para violaciones de integridad, incluyendo claves foráneas
            throw $e; // Re-lanzar para ser capturado en el controlador
        }
        return false;
    }

    // Método para obtener todas las solicitudes de adopción (con info de usuario y mascota)
    public function getAll()
    {
        $query = "SELECT sa.id_solicitud, sa.id_usuario, sa.id_mascota, sa.fecha_solicitud,
                            sa.estado_solicitud, sa.motivo, sa.fecha_aprobacion_rechazo, sa.observaciones,
                            u.nombre_usuario AS nombre_usuario, u.apellido AS apellido_usuario,
                            m.nombre AS nombre_mascota, m.especie AS especie_mascota, m.raza AS raza_mascota
                    FROM " . $this->table_name . " sa
                    LEFT JOIN usuarios u ON sa.id_usuario = u.id_usuario
                    LEFT JOIN mascotas m ON sa.id_mascota = m.id_mascota
                    ORDER BY sa.fecha_solicitud DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener una sola solicitud de adopción por ID
    public function getById($id)
    {
        $query = "SELECT sa.id_solicitud, sa.id_usuario, sa.id_mascota, sa.fecha_solicitud,
                            sa.estado_solicitud, sa.motivo, sa.fecha_aprobacion_rechazo, sa.observaciones,
                            u.nombre_usuario AS nombre_usuario, u.apellido AS apellido_usuario,
                            m.nombre AS nombre_mascota, m.especie AS especie_mascota, m.raza AS raza_mascota
                    FROM " . $this->table_name . " sa
                    LEFT JOIN usuarios u ON sa.id_usuario = u.id_usuario
                    LEFT JOIN mascotas m ON sa.id_mascota = m.id_mascota
                    WHERE sa.id_solicitud = ? LIMIT 0,1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    // Método para actualizar una solicitud de adopción (todos los campos)
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                    SET
                        id_usuario = :id_usuario,
                        id_mascota = :id_mascota,
                        fecha_solicitud = :fecha_solicitud,
                        estado_solicitud = :estado_solicitud,
                        motivo = :motivo,
                        fecha_aprobacion_rechazo = :fecha_aprobacion_rechazo,
                        observaciones = :observaciones
                    WHERE id_solicitud = :id_solicitud";

        $stmt = $this->db->prepare($query);

        // Limpiar y vincular datos
        $this->id_solicitud = (int) $this->id_solicitud; // Convertir a int directamente
        $this->id_usuario = (int) $this->id_usuario;
        $this->id_mascota = (int) $this->id_mascota;
        
        $this->fecha_solicitud = htmlspecialchars(strip_tags($this->fecha_solicitud));
        $this->estado_solicitud = htmlspecialchars(strip_tags($this->estado_solicitud));
        $this->motivo = $this->motivo === null ? null : htmlspecialchars(strip_tags($this->motivo));
        $this->fecha_aprobacion_rechazo = $this->fecha_aprobacion_rechazo === null ? null : htmlspecialchars(strip_tags($this->fecha_aprobacion_rechazo));
        $this->observaciones = $this->observaciones === null ? null : htmlspecialchars(strip_tags($this->observaciones));

        $stmt->bindParam(":id_usuario", $this->id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_mascota", $this->id_mascota, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_solicitud", $this->fecha_solicitud);
        $stmt->bindParam(":estado_solicitud", $this->estado_solicitud);
        $stmt->bindParam(":motivo", $this->motivo, ($this->motivo === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":fecha_aprobacion_rechazo", $this->fecha_aprobacion_rechazo, ($this->fecha_aprobacion_rechazo === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":observaciones", $this->observaciones, ($this->observaciones === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":id_solicitud", $this->id_solicitud, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0; // Retorna true si se afectó alguna fila
            }
        } catch (PDOException $e) {
            // Error Code 23000 es para violaciones de integridad
            throw $e; // Re-lanzar para ser capturado en el controlador
        }
        return false;
    }

    // Método para actualizar solo el estado de una solicitud (y campos relacionados con la decisión)
    public function updateEstado()
    {
        // NOTA IMPORTANTE: Se ha eliminado 'motivo' de la cláusula SET
        // para asegurar que el motivo original del solicitante no sea sobrescrito
        $query = "UPDATE " . $this->table_name . "
                    SET
                        estado_solicitud = :estado_solicitud,
                        fecha_aprobacion_rechazo = :fecha_aprobacion_rechazo,
                        observaciones = :observaciones
                    WHERE id_solicitud = :id_solicitud";

        $stmt = $this->db->prepare($query);

        // Limpiar y vincular datos
        $this->id_solicitud = (int) $this->id_solicitud; // Convertir a int directamente
        $this->estado_solicitud = htmlspecialchars(strip_tags($this->estado_solicitud));
        // Limpiar campos que pueden ser NULL
        // El motivo NO se vincula aquí para que no se sobrescriba
        $this->fecha_aprobacion_rechazo = $this->fecha_aprobacion_rechazo === null ? null : htmlspecialchars(strip_tags($this->fecha_aprobacion_rechazo));
        $this->observaciones = $this->observaciones === null ? null : htmlspecialchars(strip_tags($this->observaciones));

        $stmt->bindParam(":estado_solicitud", $this->estado_solicitud);
        // El motivo NO se vincula aquí
        $stmt->bindParam(":fecha_aprobacion_rechazo", $this->fecha_aprobacion_rechazo, ($this->fecha_aprobacion_rechazo === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":observaciones", $this->observaciones, ($this->observaciones === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":id_solicitud", $this->id_solicitud, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }


    // Método para eliminar una solicitud de adopción
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_solicitud = :id_solicitud";
        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_solicitud = (int) $this->id_solicitud; // Convertir a int directamente

        // Vincular ID
        $stmt->bindParam(':id_solicitud', $this->id_solicitud, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }
}
