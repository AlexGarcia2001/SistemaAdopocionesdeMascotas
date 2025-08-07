// PROMPT: Crear un servicio JavaScript para interactuar con la API (ApiService).
//         Debe manejar las peticiones GET, POST, PUT, DELETE.
//         Debe incluir el token JWT en el encabezado de autorización para las rutas protegidas.
//         Debe manejar errores de red y respuestas de la API.

(function() {
    // URL base de tu API. ¡Asegúrate de que esta sea la correcta para tu entorno!
    const API_BASE_URL = 'http://localhost/adopciones-api/api'; 

    /**
     * Función genérica para realizar llamadas a la API.
     * @param {string} endpoint - El endpoint específico de la API (ej. '/mascotas', '/login').
     * @param {string} method - El método HTTP (GET, POST, PUT, DELETE).
     * @param {object|null} data - Los datos a enviar en el cuerpo de la petición (para POST/PUT).
     * @param {boolean} requiresAuth - Indica si la petición requiere un token JWT en el encabezado. Por defecto es true.
     * @returns {Promise<object>} Una promesa que se resuelve con la respuesta JSON de la API o se rechaza con un error.
     */
    async function callApi(endpoint, method, data = null, requiresAuth = true) {
        const url = `${API_BASE_URL}${endpoint}`;
        console.log(`DEBUG API: callApi - Iniciando petición a ${url} con método ${method}. Requiere autenticación: ${requiresAuth}`);

        const ajaxOptions = {
            url: url,
            type: method,
            dataType: 'json', // Esperamos una respuesta JSON del servidor
            contentType: 'application/json', // Enviamos datos al servidor como JSON
            data: data ? JSON.stringify(data) : null, // Convierte el objeto de datos a una cadena JSON
            headers: {}, // Aquí se añadirán los encabezados personalizados, como el de autorización
            
            // Esta función se ejecuta antes de enviar la petición AJAX.
            beforeSend: function(xhr) {
                // Si la petición requiere autenticación, intentamos obtener y añadir el token JWT
                if (requiresAuth) {
                    // Asegúrate de que window.AuthService exista y tenga el método getAuthToken()
                    if (window.AuthService && typeof window.AuthService.getAuthToken === 'function') {
                        const token = window.AuthService.getAuthToken(); // Obtener el token del AuthService
                        if (token) {
                            xhr.setRequestHeader('Authorization', 'Bearer ' + token);
                            console.log("DEBUG API: Token JWT añadido al encabezado de autorización.");
                        } else {
                            // Si no hay token pero la ruta lo requiere, registramos una advertencia y redirigimos.
                            console.warn("ADVERTENCIA API: Petición protegida sin token JWT disponible. Redirigiendo a login.");
                            // Forzar cierre de sesión para limpiar cualquier estado de autenticación incompleto
                            window.AuthService.logout(); 
                            // Lanzar un error para detener la ejecución de la promesa actual
                            throw new Error("Acceso denegado. No se proporcionó token de autenticación."); 
                        }
                    } else {
                        // Si AuthService no está disponible, es un error de configuración del frontend
                        console.error("ERROR API: AuthService no está disponible o getAuthToken no es una función. Asegúrate de que AuthService.js se cargue antes que ApiService.js.");
                        throw new Error("Error de configuración del cliente: Servicio de autenticación no disponible.");
                    }
                }
            },
            // Función que se ejecuta si la petición AJAX es exitosa (código de estado 2xx)
            success: function(response) {
                console.log(`DEBUG API: Petición AJAX exitosa (${method} ${endpoint}).`, response);
            },
            // Función que se ejecuta si la petición AJAX falla (código de estado 4xx, 5xx, o error de red)
            error: function(jqXHR, textStatus, errorThrown) {
                console.error(`DEBUG API: Petición AJAX fallida (HTTP error).`, { jqXHR, textStatus, errorThrown });
                
                // Manejo de errores específicos, especialmente para 401 Unauthorized
                if (jqXHR.status === 401) {
                    console.error("ERROR API: 401 Unauthorized. Token inválido o expirado. Redirigiendo a login.");
                    // Forzar cierre de sesión para limpiar datos de sesión y redirigir
                    window.AuthService.logout(); 
                    // Lanzar un error específico para que el catch externo lo maneje
                    throw new Error("Acceso denegado. Su sesión ha expirado o es inválida."); 
                }

                // Intentar extraer un mensaje de error del cuerpo de la respuesta JSON
                let errorMessage = `Error en la petición a ${endpoint}: ${textStatus}`;
                let responseJSON = null;
                try {
                    // Si la respuesta tiene un cuerpo JSON, intentamos parsearlo
                    responseJSON = jqXHR.responseJSON;
                    if (responseJSON && responseJSON.message) {
                        errorMessage = responseJSON.message;
                    } else if (jqXHR.responseText) {
                        // Si no es JSON directamente, intentamos parsear el texto de respuesta
                        const parsedResponse = JSON.parse(jqXHR.responseText);
                        if (parsedResponse && parsedResponse.message) {
                            errorMessage = parsedResponse.message;
                        } else {
                            errorMessage = jqXHR.responseText; // Si no hay mensaje, usamos el texto completo
                        }
                    }
                } catch (e) {
                    // Si la respuesta no es un JSON válido, o no se puede parsear
                    console.warn("ADVERTENCIA API: La respuesta de error no es un JSON válido o está vacía.", jqXHR.responseText);
                    errorMessage = `Error de red o respuesta no JSON: ${jqXHR.status} ${jqXHR.statusText || textStatus}`;
                }
                
                // Crear y lanzar un objeto Error personalizado para que el bloque catch de la función async lo capture
                const error = new Error(errorMessage);
                error.status = jqXHR.status;
                error.statusText = jqXHR.statusText;
                error.responseJSON = responseJSON; // Adjuntar la respuesta JSON si existe
                throw error; // Propagar el error
            }
        };

        console.log("DEBUG API: Configurando llamada $.ajax...");
        // Devolver una promesa para poder usar async/await con $.ajax
        return new Promise((resolve, reject) => {
            $.ajax(ajaxOptions)
                .done(function(response) {
                    resolve(response); // Resuelve la promesa con la respuesta exitosa
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    // La función 'error' de ajaxOptions ya lanza un error.
                    // Aquí simplemente rechazamos la promesa con los detalles del error para que el 'catch'
                    // del llamador (ej. en pets.html) pueda manejarlo.
                    reject({ jqXHR, textStatus, errorThrown }); 
                });
            console.log("DEBUG API: Llamada $.ajax iniciada.");
        });
    }

    // Métodos específicos para cada tipo de petición HTTP
    /**
     * Realiza una petición GET a la API.
     * @param {string} endpoint - El endpoint de la API.
     * @param {boolean} requiresAuth - Si la petición requiere token. Por defecto es true.
     * @returns {Promise<object>}
     */
    async function get(endpoint, requiresAuth = true) {
        return await callApi(endpoint, 'GET', null, requiresAuth);
    }

    /**
     * Realiza una petición POST a la API.
     * @param {string} endpoint - El endpoint de la API.
     * @param {object} data - Los datos a enviar.
     * @param {boolean} requiresAuth - Si la petición requiere token. Por defecto es true.
     * @returns {Promise<object>}
     */
    async function post(endpoint, data, requiresAuth = true) {
        return await callApi(endpoint, 'POST', data, requiresAuth);
    }

    /**
     * Realiza una petición PUT a la API.
     * @param {string} endpoint - El endpoint de la API.
     * @param {object} data - Los datos a enviar.
     * @param {boolean} requiresAuth - Si la petición requiere token. Por defecto es true.
     * @returns {Promise<object>}
     */
    async function put(endpoint, data, requiresAuth = true) {
        return await callApi(endpoint, 'PUT', data, requiresAuth);
    }

    /**
     * Realiza una petición DELETE a la API.
     * Nota: 'delete' es una palabra reservada en JavaScript, por eso se usa 'remove'.
     * @param {string} endpoint - El endpoint de la API.
     * @param {boolean} requiresAuth - Si la petición requiere token. Por defecto es true.
     * @returns {Promise<object>}
     */
    async function remove(endpoint, requiresAuth = true) { 
        return await callApi(endpoint, 'DELETE', null, requiresAuth);
    }

    // Exportar las funciones principales para que estén disponibles globalmente
    window.ApiService = {
        get,
        post,
        put,
        remove 
    };
})();