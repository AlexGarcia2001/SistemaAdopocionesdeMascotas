// public/frontend/js/main.js

/**
 * Función para cargar dinámicamente componentes HTML en el área de contenido.
 * @param {string} componentName El nombre del componente a cargar (ej. 'login', 'register', 'dashboard-admin').
 */
window.loadComponent = async function(componentName) {
    const contentArea = $('#contentArea'); // Tu contenedor principal

    console.log(`DEBUG main.js: Intentando cargar componente: ${componentName}`);

    // Paso 1: Notificar al componente actual que va a ser descargado.
    // Esto permite que el script del componente anterior limpie sus propios listeners.
    const currentComponentName = contentArea.data('loaded-component-name');
    if (currentComponentName) {
        console.log(`DEBUG main.js: Desencadenando evento 'componentUnloaded' para: ${currentComponentName}`);
        // Disparar un evento genérico 'componentUnloaded' para que cada componente se limpie a sí mismo.
        $(document).trigger('componentUnloaded', [currentComponentName]);
    }

    // Paso 2: Limpiar completamente el contenido HTML del contenedor principal.
    // Esto elimina todos los elementos y sus listeners asociados que no fueron limpiados por el paso 1.
    contentArea.empty();
    console.log(`DEBUG main.js: Contenido del contenedor principal limpiado.`);

    try {
        // Generar un timestamp para evitar la caché del navegador.
        const cacheBuster = new Date().getTime();
        const componentHtmlUrl = `./templates/${componentName}.html?v=${cacheBuster}`; 
        
        // Usamos fetch y async/await para una carga más moderna y limpia.
        const response = await fetch(componentHtmlUrl);
        if (!response.ok) {
            throw new Error(`Componente '${componentName}.html' no encontrado o error de red: ${response.status}`);
        }
        const html = await response.text();

        // Paso 3: Parsear el nuevo HTML y extraer los scripts.
        // Usamos un DOMParser para una manipulación más robusta del HTML y scripts.
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const scripts = doc.querySelectorAll('script');
        
        // Eliminar los scripts del HTML antes de inyectarlo para evitar doble ejecución.
        scripts.forEach(script => script.remove());

        // Inyectar el contenido HTML (sin los scripts originales) en el área designada.
        contentArea.html(doc.body.innerHTML); // Usamos doc.body.innerHTML para obtener solo el contenido del body.
        // Guardar el nombre del componente que acaba de ser cargado en un atributo de datos.
        contentArea.data('loaded-component-name', componentName);

        // Paso 4: Re-ejecutar los scripts dinámicamente.
        // Esto es CRÍTICO para que el JavaScript de la nueva página se inicialice.
        scripts.forEach(originalScript => {
            const newScript = document.createElement('script');
            if (originalScript.src) {
                // Para scripts externos (con src), establecer src y añadir al head.
                // Esto es importante para scripts de utilidades globales como ApiService o AuthService.
                newScript.src = originalScript.src;
                // Si el script ya existe en el head, lo removemos y lo volvemos a añadir para forzar la ejecución.
                const existingScript = document.querySelector(`script[src="${originalScript.src}"]`);
                if (existingScript) {
                    existingScript.remove();
                }
                newScript.onload = () => console.log(`DEBUG main.js: Script externo cargado: ${newScript.src}`);
                newScript.onerror = (e) => console.error(`ERROR main.js: Fallo al cargar script externo: ${newScript.src}`, e);
                document.head.appendChild(newScript);
            } else {
                // Para scripts inline, establecer textContent y añadir al contentArea.
                // Esto asegura que se ejecuten en el contexto del componente.
                newScript.textContent = originalScript.textContent;
                contentArea.append(newScript); 
                console.log(`DEBUG main.js: Script inline ejecutado para ${componentName}.`);
            }
        });

        // Volver a aplicar traducciones después de que el nuevo contenido y scripts se hayan cargado y ejecutado.
        if (typeof applyTranslations === 'function') {
            applyTranslations();
            console.log(`DEBUG main.js: Traducciones reaplicadas para ${componentName}.`);
        } else {
            console.warn("WARN main.js: applyTranslations no está definida. Asegúrate de que lang.js se cargue correctamente.");
        }

        console.log(`DEBUG main.js: Componente ${componentName} cargado exitosamente.`);

    } catch (error) {
        console.error(`ERROR main.js: Fallo al cargar el componente ${componentName}:`, error);
        // Mostrar un mensaje de error visible al usuario.
        contentArea.html(`<div class="alert alert-danger text-center" role="alert">Error al cargar la página: ${componentName}. Por favor, inténtalo de nuevo. Detalles: ${error.message}</div>`);
        
        // Si falla la carga de un componente importante (como dashboard), redirigir a la página de inicio.
        if (componentName.includes('dashboard')) {
            if (window.AuthService && typeof window.AuthService.checkAuthAndRedirect === 'function') {
                window.AuthService.checkAuthAndRedirect();
            } else {
                window.location.href = 'index.html'; // Fallback si AuthService no está listo.
            }
        }
    }
};

// --- Event Listeners para botones de navegación globales ---
// Estos eventos se adjuntan al 'document' para que funcionen con elementos cargados dinámicamente.
$(document).on('click', '#loginBtn', function() {
    console.log("--- TEST: Clic en el botón de login detectado en main.js ---");
    window.loadComponent('login');
});

$(document).on('click', '#registerBtn', function() {
    console.log("DEBUG: Botón 'Registrarse' clicado en main.js (delegado).");
    window.loadComponent('register');
});

// Listener para enlaces de navegación que cargan componentes (ej. desde el navbar)
$(document).on('click', '.nav-link-component', function(e) {
    e.preventDefault(); // Evitar la navegación por defecto
    const componentName = $(this).data('component'); // Obtener el nombre del componente del atributo data-component
    if (componentName) {
        window.loadComponent(componentName);
    }
});


// Asegurarse de que el DOM esté completamente cargado antes de ejecutar scripts
$(document).ready(function() {
    console.log("DEBUG: main.js ha cargado y el DOM está listo.");

    // Aplicar las traducciones iniciales al cargar la página
    applyTranslations();
    console.log("DEBUG: Traducciones aplicadas.");

    // Event listener para el cambio de idioma
    $('#languageSelect').on('change', function() {
        const selectedLang = $(this).val();
        setLanguage(selectedLang);
        console.log(`DEBUG: Idioma cambiado a: ${selectedLang}`);
    });
});
