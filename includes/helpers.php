<?php
declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatCOP($value): string
{
    return '$ ' . number_format((float) $value, 0, ',', '.');
}

function asset_version(): string
{
    $ruta = __DIR__ . '/../assets/css/style.css';
    return (string) (@filemtime($ruta) ?: time());
}

function brand_name(): string
{
    return defined('BRAND_NAME') ? BRAND_NAME : 'NagoScale';
}

function brand_tagline(): string
{
    return defined('BRAND_TAGLINE') ? BRAND_TAGLINE : 'Inmuebles por Días';
}

function brand_primary_color(): string
{
    return defined('BRAND_PRIMARY_COLOR') ? BRAND_PRIMARY_COLOR : '#0E57E1';
}

function brand_dark_color(): string
{
    return defined('BRAND_DARK_COLOR') ? BRAND_DARK_COLOR : '#131E41';
}

function brand_darken(string $hex, float $percent = 0.2): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
        return '#' . $hex;
    }
    $r = (int) round(hexdec(substr($hex, 0, 2)) * (1 - $percent));
    $g = (int) round(hexdec(substr($hex, 2, 2)) * (1 - $percent));
    $b = (int) round(hexdec(substr($hex, 4, 2)) * (1 - $percent));
    return sprintf('#%02x%02x%02x', max(0, $r), max(0, $g), max(0, $b));
}

function whatsapp_link(string $numero, string $mensaje = ''): string
{
    $digitos = preg_replace('/\D+/', '', $numero) ?? '';
    if ($digitos !== '' && strlen($digitos) === 10) {
        $digitos = '57' . $digitos; // celular colombiano sin indicativo de país
    }
    $url = 'https://wa.me/' . $digitos;
    if ($mensaje !== '') {
        $url .= '?text=' . rawurlencode($mensaje);
    }
    return $url;
}

function plataformaLabel(string $plataforma): string
{
    $labels = [
        'airbnb' => 'Airbnb',
        'booking' => 'Booking',
        'web' => 'Publicidad web',
    ];
    return $labels[$plataforma] ?? $plataforma;
}

function calcular_valor_estadia(?float $precioNoche, ?float $precioFinSemana, string $fechaInicio, string $fechaFin): ?float
{
    if ($precioNoche === null || $precioNoche <= 0) {
        return null;
    }
    $precioFinSemana = ($precioFinSemana !== null && $precioFinSemana > 0) ? $precioFinSemana : $precioNoche;

    $actual = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    $total = 0.0;

    while ($actual < $fin) {
        $diaSemana = (int) $actual->format('N'); // 1=lunes ... 5=viernes, 6=sábado, 7=domingo
        $total += in_array($diaSemana, [5, 6], true) ? $precioFinSemana : $precioNoche;
        $actual->modify('+1 day');
    }

    return $total;
}

function amenidades_disponibles(): array
{
    return [
        'wifi' => ['label' => 'WiFi', 'icon' => '📶'],
        'piscina' => ['label' => 'Piscina', 'icon' => '🏊'],
        'parqueadero' => ['label' => 'Parqueadero', 'icon' => '🚗'],
        'aire_acondicionado' => ['label' => 'Aire acondicionado', 'icon' => '❄️'],
        'cocina' => ['label' => 'Cocina equipada', 'icon' => '🍳'],
        'tv' => ['label' => 'TV', 'icon' => '📺'],
        'lavadora' => ['label' => 'Lavadora', 'icon' => '🧺'],
        'seguridad' => ['label' => 'Seguridad / portería', 'icon' => '🔒'],
        'jacuzzi' => ['label' => 'Jacuzzi', 'icon' => '💦'],
        'pet_friendly' => ['label' => 'Pet friendly', 'icon' => '🐾'],
    ];
}

function decodificar_amenidades(?string $json): array
{
    if (!$json) {
        return [];
    }
    $lista = json_decode($json, true);
    return is_array($lista) ? $lista : [];
}

function estrellas_html(float $promedio): string
{
    $llenas = (int) round($promedio);
    $llenas = max(0, min(5, $llenas));
    return str_repeat('★', $llenas) . str_repeat('☆', 5 - $llenas);
}

function rating_label(float $promedio): string
{
    if ($promedio >= 4.5) return 'Excepcional';
    if ($promedio >= 4.0) return 'Excelente';
    if ($promedio >= 3.5) return 'Muy bueno';
    if ($promedio >= 3.0) return 'Bueno';
    if ($promedio >= 2.0) return 'Aceptable';
    return 'Bajo';
}
