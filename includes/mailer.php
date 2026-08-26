<?php
declare(strict_types=1);

function enviar_notificacion_solicitud(string $emailDestino, array $datos): void
{
    if ($emailDestino === '' || !filter_var($emailDestino, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $asunto = 'Nueva solicitud de reserva - ' . $datos['apartamento_nombre'];

    $cuerpo = "Tienes una nueva solicitud de reserva:\n\n";
    $cuerpo .= "Alojamiento: {$datos['apartamento_nombre']}\n";
    $cuerpo .= "Cliente: {$datos['nombre_cliente']}\n";
    $cuerpo .= "Teléfono: {$datos['telefono']}\n";
    if (!empty($datos['correo'])) {
        $cuerpo .= "Correo: {$datos['correo']}\n";
    }
    $cuerpo .= "Fechas: {$datos['fecha_inicio']} a {$datos['fecha_fin']}\n";
    if (!empty($datos['mensaje'])) {
        $cuerpo .= "Mensaje: {$datos['mensaje']}\n";
    }
    $cuerpo .= "\nRevisa y aprueba o rechaza esta solicitud desde:\n";
    $cuerpo .= APP_URL . "/solicitudes.php\n";

    $dominio = parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost';
    $headers = "From: " . brand_name() . " <no-reply@{$dominio}>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Best-effort: en hosting compartido mail() no siempre está garantizado,
    // así que no bloqueamos el flujo de la solicitud si falla el envío.
    @mail($emailDestino, $asunto, $cuerpo, $headers);
}
