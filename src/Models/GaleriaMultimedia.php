<?php

namespace App\Models;

use App\DB\Connection;
use PDO;
use PDOException;

class GaleriaMultimedia
{
    private $db;
    private $table_name = "galeriamultimedia";

    // Propiedades de la clase (columnas de la tabla galeriamultimedia)
    public $id_multimedia;
    public $id_mascota;
    public $tipo_archivo;
    public $url_archivo;
    public $descripcion;
    public $fecha_subida; // Se autogenera por CURRENT_TIMESTAMP() pero lo mantenemos

    public function __construct()
    {
        $this->db = Connection::getInstance()->getConnection();
    }

    // Método para crear un nuevo registro multimedia
    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . "
                    (id_mascota, tipo_archivo, url_archivo, descripcion)
                    VALUES
                    (:id_mascota, :tipo_archivo, :url_archivo, :descripcion)";

        $stmt = $this->db->prepare($query);

        // Limpiar y vincular datos
        // Asegurarse de que id_mascota sea un entero para el bindParam
        $id_mascota_int = (int)$this->id_mascota; 
        $this->tipo_archivo = htmlspecialchars(strip_tags($this->tipo_archivo));
        $this->url_archivo = htmlspecialchars(strip_tags($this->url_archivo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));

        $stmt->bindParam(":id_mascota", $id_mascota_int, PDO::PARAM_INT);
        $stmt->bindParam(":tipo_archivo", $this->tipo_archivo);
        $stmt->bindParam(":url_archivo", $this->url_archivo);
        $stmt->bindParam(":descripcion", $this->descripcion);

        try {
            if ($stmt->execute()) {
                // Opcional: Obtener el ID del último insertado si es necesario
                // $this->id_multimedia = $this->db->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            throw $e; // Re-throw para ser capturado en el controlador
        }
        return false;
    }

    // Método para obtener todos los registros multimedia (con info de mascota y su propietario)
    public function getAll()
    {
        $query = "SELECT gm.id_multimedia, gm.id_mascota, gm.tipo_archivo, gm.url_archivo,
                            gm.descripcion, gm.fecha_subida,
                            m.nombre as nombre_mascota, m.especie as especie_mascota,
                            m.id_usuario AS id_usuario_propietario -- Añadido el ID del propietario de la mascota
                    FROM " . $this->table_name . " gm
                    LEFT JOIN mascotas m ON gm.id_mascota = m.id_mascota
                    ORDER BY gm.fecha_subida DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método para obtener un solo registro multimedia por ID (con info de mascota y su propietario)
    public function getById($id)
    {
        $query = "SELECT gm.id_multimedia, gm.id_mascota, gm.tipo_archivo, gm.url_archivo,
                            gm.descripcion, gm.fecha_subida,
                            m.nombre as nombre_mascota, m.especie as especie_mascota,
                            m.id_usuario AS id_usuario_propietario -- Añadido el ID del propietario de la mascota
                    FROM " . $this->table_name . " gm
                    LEFT JOIN mascotas m ON gm.id_mascota = m.id_mascota
                    WHERE gm.id_multimedia = ? LIMIT 0,1";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    /**
     * Método para obtener registros multimedia por ID de mascota.
     * @param int $mascotaId El ID de la mascota.
     * @return array Un array de registros multimedia asociados a la mascota.
     */
    public function getByMascotaId(int $mascotaId): array
    {
        $query = "SELECT id_multimedia, url_archivo, tipo_archivo, descripcion, fecha_subida 
                  FROM " . $this->table_name . " 
                  WHERE id_mascota = :id_mascota";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_mascota', $mascotaId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Método para actualizar un registro multimedia existente
    public function update()
    {
        $query = "UPDATE " . $this->table_name . "
                    SET
                        id_mascota = :id_mascota,
                        tipo_archivo = :tipo_archivo,
                        url_archivo = :url_archivo,
                        descripcion = :descripcion
                    WHERE
                        id_multimedia = :id_multimedia";

        $stmt = $this->db->prepare($query);

        // Limpiar y vincular datos
        // Asegurarse de que los IDs sean enteros para el bindParam
        $id_mascota_int = (int)$this->id_mascota;
        $id_multimedia_int = (int)$this->id_multimedia;
        $this->tipo_archivo = htmlspecialchars(strip_tags($this->tipo_archivo));
        $this->url_archivo = htmlspecialchars(strip_tags($this->url_archivo));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));

        $stmt->bindParam(":id_mascota", $id_mascota_int, PDO::PARAM_INT);
        $stmt->bindParam(":tipo_archivo", $this->tipo_archivo);
        $stmt->bindParam(":url_archivo", $this->url_archivo);
        $stmt->bindParam(":descripcion", $this->descripcion);
        $stmt->bindParam(':id_multimedia', $id_multimedia_int, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            throw $e;
        }
        return false;
    }

    // Método para eliminar un registro multimedia por su propio ID
    public function delete()
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_multimedia = :id_multimedia";
        $stmt = $this->db->prepare($query);

        // Limpiar y vincular ID
        $id_multimedia_int = (int)$this->id_multimedia;
        $stmt->bindParam(':id_multimedia', $id_multimedia_int, PDO::PARAM_INT);

        try {
            return $stmt->execute() && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Método para eliminar todos los registros multimedia asociados a una mascota.
     * @param int $mascotaId El ID de la mascota cuyos registros multimedia se eliminarán.
     * @return bool True si la operación fue exitosa, false en caso contrario.
     */
    public function deleteByMascotaId(int $mascotaId): bool
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_mascota = :id_mascota";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_mascota', $mascotaId, PDO::PARAM_INT);

        try {
            return $stmt->execute(); // Devuelve true si la ejecución fue exitosa, incluso si no hay filas afectadas
        } catch (PDOException $e) {
            error_log("Error al eliminar multimedia por ID de mascota: " . $e->getMessage());
            throw $e; // Re-throw para ser capturado en el controlador/modelo superior
        }
    }
}