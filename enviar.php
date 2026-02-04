<?php
// Validamos que el formulario venga por el método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Limpieza de seguridad (Evita inyecciones de código)
    $nombre = strip_tags(trim($_POST["Nombre"]));
    $email = filter_var(trim($_POST["Email"]), FILTER_SANITIZE_EMAIL);
    $mensaje = strip_tags(trim($_POST["Mensaje"])); // Agregamos strip_tags aquí también por seguridad

    // 2. Configuración del Destinatario (EL CAMBIO IMPORTANTE)
    $destinatario = "Consultas@nsberazategui.com.ar"; 
    
    $asunto = "Nueva consulta Web de: $nombre";

    // 3. Cuerpo del mensaje
    $contenido = "Has recibido un nuevo mensaje desde el formulario de contacto:\n\n";
    $contenido .= "Nombre: $nombre\n";
    $contenido .= "Email: $email\n\n";
    $contenido .= "Mensaje:\n$mensaje\n";
    $contenido .= "\n--------------------------------------\n";
    $contenido .= "Enviado el " . date('d/m/Y', time());

    // 4. Encabezados (Headers)
    // IMPORTANTE: El 'From' debe tener tu dominio (@nsberazategui.com.ar)
    // Usamos 'no-reply' o el mismo 'Consultas' para que sea legítimo.
    $headers = "From: Consultas@nsberazategui.com.ar\r\n";
    $headers .= "Reply-To: $email\r\n"; // Esto permite que al darle 'Responder' le escribas al paciente
    $headers .= "X-Mailer: PHP/" . phpversion();

    // 5. Enviamos el mail
    if (mail($destinatario, $asunto, $contenido, $headers)) {
        // Éxito: Volvemos al index con la señal de éxito
        header("Location: index.html?enviado=exito");
        exit;
    } else {
        // Error del servidor
        echo "Hubo un error al enviar el mensaje. Por favor intente más tarde.";
    }

} else {
    // Acceso directo denegado
    header("Location: index.html");
    exit;
}
?>