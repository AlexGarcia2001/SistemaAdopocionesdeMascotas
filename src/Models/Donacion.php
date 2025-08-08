<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class Donacion
{
    private $db;
    private $table_name = "donaciones";

    // Propiedades de la clase (columnas de la tabla donaciones - Confirmado con tu estructura)
    public $id_donacion;
    public $id_usuario;
    public $id_refugio;
    public $cantidad;
    public $moneda;
    public $fecha_donacion;
    public $metodo_pago;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para crear una nueva donación
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (id_usuario, id_refugio, cantidad, moneda, fecha_donacion, metodo_pago)
                  VALUES
                  (:id_usuario, :id_refugio, :cantidad, :moneda, :fecha_donacion, :metodo_pago)";

        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $this->id_refugio = htmlspecialchars(strip_tags($this->id_refugio));
        $this->cantidad = htmlspecialchars(strip_tags($this->cantidad));
        $this->moneda = htmlspecialchars(strip_tags($this->moneda));
        $this->fecha_donacion = htmlspecialchars(strip_tags($this->fecha_donacion));
        $this->metodo_pago = htmlspecialchars(strip_tags($this->metodo_pago));

        // Vincular parámetros
        $stmt->bindParam(":id_usuario", $this->id_usuario, PDO::PARAM_INT);
        // Manejar id_refugio para que PDO lo trate como NULL si es "" o null
        $stmt->bindParam(":id_refugio", $this->id_refugio, $this->id_refugio === null || $this->id_refugio === '' ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(":cantidad", $this->cantidad);
        $fecha_donacion_param = empty($this->fecha_donacion) ? date('Y-m-d H:i:s') : $this->fecha_donacion;
        $stmt->bindParam(":fecha_donacion", $fecha_donacion_param);
        $stmt->bindParam(":moneda", $this->moneda);
        $stmt->bindParam(":metodo_pago", $this->metodo_pago, $this->metodo_pago === null || $this->metodo_pago === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);


        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para obtener todas las donaciones (con información del usuario y refugio si existen)
    public function getAll()
    {
        $query = "SELECT d.id_donacion, d.id_usuario, d.cantidad, d.moneda, d.fecha_donacion, d.metodo_pago,
                           u.nombre_usuario as nombre_usuario, u.apellido as apellido_usuario, u.email as email_usuario,
                           r.nombre as nombre_refugio, r.direccion as direccion_refugio
                    FROM " . $this->table_name . " d
                    JOIN usuarios u ON d.id_usuario = u.id_usuario
                    LEFT JOIN refugios r ON d.id_refugio = r.id_refugio
                    ORDER BY d.fecha_donacion DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener una sola donación por ID (con información del usuario y refugio)
    public function getById($id)
    {
        $query = "SELECT d.id_donacion, d.id_usuario, d.cantidad, d.moneda, d.fecha_donacion, d.metodo_pago,
                           u.nombre_usuario as nombre_usuario, u.apellido as apellido_usuario, u.email as email_usuario,
                           r.nombre as nombre_refugio, r.direccion as direccion_refugio
                    FROM " . $this->table_name . " d
                    JOIN usuarios u ON d.id_usuario = u.id_usuario
                    LEFT JOIN refugios r ON d.id_refugio = r.id_refugio
                    WHERE d.id_donacion = ? LIMIT 0,1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    // Método para actualizar una donación existente
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                  SET
                      id_usuario = :id_usuario,
                      id_refugio = :id_refugio,
                      cantidad = :cantidad,
                      moneda = :moneda,
                      metodo_pago = :metodo_pago"; // <-- QUITAMOS fecha_donacion de aquí

        // Si fecha_donacion se proporciona, lo incluimos en el UPDATE
        if (!empty($this->fecha_donacion) && $this->fecha_donacion !== '0000-00-00 00:00:00') {
            $query .= ", fecha_donacion = :fecha_donacion";
        }

        $query .= " WHERE id_donacion = :id_donacion";

        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_usuario = htmlspecialchars(strip_tags($this->id_usuario));
        $this->id_refugio = htmlspecialchars(strip_tags($this->id_refugio));
        $this->cantidad = htmlspecialchars(strip_tags($this->cantidad));
        $this->moneda = htmlspecialchars(strip_tags($this->moneda));
        $this->fecha_donacion = htmlspecialchars(strip_tags($this->fecha_donacion)); // Sigue limpiándose por si se usa
        $this->metodo_pago = htmlspecialchars(strip_tags($this->metodo_pago));
        $this->id_donacion = htmlspecialchars(strip_tags($this->id_donacion));

        // Vincular parámetros
        $stmt->bindParam(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(":id_refugio", $this->id_refugio, $this->id_refugio === null || $this->id_refugio === '' ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':cantidad', $this->cantidad);
        $stmt->bindParam(':moneda', $this->moneda);
        $stmt->bindParam(":metodo_pago", $this->metodo_pago, $this->metodo_pago === null || $this->metodo_pago === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':id_donacion', $this->id_donacion);

        // Solo vincular fecha_donacion si se incluyó en la consulta
        if (!empty($this->fecha_donacion) && $this->fecha_donacion !== '0000-00-00 00:00:00') {
            $stmt->bindParam(':fecha_donacion', $this->fecha_donacion);
        }

        try {
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para eliminar una donación
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_donacion = :id_donacion";
        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_donacion = htmlspecialchars(strip_tags($this->id_donacion));

        // Vincular ID
        $stmt->bindParam(':id_donacion', $this->id_donacion);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }
}