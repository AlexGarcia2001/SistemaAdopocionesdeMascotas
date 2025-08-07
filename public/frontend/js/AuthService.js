// PROMPT: Crear un servicio JavaScript para la gestión de autenticación (AuthService).
//         Debe manejar el inicio de sesión, el registro, el cierre de sesión y la persistencia del token JWT.
//         Debe usar ApiService para comunicarse con el backend.
//         Asegurar que el token se guarde en localStorage y se recupere.
//         Añadir función para obtener el token y el rol del usuario.

(function() {
    const TOKEN_KEY = 'jwt_token';
    const USER_ROLE_KEY = 'user_role'; // Para almacenar el rol del usuario

    /**
     * Guarda el token JWT y el rol del usuario en el almacenamiento local.
     * @param {string} token - El token JWT.
     * @param {number} role - El ID del rol del usuario.
     */
    function setAuthData(token, role) {
        localStorage.setItem(TOKEN_KEY, token);
        localStorage.setItem(USER_ROLE_KEY, role);
        console.log("DEBUG AUTH: Token y rol guardados en localStorage.");
    }

    /**
     * Obtiene el token JWT del almacenamiento local.
     * @returns {string|null} El token JWT si existe, de lo contrario null.
     */
    function getAuthToken() {
        return localStorage.getItem(TOKEN_KEY);
    }

    /**
     * Obtiene el rol del usuario del almacenamiento local.
     * @returns {string|null} El rol del usuario si existe, de lo contrario null.
     */
    function getUserRole() {
        return localStorage.getItem(USER_ROLE_KEY);
    }

    /**
     * Elimina el token y el rol del almacenamiento local (cierra la sesión).
     */
    function clearAuthData() {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_ROLE_KEY);
        console.log("DEBUG AUTH: Token y rol eliminados de localStorage.");
    }

    /**
     * Verifica si el usuario está autenticado (si hay un token JWT).
     * @returns {boolean} True si el usuario está autenticado, false en caso contrario.
     */
    function isAuthenticated() {
        return !!getAuthToken(); // Devuelve true si hay un token, false si no
    }

    /**
     * Realiza una solicitud de inicio de sesión a la API.
     * @param {string} username - El nombre de usuario.
     * @param {string} password - La contraseña.
     * @returns {Promise<object>} Un objeto que indica el éxito y un mensaje.
     */
    async function login(username, password) {
        console.log("DEBUG AUTH: Intentando iniciar sesión para usuario:", username);
        try {
            // Se asume que window.ApiService.post ya está definido y disponible globalmente
            // El tercer parámetro 'false' indica que esta petición de login NO requiere token de autenticación.
            const response = await window.ApiService.post('/login', { usuario: username, contrasena: password }, false); 
            console.log("DEBUG AUTH: Respuesta de login:", response);

            if (response.status === 'success' && response.token) {
                setAuthData(response.token, response.id_rol); // Guardar token y rol
                Swal.fire({
                    icon: 'success',
                    title: translations[currentLanguage].loginSuccess,
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    // Redirigir al dashboard según el rol
                    if (response.id_rol === 1) { // Asumiendo que 1 es el ID para Administrador
                        window.loadComponent('admin-dashboard');
                    } else { // Otros roles o usuarios normales
                        window.loadComponent('user-dashboard');
                    }
                });
                return { success: true, message: response.message };
            } else {
                Swal.fire({
                    icon: 'error',
                    title: translations[currentLanguage].loginError,
                    text: response.message || 'Credenciales inválidas.'
                });
                return { success: false, message: response.message || 'Credenciales inválidas.' };
            }
        } catch (error) {
            console.error("ERROR AUTH: Fallo en el login:", error);
            let errorMessage = translations[currentLanguage].generalError;
            if (error.responseJSON && error.responseJSON.message) {
                errorMessage = error.responseJSON.message;
            } else if (error.statusText) {
                errorMessage = `Error de red: ${error.statusText} (Código: ${error.status || 'N/A'})`;
            } else if (error.message) {
                errorMessage = `Error JavaScript: ${error.message}`;
            }
            Swal.fire({
                icon: 'error',
                title: translations[currentLanguage].generalError,
                text: errorMessage
            });
            return { success: false, message: errorMessage };
        }
    }

    /**
     * Realiza una solicitud de registro de usuario a la API.
     * @param {object} userData - Los datos del usuario a registrar.
     * @returns {Promise<object>} Un objeto que indica el éxito y un mensaje.
     */
    async function register(userData) {
        console.log("DEBUG AUTH: Intentando registrar usuario:", userData.usuario);
        try {
            // El tercer parámetro 'false' indica que esta petición de registro NO requiere token de autenticación.
            const response = await window.ApiService.post('/register', userData, false); 
            console.log("DEBUG AUTH: Respuesta de registro:", response);

            if (response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: translations[currentLanguage].registrationSuccess,
                    text: response.message || 'Usuario registrado exitosamente. Ahora puedes iniciar sesión.'
                }).then(() => {
                    window.loadComponent('login'); // Redirigir al login
                });
                return { success: true, message: response.message };
            } else {
                Swal.fire({
                    icon: 'error',
                    title: translations[currentLanguage].registrationError,
                    text: response.message || 'Error al registrar usuario.'
                });
                return { success: false, message: response.message || 'Error al registrar usuario.' };
            }
        } catch (error) {
            console.error("ERROR AUTH: Fallo en el registro:", error);
            let errorMessage = translations[currentLanguage].generalError;
            if (error.responseJSON && error.responseJSON.message) {
                errorMessage = error.responseJSON.message;
            } else if (error.statusText) {
                errorMessage = `Error de red: ${error.statusText} (Código: ${error.status || 'N/A'})`;
            } else if (error.message) {
                errorMessage = `Error JavaScript: ${error.message}`;
            }
            Swal.fire({
                icon: 'error',
                title: translations[currentLanguage].generalError,
                text: errorMessage
            });
            return { success: false, message: errorMessage };
        }
    }

    /**
     * Cierra la sesión del usuario.
     */
    function logout() {
        console.log("DEBUG AUTH: Cerrando sesión.");
        clearAuthData();
        Swal.fire({
            icon: 'info',
            title: 'Sesión Cerrada',
            text: 'Has cerrado sesión exitosamente.',
            showConfirmButton: false,
            timer: 1500
        }).then(() => {
            window.loadComponent('login'); // Redirigir a la página de login
        });
    }

    // Exportar funciones para que estén disponibles globalmente a través de window.AuthService
    window.AuthService = {
        login,
        register,
        logout,
        isAuthenticated,
        getAuthToken, // Esta es la función clave que ApiService usará
        getUserRole
    };
})();