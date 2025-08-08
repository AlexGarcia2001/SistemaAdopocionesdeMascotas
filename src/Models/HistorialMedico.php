<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class HistorialMedico
{
    private $db;
    private $table_name = "historialmedico";

    // Propiedades de la clase (columnas de la tabla historialmedico)
    public $id_historial;
    public $id_mascota;
    public $fecha_visita;
    public $tipo_visita;
    public $diagnostico;
    public $tratamiento;
    public $vacunas_aplicadas;
    public $proxima_cita;
    public $observaciones;
    public $id_veterinario;

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para crear un nuevo registro de historial médico
    public function create()
    {
        error_log("DEBUG MODEL: HistorialMedico->create() - Valores antes de la consulta:");
        error_log("DEBUG MODEL: id_mascota: " . $this->id_mascota);
        error_log("DEBUG MODEL: fecha_visita: " . $this->fecha_visita);
        error_log("DEBUG MODEL: tipo_visita: " . $this->tipo_visita);
        error_log("DEBUG MODEL: diagnostico: " . $this->diagnostico);
        error_log("DEBUG MODEL: tratamiento: " . $this->tratamiento);
        error_log("DEBUG MODEL: vacunas_aplicadas: " . $this->vacunas_aplicadas);
        error_log("DEBUG MODEL: proxima_cita: " . $this->proxima_cita);
        error_log("DEBUG MODEL: observaciones: " . $this->observaciones);
        error_log("DEBUG MODEL: id_veterinario: " . $this->id_veterinario);

        $query = "INSERT INTO " . $this->table_name . "
                    (id_mascota, fecha_visita, tipo_visita, diagnostico, tratamiento, vacunas_aplicadas, proxima_cita, observaciones, id_veterinario)
                    VALUES
                    (:id_mascota, :fecha_visita, :tipo_visita, :diagnostico, :tratamiento, :vacunas_aplicadas, :proxima_cita, :observaciones, :id_veterinario)";

        $stmt = $this->db->prepare($query);

        // Limpiar y vincular datos
        $this->id_mascota = htmlspecialchars(strip_tags($this->id_mascota));
        $this->fecha_visita = htmlspecialchars(strip_tags($this->fecha_visita));
        $this->tipo_visita = htmlspecialchars(strip_tags($this->tipo_visita));
        $this->diagnostico = htmlspecialchars(strip_tags($this->diagnostico));
        $this->tratamiento = htmlspecialchars(strip_tags($this->tratamiento));
        $this->vacunas_aplicadas = htmlspecialchars(strip_tags($this->vacunas_aplicadas));
        $this->proxima_cita = htmlspecialchars(strip_tags($this->proxima_cita));
        $this->observaciones = htmlspecialchars(strip_tags($this->observaciones));
        $this->id_veterinario = htmlspecialchars(strip_tags($this->id_veterinario));

        $stmt->bindParam(":id_mascota", $this->id_mascota, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_visita", $this->fecha_visita);
        $stmt->bindParam(":tipo_visita", $this->tipo_visita);
        $stmt->bindParam(":diagnostico", $this->diagnostico);
        $stmt->bindParam(":tratamiento", $this->tratamiento);
        $stmt->bindParam(":vacunas_aplicadas", $this->vacunas_aplicadas);
        $stmt->bindParam(":proxima_cita", $this->proxima_cita);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":id_veterinario", $this->id_veterinario, PDO::PARAM_INT);

        try {
            error_log("DEBUG MODEL: Ejecutando sentencia SQL para create().");
            if ($stmt->execute()) {
                error_log("DEBUG MODEL: Sentencia SQL ejecutada exitosamente en create().");
                return true;
            }
        } catch (PDOException $e) {
            error_log("ERROR MODEL: PDOException en create(): " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
            throw $e; // Re-throw para ser capturado en el controlador
        }
        error_log("DEBUG MODEL: Sentencia SQL no retornó true en create().");
        return false;
    }

    // Método para obtener todos los registros de historial médico (con info de mascota, veterinario y propietario de la mascota)
    public function getAll()
    {
        $query = "SELECT hm.id_historial, hm.id_mascota, hm.fecha_visita, hm.tipo_visita, hm.diagnostico,
                            hm.tratamiento, hm.vacunas_aplicadas, hm.proxima_cita, hm.observaciones, hm.id_veterinario,
                            m.nombre as nombre_mascota, m.especie as especie_mascota, m.id_usuario AS id_usuario_propietario,
                            v.nombre_usuario as nombre_veterinario, v.apellido as apellido_veterinario
                    FROM " . $this->table_name . " hm
                    LEFT JOIN mascotas m ON hm.id_mascota = m.id_mascota
                    LEFT JOIN usuarios v ON hm.id_veterinario = v.id_usuario
                    ORDER BY hm.fecha_visita DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener un solo registro de historial médico por ID (con info de mascota, veterinario y propietario de la mascota)
    public function getById($id)
    {
        $query = "SELECT hm.id_historial, hm.id_mascota, hm.fecha_visita, hm.tipo_visita, hm.diagnostico,
                            hm.tratamiento, hm.vacunas_aplicadas, hm.proxima_cita, hm.observaciones, hm.id_veterinario,
                            m.nombre as nombre_mascota, m.especie as especie_mascota, m.id_usuario AS id_usuario_propietario,
                            v.nombre_usuario as nombre_veterinario, v.apellido as apellido_veterinario
                    FROM " . $this->table_name . " hm
                    LEFT JOIN mascotas m ON hm.id_mascota = m.id_mascota
                    LEFT JOIN usuarios v ON hm.id_veterinario = v.id_usuario
                    WHERE hm.id_historial = ? LIMIT 0,1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    // Método para actualizar un registro de historial médico existente
    public function update()
    {
        error_log("DEBUG MODEL: HistorialMedico->update() - Valores antes de la consulta:");
        error_log("DEBUG MODEL: id_historial: " . $this->id_historial);
        error_log("DEBUG MODEL: id_mascota: " . $this->id_mascota);
        error_log("DEBUG MODEL: fecha_visita: " . $this->fecha_visita);
        error_log("DEBUG MODEL: tipo_visita: " . $this->tipo_visita);
        error_log("DEBUG MODEL: diagnostico: " . $this->diagnostico);
        error_log("DEBUG MODEL: tratamiento: " . $this->tratamiento);
        error_log("DEBUG MODEL: vacunas_aplicadas: " . $this->vacunas_aplicadas);
        error_log("DEBUG MODEL: proxima_cita: " . $this->proxima_cita);
        error_log("DEBUG MODEL: observaciones: " . $this->observaciones);
        error_log("DEBUG MODEL: id_veterinario: " . $this->id_veterinario);

        $query = "UPDATE " . $this->table_name . "
                    SET
                        id_mascota = :id_mascota,
                        fecha_visita = :fecha_visita,
                        tipo_visita = :tipo_visita,
                        diagnostico = :diagnostico,
                        tratamiento = :tratamiento,
                        vacunas_aplicadas = :vacunas_aplicadas,
                        proxima_cita = :proxima_cita,
                        observaciones = :observaciones,
                        id_veterinario = :id_veterinario
                    WHERE
                        id_historial = :id_historial";

        $stmt = $this->db->prepare($query);

        // Limpiar y vincular datos
        $this->id_mascota = htmlspecialchars(strip_tags($this->id_mascota));
        $this->fecha_visita = htmlspecialchars(strip_tags($this->fecha_visita));
        $this->tipo_visita = htmlspecialchars(strip_tags($this->tipo_visita));
        $this->diagnostico = htmlspecialchars(strip_tags($this->diagnostico));
        $this->tratamiento = htmlspecialchars(strip_tags($this->tratamiento));
        $this->vacunas_aplicadas = htmlspecialchars(strip_tags($this->vacunas_aplicadas));
        $this->proxima_cita = htmlspecialchars(strip_tags($this->proxima_cita));
        $this->observaciones = htmlspecialchars(strip_tags($this->observaciones));
        $this->id_veterinario = htmlspecialchars(strip_tags($this->id_veterinario));
        $this->id_historial = htmlspecialchars(strip_tags($this->id_historial));

        $stmt->bindParam(":id_mascota", $this->id_mascota, PDO::PARAM_INT);
        $stmt->bindParam(":fecha_visita", $this->fecha_visita);
        $stmt->bindParam(":tipo_visita", $this->tipo_visita);
        $stmt->bindParam(":diagnostico", $this->diagnostico);
        $stmt->bindParam(":tratamiento", $this->tratamiento);
        $stmt->bindParam(":vacunas_aplicadas", $this->vacunas_aplicadas);
        $stmt->bindParam(":proxima_cita", $this->proxima_cita);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":id_veterinario", $this->id_veterinario, PDO::PARAM_INT);
        $stmt->bindParam(':id_historial', $this->id_historial, PDO::PARAM_INT);

        try {
            error_log("DEBUG MODEL: Ejecutando sentencia SQL para update().");
            if ($stmt->execute()) {
                error_log("DEBUG MODEL: Sentencia SQL ejecutada exitosamente en update().");
                return true;
            }
        } catch (PDOException $e) {
            error_log("ERROR MODEL: PDOException en update(): " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
            throw $e;
        }
        error_log("DEBUG MODEL: Sentencia SQL no retornó true en update().");
        return false;
    }

    // Método para eliminar un registro de historial médico
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_historial = :id_historial";
        $stmt = $this->db->prepare($query);

        // Limpiar datos
        $this->id_historial = htmlspecialchars(strip_tags($this->id_historial));

        // Vincular ID
        $stmt->bindParam(':id_historial', $this->id_historial, PDO::PARAM_INT);

        if ($stmt->execute()) {
            return $stmt->rowCount() > 0;
        }
        return false;
    }
}
