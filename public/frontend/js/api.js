// public/frontend/js/api.js 

// Módulo para interactuar con la API de backend. 
// Gestiona las llamadas AJAX y la inclusión del token JWT. 
(function() { // Usamos una IIFE para encapsular el código y evitar variables globales no deseadas
    console.log("DEBUG API: Módulo ApiService cargado."); 

    // URL base de tu API (ajusta esto si tu API no está en /adopciones-api/api/) 
    const API_BASE_URL = 'http://localhost/adopciones-api/api'; 
    
    /** * Realiza una petición AJAX a la API. 
     * @param {string} endpoint La ruta específica de la API (ej. '/mascotas', '/login'). 
     * @param {string} method El método HTTP (GET, POST, PUT, DELETE). 
     * @param {object|null} data Los datos a enviar en la petición (para POST/PUT). 
     * @param {boolean} requiresAuth Indica si la petición requiere un token de autenticación. 
     * @returns {Promise<object>} Una promesa que resuelve con la respuesta JSON de la API. 
     */ 
    async function callApi(endpoint, method, data = null, requiresAuth = false) { 
        const url = `${API_BASE_URL}${endpoint}`; 
        const headers = { 
            'Content-Type': 'application/json' 
        }; 

        console.log(`DEBUG API: callApi - Iniciando petición a ${url} con método ${method}. Requiere autenticación: ${requiresAuth}`); 

        if (requiresAuth) { 
            // ApiService ahora depende de AuthService para obtener el token.
            // Asegúrate de que AuthService esté cargado ANTES de ApiService en index.html.
            if (window.AuthService && typeof window.AuthService.getAuthToken === 'function') {
                const token = window.AuthService.getAuthToken(); 
                if (token) { 
                    headers['Authorization'] = `Bearer ${token}`; 
                    console.log("DEBUG API: Token JWT encontrado y añadido a las cabeceras."); 
                } else { 
                    console.error("DEBUG API: Petición requiere autenticación pero no se encontró token. Redirigiendo a login."); 
                    const errorMessage = (typeof translations !== 'undefined' && translations[currentLanguage] && translations[currentLanguage].unauthorizedError) 
                                             ? translations[currentLanguage].unauthorizedError 
                                             : 'No autorizado: Token no encontrado.'; 
                    
                    if (window.AuthService && typeof window.AuthService.redirectToLogin === 'function') { 
                        window.AuthService.redirectToLogin(); 
                    } else { 
                        console.warn("DEBUG API: AuthService no disponible para redirigir. Forzando recarga a index.html.");
                        window.location.href = 'index.html'; // Fallback si AuthService no está disponible 
                    } 
                    // Rechazar la promesa para detener la ejecución de la llamada API
                    return Promise.reject({ 
                        status: 401, 
                        message: errorMessage, 
                        details: 'Token JWT no presente en el almacenamiento de sesión.' 
                    }); 
                } 
            } else {
                console.error("ERROR API: AuthService no está disponible o getAuthToken no es una función. Esto es crítico para peticiones protegidas.");
                return Promise.reject({ 
                    status: 500, 
                    message: 'Error de configuración del cliente: Servicio de autenticación no disponible.',
                    details: 'AuthService global no encontrado o incompleto.'
                });
            }
        } 

        return new Promise((resolve, reject) => { 
            console.log("DEBUG API: Configurando llamada $.ajax..."); 
            $.ajax({ 
                url: url, 
                method: method, 
                headers: headers, 
                data: data ? JSON.stringify(data) : null, // Convertir datos a JSON string 
                dataType: 'json', // Esperar una respuesta JSON 
                
                success: function(response) { 
                    console.log("DEBUG API: Petición AJAX exitosa (HTTP 2xx). Respuesta recibida:", response); 
                    // MODIFICACIÓN CLAVE AQUÍ:
                    // Si la API devuelve un status 'error', lo manejamos como un rechazo lógico.
                    // Si devuelve 'info' o 'success'/'éxito', lo resolvemos.
                    if (response.status === 'error') { 
                        console.warn(`DEBUG API: La API devolvió un estado 'error' en la respuesta exitosa:`, response.message); 
                        reject({ 
                            status: 200, // Mantener el status HTTP 200 para indicar que la comunicación fue exitosa 
                            message: response.message || (typeof translations !== 'undefined' && translations[currentLanguage] ? translations[currentLanguage].generalError : 'Error general de la API.'), 
                            details: response 
                        }); 
                    } else { 
                        // Tanto 'success', 'éxito' como 'info' son respuestas válidas para resolver la promesa.
                        // El componente que llama a la API decidirá cómo manejar 'info'.
                        console.log(`DEBUG API: La API devolvió un estado '${response.status}'. Resolviendo promesa.`); 
                        resolve(response); 
                    } 
                }, 
                error: function(jqXHR, textStatus, errorThrown) { 
                    console.error("DEBUG API: Petición AJAX fallida (HTTP error).", { jqXHR, textStatus, errorThrown }); 
                    let errorMessage = (typeof translations !== 'undefined' && translations[currentLanguage] && translations[currentLanguage].generalError) 
                                             ? translations[currentLanguage].generalError 
                                             : 'Ocurrió un error inesperado.'; 
                    let errorDetails = jqXHR.responseText; 

                    try { 
                        const errorResponse = JSON.parse(jqXHR.responseText); 
                        errorMessage = errorResponse.message || errorMessage; 
                    } catch (e) { 
                        errorMessage = `${textStatus}: ${errorThrown}`; 
                    } 
                    
                    reject({ 
                        status: jqXHR.status, 
                        message: errorMessage, 
                        details: errorDetails 
                    }); 
                } 
            }); 
            console.log("DEBUG API: Llamada $.ajax iniciada."); 
        }); 
    } 

    // Exportar las funciones públicas del módulo
    // Usamos 'remove' en lugar de 'delete' para evitar conflictos con la palabra reservada.
    window.ApiService = { 
        get: (endpoint, requiresAuth = true) => callApi(endpoint, 'GET', null, requiresAuth), 
        post: (endpoint, data, requiresAuth = true) => callApi(endpoint, 'POST', data, requiresAuth), 
        put: (endpoint, data, requiresAuth = true) => callApi(endpoint, 'PUT', data, requiresAuth), 
        remove: (endpoint, requiresAuth = true) => callApi(endpoint, 'DELETE', null, requiresAuth) // <<-- ¡IMPORTANTE! Cambiado a 'remove'
    }; 

    console.log("DEBUG API: window.ApiService asignado y disponible. Contenido:", window.ApiService);
    console.log("DEBUG API: typeof window.ApiService.remove:", typeof window.ApiService.remove);
})();
