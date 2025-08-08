<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class Refugio
{
    private $db;
    private $table_name = "refugios";

    // Propiedades de la clase (columnas de la tabla refugios)
    public $id_refugio;
    public $nombre;
    public $direccion;
    public $ciudad;
    public $pais;
    public $telefono;
    public $email;
    public $latitud;
    public $longitud;
    public $estado; // ENUM('activo', 'inactivo')

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para crear un nuevo refugio
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                    (nombre, direccion, ciudad, pais, telefono, email, latitud, longitud, estado)
                    VALUES
                    (:nombre, :direccion, :ciudad, :pais, :telefono, :email, :latitud, :longitud, :estado)";

        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->ciudad = htmlspecialchars(strip_tags($this->ciudad));
        $this->pais = htmlspecialchars(strip_tags($this->pais));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->email = htmlspecialchars(strip_tags($this->email));
        // Latitud y longitud deben ser tratados como números si es posible, o como cadenas si la DB es VARCHAR
        // Asumiendo que pueden ser cadenas para flexibilidad, pero la validación de formato es importante en el controlador/frontend.
        $this->latitud = htmlspecialchars(strip_tags($this->latitud));
        $this->longitud = htmlspecialchars(strip_tags($this->longitud));
        $this->estado = htmlspecialchars(strip_tags($this->estado));

        // Vincular parámetros
        $stmt->bindParam(":nombre", $this->nombre);
        // Usar PDO::PARAM_NULL para campos opcionales que pueden ser NULL o cadena vacía
        $stmt->bindParam(":direccion", $this->direccion, ($this->direccion === null || $this->direccion === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":ciudad", $this->ciudad);
        $stmt->bindParam(":pais", $this->pais, ($this->pais === null || $this->pais === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":telefono", $this->telefono, ($this->telefono === null || $this->telefono === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":email", $this->email, ($this->email === null || $this->email === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":latitud", $this->latitud, ($this->latitud === null || $this->latitud === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":longitud", $this->longitud, ($this->longitud === null || $this->longitud === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":estado", $this->estado);


        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            // Re-lanzar la excepción para que el controlador la capture y la registre
            throw $e;
        }
        return false;
    }

    // Método para obtener todos los refugios
    public function getAll()
    {
        $query = "SELECT id_refugio, nombre, direccion, ciudad, pais, telefono, email, latitud, longitud, estado
                    FROM " . $this->table_name . "
                    ORDER BY nombre ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener un solo refugio por ID
    public function getById($id)
    {
        $query = "SELECT id_refugio, nombre, direccion, ciudad, pais, telefono, email, latitud, longitud, estado
                    FROM " . $this->table_name . "
                    WHERE id_refugio = ? LIMIT 0,1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT); // Explicitly bind as INT
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    // Método para actualizar un refugio existente
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                    SET
                        nombre = :nombre,
                        direccion = :direccion,
                        ciudad = :ciudad,
                        pais = :pais,
                        telefono = :telefono,
                        email = :email,
                        latitud = :latitud,
                        longitud = :longitud,
                        estado = :estado
                    WHERE
                        id_refugio = :id_refugio";

        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->direccion = htmlspecialchars(strip_tags($this->direccion));
        $this->ciudad = htmlspecialchars(strip_tags($this->ciudad));
        $this->pais = htmlspecialchars(strip_tags($this->pais));
        $this->telefono = htmlspecialchars(strip_tags($this->telefono));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->latitud = htmlspecialchars(strip_tags($this->latitud));
        $this->longitud = htmlspecialchars(strip_tags($this->longitud));
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        $this->id_refugio = (int) $this->id_refugio; // Explicitly cast to int

        // Vincular parámetros
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":direccion", $this->direccion, ($this->direccion === null || $this->direccion === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":ciudad", $this->ciudad);
        $stmt->bindParam(":pais", $this->pais, ($this->pais === null || $this->pais === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":telefono", $this->telefono, ($this->telefono === null || $this->telefono === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":email", $this->email, ($this->email === null || $this->email === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":latitud", $this->latitud, ($this->latitud === null || $this->latitud === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":longitud", $this->longitud, ($this->longitud === null || $this->longitud === '') ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(':id_refugio', $this->id_refugio, PDO::PARAM_INT); // Explicitly bind as INT

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0; // Return true if any row was affected
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para eliminar un refugio
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_refugio = :id_refugio";
        $stmt = $this->db->prepare($query);

        // Limpiar datos y vincular ID
        $this->id_refugio = (int) $this->id_refugio; // Explicitly cast to int
        $stmt->bindParam(':id_refugio', $this->id_refugio, PDO::PARAM_INT); // Explicitly bind as INT

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
