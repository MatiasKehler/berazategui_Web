<?php
// enviar.php - Versión SMTP Autenticado (Con PHPMailer + Cloudflare)
// ---------------------------------------------------------

// 1. CARGAMOS PHPMAILER
// (Asegurate que la carpeta 'PHPMailer' esté subida junto a este archivo)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// 2. CONFIGURACIÓN
// Tu clave secreta de Cloudflare (La mantuve igual)
define('TURNSTILE_SECRET', '0x4AAAAAACXt1cIxGk_PEYx1AJfd2IeqZEU'); 

// TUS DATOS DE CORREO (SMTP)
define('SMTP_HOST', 'mail.nsberazategui.com.ar');
define('SMTP_USER', 'Consultas@nsberazategui.com.ar');
define('SMTP_PASS', 'Ns2k10con.sul'); // Contraseña real
define('SMTP_PORT', 465); // Puerto Seguro SSL

// ---------------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // A. SEGURIDAD: HONEYPOT (Trampa anti-robots)
    if (!empty($_POST['website_check'])) {
        die("Error de seguridad (Honeypot detectado).");
    }

    // B. SEGURIDAD: CLOUDFLARE TURNSTILE
    $token = $_POST['cf-turnstile-response'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'];

    // Consultamos a Cloudflare
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => TURNSTILE_SECRET,
        'response' => $token,
        'remoteip' => $ip
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $response = json_decode($result);

    // Si Cloudflare dice que no es humano
    if ($response->success == false) {
        echo "<script>alert('Error de seguridad: Por favor completá el Captcha nuevamente.'); window.history.back();</script>";
        exit;
    }

    // C. RECIBIR DATOS (Respetando tus mayúsculas originales)
    $nombre   = strip_tags(trim($_POST['Nombre'] ?? 'Sin Nombre'));
    $email    = filter_var(trim($_POST['Email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $telefono = strip_tags(trim($_POST['Telefono'] ?? 'Sin teléfono'));
    $mensaje  = strip_tags(trim($_POST['Mensaje'] ?? ''));

    // D. ENVIAR CON PHPMAILER
    $mail = new PHPMailer(true);

    try {
        // Configuración del Servidor
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomentar solo si hay errores graves
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Encriptación SSL forzada
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Remitente y Destinatario
        // OJO: El "From" TIENE que ser tu casilla real para evitar Spam
        $mail->setFrom(SMTP_USER, 'Web Sanatorio Berazategui'); 
        
        // A donde llega el mail (A vos mismo)
        $mail->addAddress(SMTP_USER); 
        
        // Responder a... (Para que al dar "Responder" le escribas al cliente)
        $mail->addReplyTo($email, $nombre);

        // Contenido del Mail
        $mail->isHTML(true);
        $mail->Subject = "Nueva Consulta Web de: $nombre";
        
        $cuerpoHTML = "<h2>Nueva Consulta desde la Web</h2>";
        $cuerpoHTML .= "<p><strong>Nombre:</strong> $nombre</p>";
        $cuerpoHTML .= "<p><strong>Email:</strong> $email</p>";
        $cuerpoHTML .= "<p><strong>Teléfono:</strong> $telefono</p>";
        $cuerpoHTML .= "<hr>";
        $cuerpoHTML .= "<p><strong>Mensaje:</strong><br>" . nl2br($mensaje) . "</p>";
        $cuerpoHTML .= "<br><small>Enviado el " . date('d/m/Y H:i') . "</small>";
        
        $mail->Body = $cuerpoHTML;
        $mail->AltBody = "Nombre: $nombre\nEmail: $email\nTelefono: $telefono\nMensaje: $mensaje";

        $mail->send();
        
        // --- ÉXITO ---
        echo "<script>alert('¡Gracias! Tu mensaje ha sido enviado correctamente.'); window.location.href='index.html';</script>";

    } catch (Exception $e) {
        // --- ERROR ---
        // Mostramos el error técnico para que sepas qué pasó si falla
        $errorMsg = addslashes($mail->ErrorInfo);
        echo "<script>alert('Hubo un error al enviar el mensaje. Detalle: $errorMsg'); window.history.back();</script>";
    }

} else {
    // Si intentan entrar directo al archivo sin enviar formulario
    header("Location: index.html");
    exit;
}
?>