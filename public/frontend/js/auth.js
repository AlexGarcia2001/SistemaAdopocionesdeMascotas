// public/frontend/js/auth.js

(function(window) {
    console.log("DEBUG AUTH: Módulo AuthService cargado.");

    const AUTH_TOKEN_KEY = 'authToken'; // Clave para el token en sessionStorage
    const USER_DATA_KEY = 'userData';   // Clave para los datos del usuario en sessionStorage
    const LOGIN_PAGE_FILE = 'index.html'; // Definir la ruta de la página de login

    const AuthService = {
        /**
         * Guarda el token de autenticación y los datos del usuario en sessionStorage.
         * @param {string} token El token JWT recibido del servidor.
         * @param {object} userData Los datos del usuario (ej. id, username, id_rol).
         */
        setAuthData: function(token, userData) {
            console.log("DEBUG AUTH: Guardando token y datos de usuario en sessionStorage.");
            sessionStorage.setItem(AUTH_TOKEN_KEY, token);
            sessionStorage.setItem(USER_DATA_KEY, JSON.stringify(userData));
        },

        /**
         * Obtiene el token de autenticación de sessionStorage.
         * @returns {string|null} El token si existe, de lo contrario null.
         */
        getAuthToken: function() {
            return sessionStorage.getItem(AUTH_TOKEN_KEY);
        },

        /**
         * Obtiene los datos del usuario de sessionStorage.
         * @returns {object|null} Los datos del usuario si existen, de lo contrario null.
         */
        getUserData: function() {
            const userDataString = sessionStorage.getItem(USER_DATA_KEY);
            return userDataString ? JSON.parse(userDataString) : null;
        },

        /**
         * Obtiene los datos del usuario autenticado.
         * Es un alias para getUserData para mayor claridad en el código.
         * @returns {object|null} Los datos del usuario si existen, de lo contrario null.
         */
        getAuthenticatedUser: function() {
            return this.getUserData();
        },

        /**
         * Elimina el token de autenticación y los datos del usuario de sessionStorage.
         */
        clearAuthData: function() {
            console.log("DEBUG AUTH: Limpiando token y datos de usuario de sessionStorage.");
            sessionStorage.removeItem(AUTH_TOKEN_KEY);
            sessionStorage.removeItem(USER_DATA_KEY);
        },

        /**
         * Intenta iniciar sesión con las credenciales proporcionadas.
         * @param {string} username El nombre de usuario (que en el frontend es el email).
         * @param {string} password La contraseña.
         */
        loginUser: async function(username, password) {
            console.log("DEBUG AUTH: Iniciando loginUser para usuario:", username);
            console.log("DEBUG AUTH: Credenciales a enviar (usuario, contraseña):", username, password);
            try {
                console.log("DEBUG AUTH: Llamando a ApiService.post para /login...");
                const loginData = { email: username, password: password }; 
                console.log("DEBUG AUTH: Datos exactos enviados a /login:", loginData);
                // La llamada a /login NO requiere autenticación (tercer parámetro en false)
                const response = await window.ApiService.post('/login', loginData, false); 
                console.log("DEBUG AUTH: Respuesta de /login recibida:", response);
                
                if ((response.status === 'success' || response.status === 'éxito') && response.token && response.user) {
                    console.log("DEBUG AUTH: Login exitoso. Guardando token y datos de usuario en sessionStorage.");
                    this.setAuthData(response.token, response.user); // AHORA AuthService guarda directamente

                    Swal.fire({
                        icon: 'success',
                        title: translations[currentLanguage].loginSuccess,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        console.log("DEBUG AUTH: Redirigiendo después de login exitoso.");
                        this.checkAuthAndRedirect(); 
                    });
                } else {
                    console.warn("DEBUG AUTH: Login fallido según la respuesta de la API (status no es 'success'/'éxito' o falta token/user).");
                    Swal.fire({
                        icon: 'error',
                        title: translations[currentLanguage].loginError,
                        text: response.message || translations[currentLanguage].generalError
                    });
                }
            } catch (error) {
                console.error("DEBUG AUTH: Error en loginUser (catch block):", error);
                Swal.fire({
                    icon: 'error',
                    title: translations[currentLanguage].loginError,
                    text: error.message || translations[currentLanguage].generalError
                });
            }
        },

        /**
         * Intenta registrar un nuevo usuario.
         * @param {object} userData Los datos del usuario a registrar.
         */
        registerUser: async function(userData) {
            console.log("DEBUG AUTH: Iniciando registerUser para datos:", userData);
            try {
                console.log("DEBUG AUTH: Llamando a ApiService.post para /usuarios/register...");
                const response = await window.ApiService.post('/usuarios/register', userData, false); 
                console.log("DEBUG AUTH: Respuesta de /usuarios/register recibida:", response);
                
                if (response.status === 'success' || response.status === 'éxito') {
                    console.log("DEBUG AUTH: Registro exitoso. Mostrando mensaje y redirigiendo.");
                    Swal.fire({
                        icon: 'success',
                        title: translations[currentLanguage].registrationSuccess,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        window.loadComponent('login'); 
                    });
                } else {
                    console.warn("DEBUG AUTH: Registro fallido según la respuesta de la API (status no es 'success' ni 'éxito').");
                    Swal.fire({
                        icon: 'error',
                        title: translations[currentLanguage].registrationError,
                        text: response.message || translations[currentLanguage].generalError
                    });
                }
            } catch (error) {
                console.error("DEBUG AUTH: Error en registerUser (catch block):", error);
                Swal.fire({
                    icon: 'error',
                    title: translations[currentLanguage].registrationError,
                    text: error.message || translations[currentLanguage].generalError
                });
            }
        },

        /**
         * Cierra la sesión del usuario.
         */
        logoutUser: function() {
            console.log("DEBUG AUTH: Ejecutando logoutUser.");
            this.clearAuthData(); // AHORA AuthService limpia directamente
            Swal.fire({
                icon: 'info',
                title: 'Sesión cerrada',
                showConfirmButton: false,
                timer: 1000
            }).then(() => {
                // Forzar una recarga completa para asegurar que se vaya al login
                window.location.href = LOGIN_PAGE_FILE; 
            });
        },

        /**
         * Verifica si el usuario está autenticado y redirige según su rol.
         * Se llama al cargar una página que requiere autenticación.
         */
        checkAuthAndRedirect: function() {
            console.log("DEBUG AUTH: Ejecutando checkAuthAndRedirect.");
            const token = this.getAuthToken(); 
            const userData = this.getUserData(); 

            // Obtener el pathname actual y normalizarlo para la comparación
            const currentPathname = window.location.pathname;
            console.log("DEBUG AUTH: Current pathname:", currentPathname);
            console.log("DEBUG AUTH: LOGIN_PAGE_FILE:", LOGIN_PAGE_FILE);

            // Determinar si la URL actual es la de la página de login
            const isOnLoginPageURL = currentPathname.endsWith(LOGIN_PAGE_FILE) || 
                                     currentPathname.endsWith('/') || 
                                     currentPathname.endsWith('/frontend/'); 

            console.log("DEBUG AUTH: ¿La URL actual es la de la página de login?", isOnLoginPageURL);

            if (token && userData) {
                console.log("DEBUG AUTH: Token y datos de usuario encontrados. Usuario autenticado.");
                try {
                    if (isOnLoginPageURL) {
                        console.log("DEBUG AUTH: Autenticado y la URL es la de la página de login. Redirigiendo a dashboard.");
                        if (userData.id_rol == 1) {
                            window.loadComponent('dashboard-admin');
                        } else {
                            window.loadComponent('dashboard-user');
                        }
                    } else {
                        console.log("DEBUG AUTH: Usuario autenticado y la URL NO es la de la página de login. Continuar en la página actual.");
                    }
                } catch (e) {
                    console.error("DEBUG AUTH: Error al parsear datos de usuario o determinar rol:", e);
                    this.logoutUser(); 
                }
            } else {
                console.log("DEBUG AUTH: No se encontraron token o datos de usuario. Usuario NO autenticado.");
                // Si el usuario NO está autenticado, SIEMPRE cargamos el componente 'welcome'
                // Esto asegura que el formulario de login se muestre.
                console.log("DEBUG AUTH: Cargando componente 'welcome' (página de login).");
                window.loadComponent('welcome'); 
            }
        }
    };

    // Exponer AuthService globalmente
    window.AuthService = AuthService;

})(window);
