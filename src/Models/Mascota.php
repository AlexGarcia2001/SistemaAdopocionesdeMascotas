<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class Mascota
{
    private $db;
    private $table_name = "mascotas";

    // Propiedades de la clase (columnas de la tabla mascotas)
    public $id_mascota;
    public $nombre;
    public $especie;
    public $raza;
    public $edad;
    public $sexo;
    public $tamano;
    public $descripcion;
    public $fecha_rescate;
    public $estado_adopcion;
    public $id_refugio;
    public $estado;
    public $id_usuario; // Propiedad para el ID del usuario propietario

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para crear una nueva mascota
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                    (nombre, especie, raza, edad, sexo, tamano, descripcion, fecha_rescate, estado_adopcion, id_refugio, estado, id_usuario)
                    VALUES
                    (:nombre, :especie, :raza, :edad, :sexo, :tamano, :descripcion, :fecha_rescate, :estado_adopcion, :id_refugio, :estado, :id_usuario)";

        $stmt = $this->db->prepare($query);

        // Limpiar datos (NO aplicar a IDs numéricos, pero sí a los que vienen del input)
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->especie = htmlspecialchars(strip_tags($this->especie));
        $this->raza = htmlspecialchars(strip_tags($this->raza));
        $this->edad = htmlspecialchars(strip_tags($this->edad));
        $this->sexo = htmlspecialchars(strip_tags($this->sexo));
        $this->tamano = htmlspecialchars(strip_tags($this->tamano));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->fecha_rescate = htmlspecialchars(strip_tags($this->fecha_rescate));
        $this->estado_adopcion = htmlspecialchars(strip_tags($this->estado_adopcion));
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        // Asegurarse de que los IDs se conviertan a enteros antes de vincular
        $id_refugio_int = (int)$this->id_refugio;
        $id_usuario_int = (int)$this->id_usuario;

        // Vincular parámetros
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":especie", $this->especie);
        $stmt->bindParam(":raza", $this->raza);
        $stmt->bindParam(":edad", $this->edad);
        $stmt->bindParam(":sexo", $this->sexo);
        $stmt->bindParam(":tamano", $this->tamano);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(":fecha_rescate", $this->fecha_rescate);
        $stmt->bindParam(":estado_adopcion", $this->estado_adopcion);
        $stmt->bindParam(":id_refugio", $id_refugio_int, PDO::PARAM_INT); 
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":id_usuario", $id_usuario_int, PDO::PARAM_INT); 

        try {
            if ($stmt->execute()) {
                // Obtener el ID de la mascota recién creada
                $this->id_mascota = $this->db->lastInsertId(); 
                return true;
            }
        } catch (PDOException $e) {
            throw $e; // Lanza la excepción para que el controlador la capture
        }
        return false;
    }

    // Método para obtener todas las mascotas (incluye foto_url del primer registro de galería)
    // Este método está optimizado para la vista de lista, trayendo solo una foto por mascota
    public function getAll()
    {
        $query = "SELECT m.id_mascota, m.nombre, m.especie, m.raza, m.edad, m.sexo, m.tamano, m.descripcion, m.fecha_rescate, m.estado_adopcion, m.id_usuario, m.id_refugio, m.estado,
                           (SELECT gm_latest.url_archivo
                            FROM galeriamultimedia gm_latest
                            WHERE gm_latest.id_mascota = m.id_mascota
                            ORDER BY gm_latest.fecha_subida DESC, gm_latest.id_multimedia DESC
                            LIMIT 1) as foto_url
                    FROM " . $this->table_name . " m
                    WHERE m.estado != 'eliminado'
                    ORDER BY m.fecha_rescate DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener una sola mascota por ID (AHORA TRAE MÚLTIPLES FOTOS SI EXISTEN)
    // Este método está diseñado para la vista de detalles, trayendo todos los registros de galería
    public function getById($id)
    {
        // Se seleccionan todos los campos de la mascota y los campos de la galería multimedia.
        // NO se usa GROUP BY ni LIMIT para permitir múltiples registros de galería por mascota.
        $query = "SELECT m.id_mascota, m.nombre, m.especie, m.raza, m.edad, m.sexo, m.tamano, m.descripcion, m.fecha_rescate, m.estado_adopcion, m.id_refugio, m.estado, m.id_usuario, 
                            gm.id_multimedia, gm.tipo_archivo, gm.url_archivo, gm.descripcion as foto_descripcion, gm.fecha_subida as foto_fecha_subida
                    FROM " . $this->table_name . " m
                    LEFT JOIN galeriamultimedia gm ON m.id_mascota = gm.id_mascota
                    WHERE m.id_mascota = :id_mascota AND m.estado != 'eliminado'
                    ORDER BY gm.fecha_subida DESC, gm.id_multimedia DESC"; // Ordenar por fecha DESC para tener la más reciente primero

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_mascota', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Se devuelve el statement directamente. El controlador será responsable de
        // iterar sobre los resultados y agrupar las fotos si es necesario.
        return $stmt;
    }

    // Método para actualizar una mascota existente
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                    SET
                        nombre = :nombre,
                        especie = :especie,
                        raza = :raza,
                        edad = :edad,
                        sexo = :sexo,
                        tamano = :tamano,
                        descripcion = :descripcion,
                        fecha_rescate = :fecha_rescate,
                        estado_adopcion = :estado_adopcion,
                        id_refugio = :id_refugio,
                        estado = :estado,
                        id_usuario = :id_usuario 
                    WHERE
                        id_mascota = :id_mascota";

        $stmt = $this->db->prepare($query);

        // Limpiar datos (NO aplicar a IDs numéricos, pero sí a los que vienen del input)
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->especie = htmlspecialchars(strip_tags($this->especie));
        $this->raza = htmlspecialchars(strip_tags($this->raza));
        $this->edad = htmlspecialchars(strip_tags($this->edad));
        $this->sexo = htmlspecialchars(strip_tags($this->sexo));
        $this->tamano = htmlspecialchars(strip_tags($this->tamano));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->fecha_rescate = htmlspecialchars(strip_tags($this->fecha_rescate));
        $this->estado_adopcion = htmlspecialchars(strip_tags($this->estado_adopcion));
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        // Asegurarse de que los IDs se conviertan a enteros antes de vincular
        $id_refugio_int = (int)$this->id_refugio;
        $id_usuario_int = (int)$this->id_usuario;
        $id_mascota_int = (int)$this->id_mascota;

        // Vincular parámetros
        $stmt->bindParam(':nombre', $this->nombre);
        $stmt->bindParam(':especie', $this->especie);
        $stmt->bindParam(':raza', $this->raza);
        $stmt->bindParam(':edad', $this->edad);
        $stmt->bindParam(':sexo', $this->sexo);
        $stmt->bindParam(':tamano', $this->tamano);
        $stmt->bindParam(':descripcion', $this->descripcion);
        $stmt->bindParam(':fecha_rescate', $this->fecha_rescate);
        $stmt->bindParam(':estado_adopcion', $this->estado_adopcion);
        $stmt->bindParam(':id_refugio', $id_refugio_int, PDO::PARAM_INT); 
        $stmt->bindParam(':estado', $this->estado);
        $stmt->bindParam(':id_usuario', $id_usuario_int, PDO::PARAM_INT); 
        $stmt->bindParam(':id_mascota', $id_mascota_int, PDO::PARAM_INT); 

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para eliminar una mascota (AHORA REALIZA ELIMINACIÓN FÍSICA Y DE MULTIMEDIA ASOCIADA)
    public function delete()
    {
        // 1. Eliminar registros de galería multimedia asociados
        $galeriaMultimediaModel = new GaleriaMultimedia();
        try {
            $galeriaMultimediaModel->deleteByMascotaId((int)$this->id_mascota);
            error_log("DEBUG: Multimedia eliminada para mascota ID: " . $this->id_mascota);
        } catch (PDOException $e) {
            error_log("ERROR: Falló la eliminación de multimedia para mascota ID " . $this->id_mascota . ": " . $e->getMessage());
            // Si falla la eliminación de multimedia, puedes decidir si quieres abortar
            // la eliminación de la mascota o continuar. Por ahora, continuamos.
        }

        // 2. Eliminar la mascota físicamente
        $query = "DELETE FROM " . $this->table_name . " WHERE id_mascota = :id_mascota";
        $stmt = $this->db->prepare($query);

        // Limpiar y vincular ID
        $id_mascota_int = (int)$this->id_mascota;
        $stmt->bindParam(':id_mascota', $id_mascota_int, PDO::PARAM_INT); 

        try {
            if ($stmt->execute()) {
                return $stmt->rowCount() > 0; // Devuelve true si se eliminó al menos 1 fila
            }
        } catch (PDOException $e) {
            throw $e; // Lanza la excepción para que el controlador la capture
        }
        return false;
    }
}