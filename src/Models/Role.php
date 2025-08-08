<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class Role
{
    private $db;
    private $table_name = "roles";

    public $id_rol;
    public $nombre_rol;
    public $descripcion;
    public $estado;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para obtener todos los roles
    public function getAll()
    {
        $query = "SELECT id_rol, nombre_rol, descripcion, estado FROM " . $this->table_name . " ORDER BY nombre_rol ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener un solo rol por ID
    public function getById($id)
    {
        $query = "SELECT id_rol, nombre_rol, descripcion, estado FROM " . $this->table_name . " WHERE id_rol = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Asignar valores a las propiedades del objeto
            $this->id_rol = $row['id_rol'];
            $this->nombre_rol = $row['nombre_rol'];
            $this->descripcion = $row['descripcion'];
            $this->estado = $row['estado'];
            return true;
        }
        return false;
    }

    // Método para crear un nuevo rol
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " (nombre_rol, descripcion, estado) VALUES (:nombre_rol, :descripcion, :estado)";
        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->nombre_rol = htmlspecialchars(strip_tags($this->nombre_rol));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->estado = htmlspecialchars(strip_tags($this->estado));

        // Vincular parámetros
        $stmt->bindParam(":nombre_rol", $this->nombre_rol);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":estado", $this->estado);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Método para actualizar un rol existente
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                  SET
                      nombre_rol = :nombre_rol,
                      descripcion = :descripcion,
                      estado = :estado
                  WHERE
                      id_rol = :id_rol";

        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->nombre_rol = htmlspecialchars(strip_tags($this->nombre_rol));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        $this->id_rol = htmlspecialchars(strip_tags($this->id_rol));

        // Vincular parámetros
        $stmt->bindParam(':nombre_rol', $this->nombre_rol);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id_rol', $this->id_rol);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Método para eliminar un rol
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_rol = :id_rol";
        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_rol = htmlspecialchars(strip_tags($this->id_rol));

        // Vincular ID
        $stmt->bindParam(':id_rol', $this->id_rol);

        if ($stmt->execute()) {
            // Comprobar si alguna fila fue afectada (es decir, si el rol existía)
            return $stmt->rowCount() > 0;
        }
        return false;
    }
}