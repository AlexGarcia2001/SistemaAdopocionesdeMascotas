<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class Voluntario
{
    private $db;
    private $table_name = "voluntarios";

    // Propiedades de la clase (columnas de la tabla voluntarios)
    public $id_voluntario;
    public $id_usuario;
    public $id_actividad;
    public $fecha_registro;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para crear un nuevo registro de voluntario
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (id_usuario, id_actividad, fecha_registro)
                  VALUES
                  (:id_usuario, :id_actividad, :fecha_registro)";

        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $this->id_actividad = htmlspecialchars(strip_tags($this->id_actividad));
        // La fecha de registro tiene un DEFAULT CURRENT_TIMESTAMP, pero permitimos enviarla
        $this->fecha_registro = htmlspecialchars(strip_tags($this->fecha_registro));

        // Vincular parámetros
        $stmt->bindParam(":id_usuario", $this->id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_actividad", $this->id_actividad, PDO::PARAM_INT);
        // Usar PDO::PARAM_STR para fecha_registro, permitiendo NULL si se quiere usar DEFAULT
        $stmt->bindParam(":fecha_registro", $this->fecha_registro, ($this->fecha_registro === null || $this->fecha_registro === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            throw $e; // Re-throw para ser capturado en el controlador
        }
        return false;
    }

    // Método para obtener todos los registros de voluntarios (con info de usuario y actividad)
    public function getAll()
    {
        $query = "SELECT v.id_voluntario, v.id_usuario, v.id_actividad, v.fecha_registro,
                         u.nombre_usuario as nombre_usuario, u.apellido as apellido_usuario,
                         a.nombre_actividad as nombre_actividad, a.fecha_hora as fecha_actividad
                  FROM " . $this->table_name . " v
                  LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
                  LEFT JOIN actividades a ON v.id_actividad = a.id_actividad
                  ORDER BY v.fecha_registro DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener un solo registro de voluntario por ID
    public function getById($id)
    {
        $query = "SELECT v.id_voluntario, v.id_usuario, v.id_actividad, v.fecha_registro,
                         u.nombre_usuario as nombre_usuario, u.apellido as apellido_usuario,
                         a.nombre_actividad as nombre_actividad, a.fecha_hora as fecha_actividad
                  FROM " . $this->table_name . " v
                  LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
                  LEFT JOIN actividades a ON v.id_actividad = a.id_actividad
                  WHERE v.id_voluntario = ? LIMIT 0,1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    // Método para actualizar un registro de voluntario existente
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                  SET
                      id_usuario = :id_usuario,
                      id_actividad = :id_actividad,
                      fecha_registro = :fecha_registro
                  WHERE
                      id_voluntario = :id_voluntario";

        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $this->id_actividad = htmlspecialchars(strip_tags($this->id_actividad));
        $this->fecha_registro = htmlspecialchars(strip_tags($this->fecha_registro));
        $this->id_voluntario = htmlspecialchars(strip_tags($this->id_voluntario));

        // Vincular parámetros
        $stmt->bindParam(":id_usuario", $this->id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_actividad", $this->id_actividad, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_registro", $this->fecha_registro, ($this->fecha_registro === null || $this->fecha_registro === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':id_voluntario', $this->id_voluntario, PDO::PARAM_INT);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para eliminar un registro de voluntario
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_voluntario = :id_voluntario";
        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_voluntario = htmlspecialchars(strip_tags($this->id_voluntario));

        // Vincular ID
        $stmt->bindParam(':id_voluntario', $this->id_voluntario, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }
}