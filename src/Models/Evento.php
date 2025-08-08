<?php
// src/Models/Evento.php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class Evento
{
    private $db;
    private $table_name = "eventos"; // Asegúrate de que este sea el nombre exacto de tu tabla de eventos

    // Propiedades de la clase (columnas de la tabla eventos)
    public $id_evento;
    public $nombre_evento;
    public $descripcion;
    public $fecha_hora; // Contiene tanto fecha como hora (TIMESTAMP o DATETIME en DB)
    public $ubicacion;
    public $organizador; // Campo para el nombre del organizador (cadena)
    public $cupo_maximo; // Capacidad máxima de asistentes (entero, puede ser NULL)
    public $estado;       // Por ejemplo: 'programado', 'cancelado', 'completado'

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para crear un nuevo evento
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                    (nombre_evento, descripcion, fecha_hora, ubicacion, organizador, cupo_maximo, estado)
                    VALUES
                    (:nombre_evento, :descripcion, :fecha_hora, :ubicacion, :organizador, :cupo_maximo, :estado)";

        $stmt = $this->db->prepare($query);

        // Limpiar y vincular datos
        $this->nombre_evento = htmlspecialchars(strip_tags($this->nombre_evento));
        $this->descripcion = ($this->descripcion === null || $this->descripcion === '') ? null : htmlspecialchars(strip_tags($this->descripcion));
        
        // CLAVE: Convertir 'YYYY-MM-DDTHH:MM' a 'YYYY-MM-DD HH:MM:SS' para la base de datos
        // Asegurarse de que $this->fecha_hora no sea nulo o vacío antes de strtotime
        $this->fecha_hora = !empty($this->fecha_hora) ? date('Y-m-d H:i:s', strtotime($this->fecha_hora)) : null;
        
        $this->ubicacion = ($this->ubicacion === null || $this->ubicacion === '') ? null : htmlspecialchars(strip_tags($this->ubicacion));
        $this->organizador = ($this->organizador === null || $this->organizador === '') ? null : htmlspecialchars(strip_tags($this->organizador));
        $this->cupo_maximo = ($this->cupo_maximo === null || $this->cupo_maximo === '') ? null : (int) $this->cupo_maximo;
        $this->estado = htmlspecialchars(strip_tags((string)($this->estado ?? ''))); 

        $stmt->bindParam(":nombre_evento", $this->nombre_evento);
        $stmt->bindParam(":descripcion", $this->descripcion, ($this->descripcion === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":fecha_hora", $this->fecha_hora, ($this->fecha_hora === null) ? PDO::PARAM_NULL : PDO::PARAM_STR); // Bind como STR o NULL
        $stmt->bindParam(":ubicacion", $this->ubicacion, ($this->ubicacion === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":organizador", $this->organizador, ($this->organizador === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":cupo_maximo", $this->cupo_maximo, ($this->cupo_maximo === null) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":estado", $this->estado, PDO::PARAM_STR);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para obtener todos los eventos
    public function getAll()
    {
        $query = "SELECT id_evento, nombre_evento, descripcion, fecha_hora, ubicacion, organizador, cupo_maximo, estado
                    FROM " . $this->table_name . "
                    ORDER BY fecha_hora DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener un solo evento por ID
    public function getById($id)
    {
        $query = "SELECT id_evento, nombre_evento, descripcion, fecha_hora, ubicacion, organizador, cupo_maximo, estado
                    FROM " . $this->table_name . "
                    WHERE id_evento = :id_evento LIMIT 0,1"; // Usar named parameter

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id_evento", $id, PDO::PARAM_INT); // Vincular con named parameter y tipo INT
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    // Método para actualizar un evento existente
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                    SET
                        nombre_evento = :nombre_evento,
                        descripcion = :descripcion,
                        fecha_hora = :fecha_hora,
                        ubicacion = :ubicacion,
                        organizador = :organizador,
                        cupo_maximo = :cupo_maximo,
                        estado = :estado
                    WHERE
                        id_evento = :id_evento";

        $stmt = $this->db->prepare($query);

        // Limpiar y vincular datos
        $this->nombre_evento = htmlspecialchars(strip_tags($this->nombre_evento));
        $this->descripcion = ($this->descripcion === null || $this->descripcion === '') ? null : htmlspecialchars(strip_tags($this->descripcion));
        
        // CLAVE: Convertir 'YYYY-MM-DDTHH:MM' a 'YYYY-MM-DD HH:MM:SS' para la base de datos
        // Asegurarse de que $this->fecha_hora no sea nulo o vacío antes de strtotime
        $this->fecha_hora = !empty($this->fecha_hora) ? date('Y-m-d H:i:s', strtotime($this->fecha_hora)) : null;
        
        $this->ubicacion = ($this->ubicacion === null || $this->ubicacion === '') ? null : htmlspecialchars(strip_tags($this->ubicacion));
        $this->organizador = ($this->organizador === null || $this->organizador === '') ? null : htmlspecialchars(strip_tags($this->organizador));
        $this->cupo_maximo = ($this->cupo_maximo === null || $this->cupo_maximo === '') ? null : (int) $this->cupo_maximo;
        $this->estado = htmlspecialchars(strip_tags((string)($this->estado ?? '')));
        $this->id_evento = (int) $this->id_evento; // Convertir a int

        $stmt->bindParam(":nombre_evento", $this->nombre_evento);
        $stmt->bindParam(":descripcion", $this->descripcion, ($this->descripcion === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":fecha_hora", $this->fecha_hora, ($this->fecha_hora === null) ? PDO::PARAM_NULL : PDO::PARAM_STR); // Bind como STR o NULL
        $stmt->bindParam(":ubicacion", $this->ubicacion, ($this->ubicacion === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":organizador", $this->organizador, ($this->organizador === null) ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":cupo_maximo", $this->cupo_maximo, ($this->cupo_maximo === null) ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":estado", $this->estado, PDO::PARAM_STR);
        $stmt->bindParam(':id_evento', $this->id_evento, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                // Si la consulta se ejecutó sin errores, consideramos que la operación fue un éxito.
                // El rowCount() puede ser 0 si no hubo cambios reales en los datos.
                return true; 
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para eliminar un evento
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_evento = :id_evento";
        $stmt = $this->db->prepare($query);

        // Limpiar datos y vincular ID
        $this->id_evento = (int) $this->id_evento; // Convertir a int
        $stmt->bindParam(':id_evento', $this->id_evento, PDO::PARAM_INT); // Vincular con tipo INT

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }
}
