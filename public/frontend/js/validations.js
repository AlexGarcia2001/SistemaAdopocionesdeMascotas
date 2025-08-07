// public/frontend/js/validations.js

// PROMPT: Crear un archivo JavaScript (validations.js) para manejar la validación de formularios en el frontend.
//         Debe incluir funciones de validación genéricas (campo requerido, email, contraseña, coincidencia de contraseñas).
//         Debe usar clases de Bootstrap para mostrar feedback de validación (is-invalid, invalid-feedback).
//         Debe ser modular para que sus funciones puedan ser usadas por otros scripts.
//         Hacer el módulo FormValidator accesible globalmente.

/**
 * Módulo de funciones de validación de formularios.
 * Utiliza jQuery y Bootstrap para la gestión de clases de validación.
 */
const FormValidator = (function() {

    /**
     * Muestra un mensaje de validación para un campo específico.
     * @param {jQuery} $element El elemento jQuery del campo de entrada.
     * @param {string} message El mensaje de error a mostrar.
     */
    function showValidationFeedback($element, message) {
        $element.addClass('is-invalid'); // Añade clase de Bootstrap para indicar error
        // Asegúrate de que el div invalid-feedback exista justo después del input
        let $feedbackDiv = $element.next('.invalid-feedback');
        if ($feedbackDiv.length === 0) {
            // Si no existe, lo creamos (esto es una medida de seguridad, debería estar en el HTML)
            $feedbackDiv = $('<div class="invalid-feedback"></div>');
            $element.after($feedbackDiv);
        }
        $feedbackDiv.text(message); // Inserta el mensaje en el div de feedback
    }

    /**
     * Oculta el mensaje de validación para un campo específico.
     * @param {jQuery} $element El elemento jQuery del campo de entrada.
     */
    function hideValidationFeedback($element) {
        $element.removeClass('is-invalid'); // Remueve la clase de error
        $element.next('.invalid-feedback').text(''); // Limpia el mensaje de feedback
    }

    /**
     * Valida si un campo requerido no está vacío.
     * @param {jQuery} $element El elemento jQuery del campo de entrada.
     * @param {string} langKey La clave de traducción para el mensaje de campo requerido.
     * @returns {boolean} True si el campo no está vacío, false de lo contrario.
     */
    function validateRequired($element, langKey = 'requiredField') {
        if ($element.val().trim() === '') {
            showValidationFeedback($element, translations[currentLanguage][langKey]);
            return false;
        }
        hideValidationFeedback($element);
        return true;
    }

    /**
     * Valida si una cadena es un formato de correo electrónico válido.
     * @param {jQuery} $element El elemento jQuery del campo de entrada.
     * @param {string} langKey La clave de traducción para el mensaje de email inválido.
     * @returns {boolean} True si el email es válido, false de lo contrario.
     */
    function validateEmail($element, langKey = 'invalidEmail') {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test($element.val().trim())) {
            showValidationFeedback($element, translations[currentLanguage][langKey]);
            return false;
        }
        hideValidationFeedback($element);
        return true;
    }

    /**
     * Valida si una contraseña cumple con ciertos criterios (ej. longitud mínima).
     * @param {jQuery} $element El elemento jQuery del campo de entrada.
     * @param {number} minLength La longitud mínima requerida para la contraseña.
     * @param {string} langKey La clave de traducción para el mensaje de contraseña inválida.
     * @returns {boolean} True si la contraseña es válida, false de lo contrario.
     */
    function validatePassword($element, minLength = 6, langKey = 'passwordTooShort') {
        if ($element.val().length < minLength) {
            // Asume que 'passwordTooShort' existe en lang.js o usa un mensaje genérico
            showValidationFeedback($element, translations[currentLanguage][langKey] || `La contraseña debe tener al menos ${minLength} caracteres.`);
            return false;
        }
        hideValidationFeedback($element);
        return true;
    }

    /**
     * Valida si dos campos de contraseña coinciden.
     * @param {jQuery} $passwordElement El elemento jQuery del campo de contraseña.
     * @param {jQuery} $confirmPasswordElement El elemento jQuery del campo de confirmación de contraseña.
     * @param {string} langKey La clave de traducción para el mensaje de no coincidencia.
     * @returns {boolean} True si las contraseñas coinciden, false de lo contrario.
     */
    function validatePasswordMatch($passwordElement, $confirmPasswordElement, langKey = 'passwordMismatch') {
        if ($passwordElement.val() !== $confirmPasswordElement.val()) {
            showValidationFeedback($confirmPasswordElement, translations[currentLanguage][langKey]);
            return false;
        }
        hideValidationFeedback($confirmPasswordElement);
        return true;
    }

    // Retorna las funciones públicas del módulo
    return {
        showFeedback: showValidationFeedback,
        hideFeedback: hideValidationFeedback,
        validateRequired: validateRequired,
        validateEmail: validateEmail,
        validatePassword: validatePassword,
        validatePasswordMatch: validatePasswordMatch
    };

})();

// Hacer el módulo FormValidator accesible globalmente
window.FormValidator = FormValidator;