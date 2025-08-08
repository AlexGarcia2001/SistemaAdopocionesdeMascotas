<?php

namespace App\Models;

use App\DB\Connection; // Asegúrate de que la ruta a tu clase de conexión sea correcta
use PDO;
use PDOException;

class SeguimientoPostAdopcion
{
    private $conn;
    private $table_name = "seguimientos_post_adopcion";

    // Propiedades del objeto (corresponden a las columnas de la tabla)
    public $id_seguimiento;
    public $id_mascota;
    public $id_usuario_adoptante;
    public $fecha_seguimiento;
    public $tipo_seguimiento;
    public $observaciones;
    public $estado_mascota;
    public $recomendaciones;
    public $creado_por_id_usuario;
    public $fecha_creacion;
    public $fecha_actualizacion;

    /**
     * Constructor que inicializa la conexión a la base de datos.
     */
    public function __construct()
    {
        $this->conn = Connection::getInstance()->getConnection();
    }

    /**
     * Crea un nuevo registro de seguimiento post-adopción en la base de datos.
     * @return bool True si se creó exitosamente, false en caso contrario.
     */
    public function create(): bool
    {
        $query = "INSERT INTO " . $this->table_name . "
                  SET
                    id_mascota = :id_mascota,
                    id_usuario_adoptante = :id_usuario_adoptante,
                    fecha_seguimiento = :fecha_seguimiento,
                    tipo_seguimiento = :tipo_seguimiento,
                    observaciones = :observaciones,
                    estado_mascota = :estado_mascota,
                    recomendaciones = :recomendaciones,
                    creado_por_id_usuario = :creado_por_id_usuario";

        $stmt = $this->conn->prepare($query);

        // Limpiar y enlazar los valores
        $this->id_mascota = htmlspecialchars(strip_tags($this->id_mascota));
        $this->id_usuario_adoptante = htmlspecialchars(strip_tags($this->id_usuario_adoptante));
        $this->fecha_seguimiento = htmlspecialchars(strip_tags($this->fecha_seguimiento));
        $this->tipo_seguimiento = htmlspecialchars(strip_tags($this->tipo_seguimiento));
        $this->observaciones = htmlspecialchars(strip_tags($this->observaciones));
        $this->estado_mascota = htmlspecialchars(strip_tags($this->estado_mascota));
        $this->recomendaciones = htmlspecialchars(strip_tags($this->recomendaciones));
        $this->creado_por_id_usuario = htmlspecialchars(strip_tags($this->creado_por_id_usuario));

        $stmt->bindParam(":id_mascota", $this->id_mascota);
        $stmt->bindParam(":id_usuario_adoptante", $this->id_usuario_adoptante);
        $stmt->bindParam(":fecha_seguimiento", $this->fecha_seguimiento);
        $stmt->bindParam(":tipo_seguimiento", $this->tipo_seguimiento);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":estado_mascota", $this->estado_mascota);
        $stmt->bindParam(":recomendaciones", $this->recomendaciones);
        $stmt->bindParam(":creado_por_id_usuario", $this->creado_por_id_usuario);

        try {
            if ($stmt->execute()) {
                // Opcional: Obtener el ID del último insertado si lo necesitas
                $this->id_seguimiento = $this->conn->lastInsertId();
                return true;
            }
        } catch (PDOException $e) {
            error_log("Error al crear seguimiento: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Obtiene todos los registros de seguimiento post-adopción.
     * Incluye información de la mascota y del usuario adoptante.
     * @return PDOStatement|false Statement si la consulta fue exitosa, false en caso contrario.
     */
    public function getAll()
    {
        $query = "SELECT
                    s.id_seguimiento, s.id_mascota, s.id_usuario_adoptante, s.fecha_seguimiento,
                    s.tipo_seguimiento, s.observaciones, s.estado_mascota, s.recomendaciones,
                    s.creado_por_id_usuario, s.fecha_creacion, s.fecha_actualizacion,
                    m.nombre AS mascota_nombre, m.especie AS mascota_especie, m.raza AS mascota_raza,
                    u.nombre_usuario AS adoptante_nombre, u.email AS adoptante_email,
                    uc.nombre_usuario AS creador_seguimiento_nombre
                  FROM
                    " . $this->table_name . " s
                  LEFT JOIN
                    mascotas m ON s.id_mascota = m.id_mascota
                  LEFT JOIN
                    usuarios u ON s.id_usuario_adoptante = u.id_usuario
                  LEFT JOIN
                    usuarios uc ON s.creado_por_id_usuario = uc.id_usuario
                  ORDER BY
                    s.fecha_seguimiento DESC, s.id_seguimiento DESC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error al obtener todos los seguimientos: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene un registro de seguimiento post-adopción por su ID.
     * @param int $id El ID del seguimiento.
     * @return array|false Los datos del seguimiento si se encuentra, false en caso contrario.
     */
    public function getById(int $id)
    {
        $query = "SELECT
                    s.id_seguimiento, s.id_mascota, s.id_usuario_adoptante, s.fecha_seguimiento,
                    s.tipo_seguimiento, s.observaciones, s.estado_mascota, s.recomendaciones,
                    s.creado_por_id_usuario, s.fecha_creacion, s.fecha_actualizacion,
                    m.nombre AS mascota_nombre, m.especie AS mascota_especie, m.raza AS mascota_raza,
                    u.nombre_usuario AS adoptante_nombre, u.email AS adoptante_email,
                    uc.nombre_usuario AS creador_seguimiento_nombre
                  FROM
                    " . $this->table_name . " s
                  LEFT JOIN
                    mascotas m ON s.id_mascota = m.id_mascota
                  LEFT JOIN
                    usuarios u ON s.id_usuario_adoptante = u.id_usuario
                  LEFT JOIN
                    usuarios uc ON s.creado_por_id_usuario = uc.id_usuario
                  WHERE
                    s.id_seguimiento = :id_seguimiento
                  LIMIT 0,1";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_seguimiento', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row;
        } catch (PDOException $e) {
            error_log("Error al obtener seguimiento por ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene registros de seguimiento post-adopción por ID de mascota.
     * @param int $mascotaId El ID de la mascota.
     * @return PDOStatement|false Statement si la consulta fue exitosa, false en caso contrario.
     */
    public function getByMascotaId(int $mascotaId)
    {
        $query = "SELECT
                    s.id_seguimiento, s.id_mascota, s.id_usuario_adoptante, s.fecha_seguimiento,
                    s.tipo_seguimiento, s.observaciones, s.estado_mascota, s.recomendaciones,
                    s.creado_por_id_usuario, s.fecha_creacion, s.fecha_actualizacion,
                    m.nombre AS mascota_nombre, m.especie AS mascota_especie, m.raza AS mascota_raza,
                    u.nombre_usuario AS adoptante_nombre, u.email AS adoptante_email,
                    uc.nombre_usuario AS creador_seguimiento_nombre
                  FROM
                    " . $this->table_name . " s
                  LEFT JOIN
                    mascotas m ON s.id_mascota = m.id_mascota
                  LEFT JOIN
                    usuarios u ON s.id_usuario_adoptante = u.id_usuario
                  LEFT JOIN
                    usuarios uc ON s.creado_por_id_usuario = uc.id_usuario
                  WHERE
                    s.id_mascota = :id_mascota
                  ORDER BY
                    s.fecha_seguimiento DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_mascota', $mascotaId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error al obtener seguimientos por ID de mascota: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene registros de seguimiento post-adopción por ID de usuario adoptante.
     * @param int $usuarioAdoptanteId El ID del usuario adoptante.
     * @return PDOStatement|false Statement si la consulta fue exitosa, false en caso contrario.
     */
    public function getByUsuarioAdoptanteId(int $usuarioAdoptanteId)
    {
        $query = "SELECT
                    s.id_seguimiento, s.id_mascota, s.id_usuario_adoptante, s.fecha_seguimiento,
                    s.tipo_seguimiento, s.observaciones, s.estado_mascota, s.recomendaciones,
                    s.creado_por_id_usuario, s.fecha_creacion, s.fecha_actualizacion,
                    m.nombre AS mascota_nombre, m.especie AS mascota_especie, m.raza AS mascota_raza,
                    u.nombre_usuario AS adoptante_nombre, u.email AS adoptante_email,
                    uc.nombre_usuario AS creador_seguimiento_nombre
                  FROM
                    " . $this->table_name . " s
                  LEFT JOIN
                    mascotas m ON s.id_mascota = m.id_mascota
                  LEFT JOIN
                    usuarios u ON s.id_usuario_adoptante = u.id_usuario
                  LEFT JOIN
                    usuarios uc ON s.creado_por_id_usuario = uc.id_usuario
                  WHERE
                    s.id_usuario_adoptante = :id_usuario_adoptante
                  ORDER BY
                    s.fecha_seguimiento DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id_usuario_adoptante', $usuarioAdoptanteId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error al obtener seguimientos por ID de usuario adoptante: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un registro de seguimiento post-adopción existente.
     * @return bool True si se actualizó exitosamente, false en caso contrario.
     */
    public function update(): bool
    {
        $query = "UPDATE " . $this->table_name . "
                  SET
                    id_mascota = :id_mascota,
                    id_usuario_adoptante = :id_usuario_adoptante,
                    fecha_seguimiento = :fecha_seguimiento,
                    tipo_seguimiento = :tipo_seguimiento,
                    observaciones = :observaciones,
                    estado_mascota = :estado_mascota,
                    recomendaciones = :recomendaciones,
                    creado_por_id_usuario = :creado_por_id_usuario,
                    fecha_actualizacion = CURRENT_TIMESTAMP
                  WHERE
                    id_seguimiento = :id_seguimiento";

        $stmt = $this->conn->prepare($query);

        // Limpiar y enlazar los valores
        $this->id_mascota = htmlspecialchars(strip_tags($this->id_mascota));
        $this->id_usuario_adoptante = htmlspecialchars(strip_tags($this->id_usuario_adoptante));
        $this->fecha_seguimiento = htmlspecialchars(strip_tags($this->fecha_seguimiento));
        $this->tipo_seguimiento = htmlspecialchars(strip_tags($this->tipo_seguimiento));
        $this->observaciones = htmlspecialchars(strip_tags($this->observaciones));
        $this->estado_mascota = htmlspecialchars(strip_tags($this->estado_mascota));
        $this->recomendaciones = htmlspecialchars(strip_tags($this->recomendaciones));
        $this->creado_por_id_usuario = htmlspecialchars(strip_tags($this->creado_por_id_usuario));
        $this->id_seguimiento = htmlspecialchars(strip_tags($this->id_seguimiento)); // ID para la cláusula WHERE

        $stmt->bindParam(":id_mascota", $this->id_mascota);
        $stmt->bindParam(":id_usuario_adoptante", $this->id_usuario_adoptante);
        $stmt->bindParam(":fecha_seguimiento", $this->fecha_seguimiento);
        $stmt->bindParam(":tipo_seguimiento", $this->tipo_seguimiento);
        $stmt->bindParam(":observaciones", $this->observaciones);
        $stmt->bindParam(":estado_mascota", $this->estado_mascota);
        $stmt->bindParam(":recomendaciones", $this->recomendaciones);
        $stmt->bindParam(":creado_por_id_usuario", $this->creado_por_id_usuario);
        $stmt->bindParam(":id_seguimiento", $this->id_seguimiento);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al actualizar seguimiento: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Elimina un registro de seguimiento post-adopción por su ID.
     * @param int $id El ID del seguimiento a eliminar.
     * @return bool True si se eliminó exitosamente, false en caso contrario.
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_seguimiento = :id_seguimiento";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':id_seguimiento', $id, PDO::PARAM_INT);

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error al eliminar seguimiento: " . $e->getMessage());
        }
        return false;
    }
}
