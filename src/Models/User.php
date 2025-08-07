<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class User
{
    private $db;
    private $table_name = "usuarios";

    // Propiedades de la clase (columnas de la tabla usuarios)
    public $id_usuario;
    public $nombre_usuario;
    public $apellido;
    public $email;
    public $password; // Este contendrá el hash de la contraseña (usado solo en create y findByEmail)
    public $telefono;
    public $direccion;
    public $id_rol;
    public $fecha_registro;
    public $estado;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para crear un nuevo usuario
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (nombre_usuario, apellido, email, password, telefono, direccion, id_rol, estado)
                  VALUES
                  (:nombre_usuario, :apellido, :email, :password, :telefono, :direccion, :id_rol, :estado)";

        $stmt = $this->db->prepare($query);

        // Limpiar datos (EXCEPTO la contraseña, que ya viene hasheada del controlador)
        $this->nombre_usuario = htmlspecialchars(strip_tags($this->nombre_usuario));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->email = htmlspecialchars(strip_tags($this->email));
        // NO aplicar htmlspecialchars/strip_tags a la contraseña, ya está hasheada
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->id_rol = htmlspecialchars(strip_tags($this->id_rol));
        $this->estado = htmlspecialchars(strip_tags($this->estado));

        // Vincular parámetros
        $stmt->bindParam(":nombre_usuario", $this->nombre_usuario);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password); // Vincular el hash directamente
        $stmt->bindParam(":telefono", $this->telefono, $this->telefono === null || $this->telefono === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, $this->direccion === null || $this->direccion === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":id_rol", $this->id_rol);
        $stmt->bindParam(":estado", $this->estado);

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            throw $e; // Re-lanzar para ser capturado en el controlador
        }
        return false;
    }

    // Método para obtener todos los usuarios (con nombre de rol)
    public function getAll()
    {
        $query = "SELECT u.id_usuario, u.nombre_usuario, u.apellido, u.email, u.telefono, u.direccion,
                             u.id_rol, r.nombre_rol, u.fecha_registro, u.estado
                      FROM " . $this->table_name . " u
                      JOIN roles r ON u.id_rol = r.id_rol
                      ORDER BY u.fecha_registro DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener un solo usuario por ID (con nombre de rol)
    public function getById($id)
    {
        $query = "SELECT u.id_usuario, u.nombre_usuario, u.apellido, u.email, u.telefono, u.direccion,
                             u.id_rol, r.nombre_rol, u.fecha_registro, u.estado
                      FROM " . $this->table_name . " u
                      JOIN roles r ON u.id_rol = r.id_rol
                      WHERE u.id_usuario = ? LIMIT 0,1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    // Método para actualizar un usuario existente
    public function update()
    {
        // NOTA IMPORTANTE: La contraseña NO se actualiza en este método.
        // Se usa el método updatePassword() para eso.
        $query = "UPDATE " . $this->table_name . "
                  SET
                      nombre_usuario = :nombre_usuario,
                      apellido = :apellido,
                      email = :email,
                      telefono = :telefono,
                      direccion = :direccion,
                      id_rol = :id_rol,
                      estado = :estado
                  WHERE
                      id_usuario = :id_usuario";

        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->nombre_usuario = htmlspecialchars(strip_tags($this->nombre_usuario));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->id_rol = htmlspecialchars(strip_tags($this->id_rol));
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));


        // Vincular parámetros
        $stmt->bindParam(':nombre_usuario', $this->nombre_usuario);
        $stmt->bindParam(':apellido', $this->apellido);
        $stmt->bindParam(':email', $this->email);
        // NO vincular :password aquí, ya que no está en la consulta UPDATE
        $stmt->bindParam(":telefono", $this->telefono, $this->telefono === null || $this->telefono === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":direccion", $this->direccion, $this->direccion === null || $this->direccion === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':id_rol', $this->id_rol);
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id_usuario', $this->id_usuario);

        try {
            if ($stmt->execute()) {
                // Retorna true si al menos una fila fue afectada (actualizada)
                return $stmt->rowCount() > 0;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para eliminar un usuario
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_usuario = :id_usuario";
        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));

        // Vincular ID
        $stmt->bindParam(':id_usuario', $this->id_usuario);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }

    // Método para buscar un usuario por email (para login)
    // CORREGIDO: Ahora acepta el email como parámetro
    public function findByEmail($email) 
    {
        $query = "SELECT id_usuario, nombre_usuario, apellido, email, password, id_rol, estado
                  FROM " . $this->table_name . "
                  WHERE email = ? LIMIT 0,1";

        $stmt = $this->db->prepare($query);
        // Limpiar el email recibido como parámetro, no la propiedad de la clase
        $cleanEmail = htmlspecialchars(strip_tags($email)); 
        $stmt->bindParam(1, $cleanEmail); // Vincular el email limpio
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row;
        }
        return null;
    }

    // Método para actualizar la contraseña de un usuario
    public function updatePassword($id, $newPasswordHash)
    {
        $query = "UPDATE " . $this->table_name . " SET password = :password WHERE id_usuario = :id_usuario";

        $stmt = $this->db->prepare($query);

        $stmt->bindParam(':password', $newPasswordHash);
        $stmt->bindParam(':id_usuario', $id, PDO::PARAM_INT);

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