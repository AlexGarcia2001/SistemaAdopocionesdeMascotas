<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class Actividad
{
    private $db;
    private $table_name = "actividades";

    // Propiedades de la clase (columnas de la tabla actividades - Confirmado con tu estructura)
    public $id_actividad;
    public $nombre_actividad;
    public $descripcion;
    public $fecha_hora;
    public $id_refugio;
    public $tipo_actividad;
    public $estado;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para crear una nueva actividad
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (nombre_actividad, descripcion, fecha_hora, id_refugio, tipo_actividad, estado)
                  VALUES
                  (:nombre_actividad, :descripcion, :fecha_hora, :id_refugio, :tipo_actividad, :estado)";

        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->nombre_actividad = htmlspecialchars(strip_tags($this->nombre_actividad));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->fecha_hora = htmlspecialchars(strip_tags($this->fecha_hora));
        $this->id_refugio = htmlspecialchars(strip_tags($this->id_refugio)); // Ojo: si es null, strip_tags puede convertirlo a ""
        $this->tipo_actividad = htmlspecialchars(strip_tags($this->tipo_actividad));
        $this->estado = htmlspecialchars(strip_tags($this->estado));

        // Vincular parámetros
        $stmt->bindParam(":nombre_actividad", $this->nombre_actividad);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":fecha_hora", $this->fecha_hora);
        // Manejar id_refugio para que PDO lo trate como NULL si es "" o null
        $stmt->bindParam(":id_refugio", $this->id_refugio, $this->id_refugio === null || $this->id_refugio === '' ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":tipo_actividad", $this->tipo_actividad);
        $stmt->bindParam(":estado", $this->estado);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para obtener todas las actividades (con información del refugio si existe)
    public function getAll()
    {
        $query = "SELECT a.id_actividad, a.nombre_actividad, a.descripcion, a.fecha_hora,
                         a.id_refugio, a.tipo_actividad, a.estado,
                         r.nombre as nombre_refugio, r.direccion as direccion_refugio
                  FROM " . $this->table_name . " a
                  LEFT JOIN refugios r ON a.id_refugio = r.id_refugio
                  ORDER BY a.fecha_hora DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt; // Devuelve el PDOStatement para que el controlador lo procese
    }

    // Método para obtener una sola actividad por ID (con información del refugio)
    public function getById($id)
    {
        $query = "SELECT a.id_actividad, a.nombre_actividad, a.descripcion, a.fecha_hora,
                         a.id_refugio, a.tipo_actividad, a.estado,
                         r.nombre as nombre_refugio, r.direccion as direccion_refugio
                  FROM " . $this->table_name . " a
                  LEFT JOIN refugios r ON a.id_refugio = r.id_refugio
                  WHERE a.id_actividad = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Retornamos el array asociativo directamente
        return $row;
    }

    // Método para actualizar una actividad existente
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                  SET
                      nombre_actividad = :nombre_actividad,
                      descripcion = :descripcion,
                      fecha_hora = :fecha_hora,
                      id_refugio = :id_refugio,
                      tipo_actividad = :tipo_actividad,
                      estado = :estado
                  WHERE
                      id_actividad = :id_actividad";

        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->nombre_actividad = htmlspecialchars(strip_tags($this->nombre_actividad));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->fecha_hora = htmlspecialchars(strip_tags($this->fecha_hora));
        $this->id_refugio = htmlspecialchars(strip_tags($this->id_refugio)); // Ojo: si es null, strip_tags puede convertirlo a ""
        $this->tipo_actividad = htmlspecialchars(strip_tags($this->tipo_actividad));
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        $this->id_actividad = htmlspecialchars(strip_tags($this->id_actividad));

        // Vincular parámetros
        $stmt->bindParam(':nombre_actividad', $this->nombre_actividad);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':fecha_hora', $this->fecha_hora);
        // Manejar id_refugio para que PDO lo trate como NULL si es "" o null
        $stmt->bindParam(":id_refugio", $this->id_refugio, $this->id_refugio === null || $this->id_refugio === '' ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':tipo_actividad', $this->tipo_actividad);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id_actividad', $this->id_actividad);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para eliminar una actividad
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_actividad = :id_actividad";
        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_actividad = htmlspecialchars(strip_tags($this->id_actividad));

        // Vincular ID
        $stmt->bindParam(':id_actividad', $this->id_actividad);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }
}