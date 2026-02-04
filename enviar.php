<?php
// enviar.php - Script Seguro con Cloudflare + Honeypot

// --- CONFIGURACIÓN DE SEGURIDAD ---
// PEGA TU SECRET KEY DE CLOUDFLARE ENTRE LAS COMILLAS
$turnstile_secret = '0x4AAAAAACXt1cIxGk_PEYx1AJfd2IeqZEU'; 
// --------------------------------

// 1. Verificar método POST
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // Si intentan entrar directo, los mandamos al inicio
    header("Location: index.html");
    exit;
}

// 2. HONEYPOT: Si el campo oculto 'website_check' tiene algo, es un BOT.
if (!empty($_POST['website_check'])) {
    die("Error de seguridad automático.");
}

// 3. CLOUDFLARE TURNSTILE (Verificación del Captcha)
if (isset($_POST['cf-turnstile-response'])) {
    $token = $_POST['cf-turnstile-response'];
    $ip = $_SERVER['REMOTE_ADDR'];

    // Preguntarle a Cloudflare si el token es válido
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret' => $turnstile_secret,
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

    // Si Cloudflare dice que no es humano:
    if ($response->success != true) {
        echo "<script>alert('Error de seguridad: No se pudo verificar que eres humano. Por favor intenta de nuevo.'); window.location.href='contacto.html';</script>";
        exit;
    }

} else {
    // Si no envió el token del captcha
    echo "<script>alert('Por favor completa la verificación de seguridad.'); window.location.href='contacto.html';</script>";
    exit;
}

// --- SI LLEGAMOS ACÁ, ES UN HUMANO VALIDADO ---

// 4. Limpieza de datos (Sanitización)
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$nombre = clean_input($_POST['Nombre']);
$telefono = clean_input($_POST['Telefono']);
$email = clean_input($_POST['Email']);
$mensaje = clean_input($_POST['Mensaje']);

// Validación básica de campos vacíos
if (empty($nombre) || empty($email) || empty($mensaje) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Datos incompletos o incorrectos.'); window.location.href='contacto.html';</script>";
    exit;
}

// 5. Envío del Correo
$destinatario = "Consultas@nsberazategui.com.ar";
$asunto = "Nueva Consulta Web de: $nombre";

$cuerpo = "Nueva consulta verificada (Sin Spam).\n\n";
$cuerpo .= "Nombre: $nombre\n";
$cuerpo .= "Email: $email\n";
$cuerpo .= "Teléfono: $telefono\n\n";
$cuerpo .= "Mensaje:\n$mensaje\n";
$cuerpo .= "\n--------------------------------------\n";
$cuerpo .= "Enviado el " . date('d/m/Y H:i');

$headers = "From: Web Sanatorio <no-reply@nsberazategui.com.ar>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

if (mail($destinatario, $asunto, $cuerpo, $headers)) {
    // Éxito: Usamos JS para mostrar alerta y redirigir
    echo "<script>alert('¡Mensaje enviado con éxito! Nos pondremos en contacto pronto.'); window.location.href='index.html';</script>";
} else {
    // Error del servidor
    echo "<script>alert('Hubo un error al enviar el mensaje. Por favor intente más tarde.'); window.location.href='contacto.html';</script>";
}
?>