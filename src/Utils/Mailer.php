<?php
// app/Utils/Mailer.php

namespace App\Utils;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv; // Necesario para cargar variables de entorno si no se cargan globalmente

class Mailer
{
    private $mail;
    private $logger;

    public function __construct()
    {
        // Cargar variables de entorno si no se han cargado ya.
        // Esto es una salvaguarda. Si ya se cargaron en index.php, no hará daño.
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $this->logger = Logger::getInstance(); // Obtener la instancia del Logger

        $this->mail = new PHPMailer(true); // Pasar 'true' habilita excepciones
        try {
            // Configuración del servidor SMTP
            $this->mail->isSMTP();                                            // Usar SMTP
            $this->mail->Host       = $_ENV['MAIL_HOST'];                     // Servidor SMTP
            $this->mail->SMTPAuth   = true;                                   // Habilitar autenticación SMTP
            $this->mail->Username   = $_ENV['MAIL_USERNAME'];                 // Nombre de usuario SMTP
            $this->mail->Password   = $_ENV['MAIL_PASSWORD'];                 // Contraseña SMTP
            $this->mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];               // Habilitar encriptación TLS/SSL
            $this->mail->Port       = (int)$_ENV['MAIL_PORT'];                // Puerto TCP para conectar

            // Remitente
            $this->mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $this->mail->CharSet = 'UTF-8'; // Asegurar que los caracteres especiales se envíen correctamente
            $this->mail->isHTML(true); // Habilitar contenido HTML en el correo
        } catch (Exception $e) {
            $this->logger->error("Error al configurar PHPMailer: {$e->getMessage()}");
            // Puedes decidir si lanzar la excepción o simplemente loguearla.
            // Por ahora, solo logueamos, pero el método sendEmail manejará el fallo.
        }
    }

    /**
     * Envía un correo electrónico.
     *
     * @param string $toEmail La dirección de correo del destinatario.
     * @param string $toName El nombre del destinatario.
     * @param string $subject El asunto del correo.
     * @param string $body El cuerpo del correo (puede ser HTML).
     * @param string $altBody El cuerpo alternativo en texto plano (para clientes que no soportan HTML).
     * @return bool True si el correo se envió exitosamente, false en caso contrario.
     */
    public function sendEmail(string $toEmail, string $toName, string $subject, string $body, string $altBody = ''): bool
    {
        try {
            $this->mail->clearAddresses(); // Limpiar direcciones anteriores
            $this->mail->addAddress($toEmail, $toName); // Añadir destinatario

            $this->mail->Subject = $subject; // Asunto
            $this->mail->Body    = $body;    // Contenido HTML
            $this->mail->AltBody = $altBody; // Contenido en texto plano

            $this->mail->send();
            $this->logger->info("Correo enviado exitosamente a {$toEmail} con asunto: '{$subject}'");
            return true;
        } catch (Exception $e) {
            $this->logger->error("Error al enviar correo a {$toEmail} (Asunto: '{$subject}'): {$e->getMessage()}");
            return false;
        }
    }
}