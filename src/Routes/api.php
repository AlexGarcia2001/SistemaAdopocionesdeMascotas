<?php
// src/Routes/api.php
// Definición de las clases de controladores que se usarán en las rutas
use App\Controllers\RolesController;
use App\Controllers\UsersController;
use App\Controllers\MascotasController;
use App\Controllers\ActividadesController;
use App\Controllers\DonacionesController;
use App\Controllers\RefugiosController; 
use App\Controllers\VoluntariosController;
use App\Controllers\HistorialMedicoController;
use App\Controllers\EventoController;
use App\Controllers\GaleriaMultimediaController;
use App\Controllers\NotificacionController;
use App\Controllers\ParticipacionEventoController;
use App\Controllers\SolicitudAdopcionController;
use App\Controllers\SeguimientosPostAdopcionController; // NUEVO: Importar el controlador de seguimientos
// SEGURIDAD: Importar el AuthMiddleware
use App\Middleware\AuthMiddleware;

// Se asume que $requestUri, $requestMethod y $requestData ya están definidos
// y disponibles desde public/index.php donde se incluye este archivo.

// --- LÍNEA DE DEPURACIÓN (MANTENER ACTIVA PARA DIAGNÓSTICO) ---
error_log("DEBUG-API: URI de la solicitud en api.php es: " . $requestUri . " | Método: " . $requestMethod);
error_log("DEBUG-API: Datos de la solicitud en api.php son: " . json_encode($requestData)); // Añadido para depurar el cuerpo
// --- FIN LÍNEA DE DEPURACIÓN ---

// Manejar peticiones OPTIONS (pre-flight CORS)
if ($requestMethod === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    http_response_code(200);
    exit();
}

// Enrutamiento de la API
switch (true) {
    // --- Rutas para AUTENTICACIÓN / LOGIN ---
    // POST /api/login - Esta ruta NO debe estar protegida, es para obtener el token
    case $requestUri === '/api/login' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/login");
        $usersController = new UsersController();
        $usersController->loginUser($requestData); // Pasa $requestData
        break;

    // --- Rutas para USUARIOS ---

    // RUTA NUEVA: POST /api/usuarios/register (Registrar usuario)
    // Esta ruta no requiere autenticación para permitir la creación inicial de usuarios.
    case $requestUri === '/api/usuarios/register' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/usuarios/register (Registro de usuario)");
        $usersController = new UsersController();
        $usersController->createUser($requestData); // El método createUser ya maneja la lógica de creación
        break;

    // GET /api/usuarios (Obtener todos los usuarios)
    // ESTA RUTA ESTABA COMENTADA Y ES NECESARIA PARA 'Gestionar Usuarios'
    case $requestUri === '/api/usuarios' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/usuarios");
        AuthMiddleware::handle(); // Proteger esta ruta
        $usersController = new UsersController();
        $usersController->getAllUsers();
        break;

    // POST /api/usuarios (Crear usuario)
    // Simplificado: Ya no es un parche para GET, es solo para CREATE.
    case $requestUri === '/api/usuarios' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/usuarios (Crear usuario)");
        AuthMiddleware::handle(); // Proteger POST para creación (solo admins pueden crear usuarios desde la gestión)
        $usersController = new UsersController();
        $usersController->createUser($requestData);
        break;

    // GET /api/usuarios/{id}
    case preg_match('/^\/api\/usuarios\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/usuarios/{id}");
        AuthMiddleware::handle(); // Proteger esta ruta
        $id = $matches[1];
        $usersController = new UsersController();
        $usersController->getUserById($id);
        break;

    // PUT /api/usuarios/{id}
    case preg_match('/^\/api\/usuarios\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/usuarios/{id}");
        AuthMiddleware::handle(); // Proteger esta ruta
        $id = $matches[1];
        $usersController = new UsersController();
        $usersController->updateUser($id, $requestData); // Pasa $requestData
        break;

    // DELETE /api/usuarios/{id}
    case preg_match('/^\/api\/usuarios\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/usuarios/{id}");
        AuthMiddleware::handle(); // Proteger esta ruta
        $id = $matches[1];
        $usersController = new UsersController();
        $usersController->deleteUser($id);
        break;

    // PUT /api/usuarios/{id}/password
    case preg_match('/^\/api\/usuarios\/(\d+)\/password$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/usuarios/{id}/password");
        AuthMiddleware::handle(); // Proteger esta ruta
        $id = $matches[1];
        $usersController = new UsersController();
        $usersController->updateUserPassword($id, $requestData); // Pasa $requestData
        break;
    
    // PUT /api/usuarios/{id}/estado
    case preg_match('/^\/api\/usuarios\/(\d+)\/estado$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/usuarios/{id}/estado");
        AuthMiddleware::handle();
        $id = $matches[1];
        $usersController = new UsersController();
        $usersController->updateUserStatus($id, $requestData);
        break;

    // GET /api/usuarios/{id}/rol
    case preg_match('/^\/api\/usuarios\/(\d+)\/rol$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/usuarios/{id}/rol");
        AuthMiddleware::handle();
        $id = $matches[1];
        $usersController = new UsersController();
        $usersController->getUserRole($id);
        break;


    // --- Rutas para ROLES --- (Generalmente protegidas, solo para administradores)
    // GET /api/roles
    case $requestUri === '/api/roles' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/roles");
        AuthMiddleware::handle(); // Proteger esta ruta
        $rolesController = new RolesController();
        $rolesController->getAllRoles();
        break;

    // POST /api/roles
    case $requestUri === '/api/roles' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/roles");
        AuthMiddleware::handle(); // Proteger POST para creación
        $rolesController = new RolesController();
        $rolesController->createRole($requestData); // Pasa $requestData
        break;

    // GET /api/roles/{id}
    case preg_match('/^\/api\/roles\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/roles/{id}");
        AuthMiddleware::handle(); // Proteger esta ruta
        $id = $matches[1];
        $rolesController = new RolesController();
        $rolesController->getRoleById($id);
        break;

    // PUT /api/roles/{id}
    case preg_match('/^\/api\/roles\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/roles/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $rolesController = new RolesController();
        $rolesController->updateRole($id, $requestData); // Pasa $requestData
        break;

    // DELETE /api/roles/{id}
    case preg_match('/^\/api\/roles\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/roles/{id}");
        AuthMiddleware::handle(); // Proteger esta ruta
        $id = $matches[1];
        $rolesController = new RolesController();
        $rolesController->deleteRole($id);
        break;

    // --- Rutas para MASCOTAS ---
    // GET /api/mascotas
    case $requestUri === '/api/mascotas' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/mascotas");
        // No se requiere AuthMiddleware::handle() aquí, ya que es público
        $mascotasController = new MascotasController();
        $mascotasController->getAllMascotas();
        break;

    // POST /api/mascotas (Crear mascota)
    case $requestUri === '/api/mascotas' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/mascotas");
        AuthMiddleware::handle(); // Proteger POST para creación
        $mascotasController = new MascotasController();
        $mascotasController->createMascota($requestData); // Pasa $requestData
        break;

    // GET /api/mascotas/{id}
    case preg_match('/^\/api\/mascotas\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/mascotas/{id}");
        // No se requiere AuthMiddleware::handle() aquí si la información de la mascota es pública
        $id = $matches[1];
        $mascotasController = new MascotasController();
        $mascotasController->getMascotaById($id);
        break;

    // PUT /api/mascotas/{id}
    case preg_match('/^\/api\/mascotas\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/mascotas/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $mascotasController = new MascotasController();
        $mascotasController->updateMascota($id, $requestData);
        break;

    // DELETE /api/mascotas/{id}
    case preg_match('/^\/api\/mascotas\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/mascotas/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $mascotasController = new MascotasController();
        $mascotasController->deleteMascota($id);
        break;

    // --- NUEVAS RUTAS PARA ESTADÍSTICAS DEL USUARIO (GET por ID de usuario) ---
    // GET /api/solicitudes-adopcion/usuario/{id_usuario}
    case preg_match('/^\/api\/solicitudes-adopcion\/usuario\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/solicitudes-adopcion/usuario/{id_usuario}");
        AuthMiddleware::handle(); // Proteger esta ruta, ya que es de un usuario específico
        $userId = $matches[1];
        $solicitudController = new SolicitudAdopcionController();
        $solicitudController->getSolicitudesByUsuario($userId); 
        break;

    // GET /api/mascotas/adoptadas/usuario/{id_usuario}
    case preg_match('/^\/api\/mascotas\/adoptadas\/usuario\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/mascotas/adoptadas/usuario/{id_usuario}");
        AuthMiddleware::handle(); // Proteger esta ruta
        $userId = $matches[1];
        $mascotasController = new MascotasController();
        $mascotasController->getAdoptadasByUsuario($userId); 
        break;
    // --- FIN NUEVAS RUTAS ---

    // --- Rutas para SOLICITUDES DE ADOPCIÓN (existentes) ---
    // GET /api/solicitudes-adopcion
    case $requestUri === '/api/solicitudes-adopcion' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/solicitudes-adopcion");
        AuthMiddleware::handle();
        $solicitudController = new SolicitudAdopcionController();
        $solicitudController->getAllSolicitudesAdopcion();
        break;

    // POST /api/solicitudes-adopcion (Crear solicitud)
    case $requestUri === '/api/solicitudes-adopcion' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/solicitudes-adopcion");
        AuthMiddleware::handle(); // Proteger POST para creación
        $solicitudController = new SolicitudAdopcionController();
        $solicitudController->createSolicitudAdopcion($requestData); // Pasa $requestData
        break;

    // GET /api/solicitudes-adopcion/{id}
    case preg_match('/^\/api\/solicitudes-adopcion\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/solicitudes-adopcion/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $solicitudController = new SolicitudAdopcionController();
        $solicitudController->getSolicitudAdopcionById($id);
        break;

    // PUT /api/solicitudes-adopcion/{id}
    case preg_match('/^\/api\/solicitudes-adopcion\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/solicitudes-adopcion/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $solicitudController = new SolicitudAdopcionController();
        $solicitudController->updateSolicitudAdopcion($id, $requestData);
        break;

    // DELETE /api/solicitudes-adopcion/{id}
    case preg_match('/^\/api\/solicitudes-adopcion\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/solicitudes-adopcion/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $solicitudController = new SolicitudAdopcionController();
        $solicitudController->deleteSolicitudAdopcion($id);
        break;

    // PUT /api/solicitudes-adopcion/{id}/estado
    case preg_match('/^\/api\/solicitudes-adopcion\/(\d+)\/estado$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/solicitudes-adopcion/{id}/estado");
        AuthMiddleware::handle();
        $id = $matches[1];
        $solicitudController = new SolicitudAdopcionController();
        $solicitudController->updateEstadoSolicitud($id, $requestData);
        break;

    // --- Rutas para PARTICIPACIÓN EN EVENTOS ---
    // GET /api/participacionevento
    case $requestUri === '/api/participacionevento' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/participacionevento");
        AuthMiddleware::handle();
        $participacionController = new ParticipacionEventoController();
        $participacionController->getAllParticipacionesEvento();
        break;

    // POST /api/participacionevento
    case $requestUri === '/api/participacionevento' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/participacionevento");
        AuthMiddleware::handle();
        $participacionController = new ParticipacionEventoController();
        $participacionController->createParticipacionEvento($requestData);
        break;

    // GET /api/participacionevento/{id}
    case preg_match('/^\/api\/participacionevento\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/participacionevento/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $participacionController = new ParticipacionEventoController();
        $participacionController->getParticipacionEventoById($id);
        break;

    // PUT /api/participacionevento/{id}
    case preg_match('/^\/api\/participacionevento\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/participacionevento/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $participacionController = new ParticipacionEventoController();
        $participacionController->updateParticipacionEvento($id, $requestData);
        break;

    // DELETE /api/participacionevento/{id}
    case preg_match('/^\/api\/participacionevento\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/participacionevento/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $participacionController = new ParticipacionEventoController();
        $participacionController->deleteParticipacionEvento($id);
        break;

    // --- Rutas para NOTIFICACIONES ---
    // GET /api/notificaciones
    case $requestUri === '/api/notificaciones' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/notificaciones");
        AuthMiddleware::handle();
        $notificacionController = new NotificacionController();
        $notificacionController->getAllNotificaciones();
        break;

    // POST /api/notificaciones
    case $requestUri === '/api/notificaciones' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/notificaciones");
        AuthMiddleware::handle();
        $notificacionController = new NotificacionController();
        $notificacionController->createNotificacion($requestData);
        break;

    // GET /api/notificaciones/{id}
    case preg_match('/^\/api\/notificaciones\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/notificaciones/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $notificacionController = new NotificacionController();
        $notificacionController->getNotificacionById($id);
        break;

    // PUT /api/notificaciones/{id}
    case preg_match('/^\/api\/notificaciones\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/notificaciones/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $notificacionController = new NotificacionController();
        $notificacionController->updateNotificacion($id, $requestData);
        break;

    // DELETE /api/notificaciones/{id}
    case preg_match('/^\/api\/notificaciones\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/notificaciones/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $notificacionController = new NotificacionController();
        $notificacionController->deleteNotificacion($id);
        break;

    // PUT /api/notificaciones/{id}/leida (Marcar una notificación específica como leída)
    case preg_match('/^\/api\/notificaciones\/(\d+)\/leida$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/notificaciones/{id}/leida");
        AuthMiddleware::handle();
        $id = $matches[1];
        $notificacionController = new NotificacionController();
        $notificacionController->markNotificacionAsRead($id);
        break;

    // ¡NUEVA RUTA! Para marcar todas las notificaciones de un usuario como leídas
    // PUT /api/notificaciones/usuario/{id_usuario}/marcar-leidas
    case preg_match('/^\/api\/notificaciones\/usuario\/(\d+)\/marcar-leidas$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/notificaciones/usuario/{id_usuario}/marcar-leidas");
        AuthMiddleware::handle();
        $userId = $matches[1];
        $notificacionController = new NotificacionController();
        $notificacionController->markAllAsReadByUserId($userId);
        break;

    // --- Rutas para GALERÍA MULTIMEDIA ---
    // GET /api/galeriamultimedia (Eliminado guion)
    case $requestUri === '/api/galeriamultimedia' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/galeriamultimedia");
        // No se requiere AuthMiddleware::handle() aquí, ya que es público.
        $galeriaController = new GaleriaMultimediaController();
        $galeriaController->getAllGaleriaMultimedia();
        break;

    // POST /api/galeriamultimedia (Crear registro multimedia - Eliminado guion)
    case $requestUri === '/api/galeriamultimedia' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/galeriamultimedia");
        AuthMiddleware::handle(); // Proteger POST para creación
        $galeriaController = new GaleriaMultimediaController();
        $galeriaController->createGaleriaMultimedia($requestData); // Pasa $requestData
        break;

    // GET /api/galeriamultimedia/{id} (Eliminado guion)
    case preg_match('/^\/api\/galeriamultimedia\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/galeriamultimedia/{id}");
        // No se requiere AuthMiddleware::handle() aquí, ya que es público.
        $id = $matches[1];
        $galeriaController = new GaleriaMultimediaController();
        $galeriaController->getGaleriaMultimediaById($id);
        break;

    // PUT /api/galeriamultimedia/{id} (Eliminado guion)
    case preg_match('/^\/api\/galeriamultimedia\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/galeriamultimedia/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $galeriaController = new GaleriaMultimediaController();
        $galeriaController->updateGaleriaMultimedia($id, $requestData);
        break;

    // DELETE /api/galeriamultimedia/{id} (Eliminado guion)
    case preg_match('/^\/api\/galeriamultimedia\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/galeriamultimedia/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $galeriaController = new GaleriaMultimediaController();
        $galeriaController->deleteGaleriaMultimedia($id);
        break;
    
    // GET /api/galeriamultimedia/mascota/{id_mascota} (Eliminado guion)
    case preg_match('/^\/api\/galeriamultimedia\/mascota\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/galeriamultimedia/mascota/{id_mascota}");
        $id_mascota = $matches[1];
        $galeriaController = new GaleriaMultimediaController();
        $galeriaController->getGaleriaMultimediaByMascotaId($id_mascota);
        break;


    // --- Rutas para EVENTOS ---
    // GET /api/eventos
    case $requestUri === '/api/eventos' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/eventos");
        AuthMiddleware::handle();
        $eventoController = new EventoController();
        $eventoController->getAllEventos();
        break;

    // POST /api/eventos
    case $requestUri === '/api/eventos' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/eventos");
        AuthMiddleware::handle();
        $eventoController = new EventoController();
        $eventoController->createEvento($requestData);
        break;

    // GET /api/eventos/{id}
    case preg_match('/^\/api\/eventos\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/eventos/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $eventoController = new EventoController();
        $eventoController->getEventoById($id);
        break;

    // PUT /api/eventos/{id}
    case preg_match('/^\/api\/eventos\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/eventos/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $eventoController = new EventoController();
        $eventoController->updateEvento($id, $requestData);
        break;

    // DELETE /api/eventos/{id}
    case preg_match('/^\/api\/eventos\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/eventos/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $eventoController = new EventoController();
        $eventoController->deleteEvento($id);
        break;

    // --- Rutas para HISTORIAL MÉDICO ---
    // GET /api/historialmedico (Corregido el guion)
    case $requestUri === '/api/historialmedico' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/historialmedico");
        AuthMiddleware::handle();
        $historialMedicoController = new HistorialMedicoController();
        $historialMedicoController->getAllHistorialMedico();
        break;

    // POST /api/historialmedico (Corregido el guion)
    case $requestUri === '/api/historialmedico' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/historialmedico");
        AuthMiddleware::handle();
        $historialMedicoController = new HistorialMedicoController();
        $historialMedicoController->createHistorialMedico($requestData);
        break;

    // GET /api/historialmedico/{id} (Corregido el guion)
    case preg_match('/^\/api\/historialmedico\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/historialmedico/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $historialMedicoController = new HistorialMedicoController();
        $historialMedicoController->getHistorialMedicoById($id);
        break;

    // PUT /api/historialmedico/{id} (Corregido el guion)
    case preg_match('/^\/api\/historialmedico\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/historialmedico/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $historialMedicoController = new HistorialMedicoController();
        $historialMedicoController->updateHistorialMedico($id, $requestData);
        break;

    // DELETE /api/historialmedico/{id} (Corregido el guion)
    case preg_match('/^\/api\/historialmedico\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/historialmedico/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $historialMedicoController = new HistorialMedicoController();
        $historialMedicoController->deleteHistorialMedico($id);
        break;
    
    // GET /api/historialmedico/mascota/{id_mascota}
    case preg_match('/^\/api\/historialmedico\/mascota\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/historialmedico/mascota/{id_mascota}");
        $id_mascota = $matches[1];
        $historialMedicoController = new HistorialMedicoController();
        $historialMedicoController->getHistorialMedicoByMascotaId($id_mascota);
        break;
    // --- Rutas para VOLUNTARIOS ---
    // GET /api/voluntarios
    case $requestUri === '/api/voluntarios' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/voluntarios");
        AuthMiddleware::handle();
        $voluntariosController = new VoluntariosController();
        $voluntariosController->getAllVoluntarios();
        break;

    // POST /api/voluntarios
    case $requestUri === '/api/voluntarios' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/voluntarios");
        AuthMiddleware::handle();
        $voluntariosController = new VoluntariosController();
        $voluntariosController->createVoluntario($requestData);
        break;

    // GET /api/voluntarios/{id}
    case preg_match('/^\/api\/voluntarios\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/voluntarios/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $voluntariosController = new VoluntariosController();
        $voluntariosController->getVoluntarioById($id);
        break;

    // PUT /api/voluntarios/{id}
    case preg_match('/^\/api\/voluntarios\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/voluntarios/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $voluntariosController = new VoluntariosController();
        $voluntariosController->updateVoluntario($id, $requestData);
        break;

    // DELETE /api/voluntarios/{id}
    case preg_match('/^\/api\/voluntarios\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/voluntarios/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $voluntariosController = new VoluntariosController();
        $voluntariosController->deleteVoluntario($id);
        break;

    // --- Rutas para DONACIONES ---
    // GET /api/donaciones
    case $requestUri === '/api/donaciones' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/donaciones");
        AuthMiddleware::handle();
        $donacionesController = new DonacionesController();
        $donacionesController->getAllDonaciones();
        break;

    // POST /api/donaciones
    case $requestUri === '/api/donaciones' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/donaciones");
        AuthMiddleware::handle();
        $donacionesController = new DonacionesController();
        $donacionesController->createDonacion($requestData);
        break;

    // GET /api/donaciones/{id}
    case preg_match('/^\/api\/donaciones\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/donaciones/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $donacionesController = new DonacionesController();
        $donacionesController->getDonacionById($id);
        break;

    // PUT /api/donaciones/{id}
    case preg_match('/^\/api\/donaciones\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/donaciones/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $donacionesController = new DonacionesController();
        $donacionesController->updateDonacion($id, $requestData);
        break;

    // DELETE /api/donaciones/{id}
    case preg_match('/^\/api\/donaciones\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/donaciones/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $donacionesController = new DonacionesController();
        $donacionesController->deleteDonacion($id);
        break;

    // --- Rutas para REFUGIOS ---
    // GET /api/refugios
    case $requestUri === '/api/refugios' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/refugios");
        // No proteger GET /api/refugios, generalmente es público
        $refugiosController = new RefugiosController(); 
        $refugiosController->getAllRefugios();
        break;

    // POST /api/refugios
    case $requestUri === '/api/refugios' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/refugios");
        AuthMiddleware::handle(); // Proteger POST para creación
        $refugiosController = new RefugiosController(); 
        $refugiosController->createRefugio($requestData);
        break;

    // GET /api/refugios/{id}
    case preg_match('/^\/api\/refugios\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/refugios/{id}");
        // No proteger GET /api/refugios/{id}, generalmente es público
        $id = $matches[1];
        $refugiosController = new RefugiosController(); 
        $refugiosController->getRefugioById($id);
        break;

    // PUT /api/refugios/{id}
    case preg_match('/^\/api\/refugios\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/refugios/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $refugiosController = new RefugiosController(); 
        $refugiosController->updateRefugio($id, $requestData);
        break;

    // DELETE /api/refugios/{id}
    case preg_match('/^\/api\/refugios\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/refugios/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $refugiosController = new RefugiosController(); 
        $refugiosController->deleteRefugio($id);
        break;

    // --- Rutas para ACTIVIDADES ---
    // GET /api/actividades
    case $requestUri === '/api/actividades' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/actividades");
        // Considerar si GET ALL actividades debe ser público o protegido
        $actividadesController = new ActividadesController();
        $actividadesController->getAllActividades();
        break;

    // POST /api/actividades
    case $requestUri === '/api/actividades' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/actividades");
        AuthMiddleware::handle();
        $actividadesController = new ActividadesController();
        $actividadesController->createActividad($requestData);
        break;

    // GET /api/actividades/{id}
    case preg_match('/^\/api\/actividades\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/actividades/{id}");
        // Considerar si GET by ID actividad debe ser público o protegido
        $id = $matches[1];
        $actividadesController = new ActividadesController();
        $actividadesController->getActividadById($id);
        break;

    // PUT /api/actividades/{id}
    case preg_match('/^\/api\/actividades\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/actividades/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $actividadesController = new ActividadesController();
        $actividadesController->updateActividad($id, $requestData);
        break;

    // DELETE /api/actividades/{id}
    case preg_match('/^\/api\/actividades\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/actividades/{id}");
        AuthMiddleware::handle();
        $id = $matches[1];
        $actividadesController = new ActividadesController();
        $actividadesController->deleteActividad($id);
        break;

    // --- NUEVAS RUTAS PARA SEGUIMIENTOS POST-ADOPCIÓN ---
    // Todas estas rutas requieren rol de Administrador para su gestión
    case $requestUri === '/api/seguimientos-post-adopcion' && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/seguimientos-post-adopcion");
        $seguimientosPostAdopcionController = new SeguimientosPostAdopcionController();
        $seguimientosPostAdopcionController->getAllSeguimientos();
        break;
    case preg_match('/^\/api\/seguimientos-post-adopcion\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/seguimientos-post-adopcion/{id}");
        $id = $matches[1];
        $seguimientosPostAdopcionController = new SeguimientosPostAdopcionController();
        $seguimientosPostAdopcionController->getSeguimientoById($id);
        break;
    case preg_match('/^\/api\/seguimientos-post-adopcion\/mascota\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/seguimientos-post-adopcion/mascota/{id_mascota}");
        $mascotaId = $matches[1];
        $seguimientosPostAdopcionController = new SeguimientosPostAdopcionController();
        $seguimientosPostAdopcionController->getSeguimientosByMascotaId($mascotaId);
        break;
    case preg_match('/^\/api\/seguimientos-post-adopcion\/usuario\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'GET':
        error_log("DEBUG: Coincidencia de ruta: GET /api/seguimientos-post-adopcion/usuario/{id_usuario_adoptante}");
        $usuarioAdoptanteId = $matches[1];
        $seguimientosPostAdopcionController = new SeguimientosPostAdopcionController();
        $seguimientosPostAdopcionController->getSeguimientosByUsuarioAdoptanteId($usuarioAdoptanteId);
        break;
    case $requestUri === '/api/seguimientos-post-adopcion' && $requestMethod === 'POST':
        error_log("DEBUG: Coincidencia de ruta: POST /api/seguimientos-post-adopcion");
        $seguimientosPostAdopcionController = new SeguimientosPostAdopcionController();
        $seguimientosPostAdopcionController->createSeguimiento($requestData);
        break;
    case preg_match('/^\/api\/seguimientos-post-adopcion\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'PUT':
        error_log("DEBUG: Coincidencia de ruta: PUT /api/seguimientos-post-adopcion/{id}");
        $id = $matches[1];
        $seguimientosPostAdopcionController = new SeguimientosPostAdopcionController();
        $seguimientosPostAdopcionController->updateSeguimiento($id, $requestData);
        break;
    case preg_match('/^\/api\/seguimientos-post-adopcion\/(\d+)$/', $requestUri, $matches) && $requestMethod === 'DELETE':
        error_log("DEBUG: Coincidencia de ruta: DELETE /api/seguimientos-post-adopcion/{id}");
        $id = $matches[1];
        $seguimientosPostAdopcionController = new SeguimientosPostAdopcionController();
        $seguimientosPostAdopcionController->deleteSeguimiento($id);
        break;
    // FIN NUEVAS RUTAS PARA SEGUIMIENTOS POST-ADOPCIÓN

    default:
        // Manejar rutas no encontradas
        error_log("DEBUG: Ruta no encontrada o método no permitido para URI: " . $requestUri . " | Método: " . $requestMethod);
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['message' => 'Ruta no encontrada o método no permitido', 'status' => 'error']);
        break;
}
