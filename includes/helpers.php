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

function mostrar_split_comision(): bool
{
    return defined('SHOW_COMISION_SPLIT') ? (bool) SHOW_COMISION_SPLIT : true;
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

function whatsapp_icon_svg(int $size = 20): string
{
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="currentColor" style="display:inline-block; vertical-align:middle;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413"/></svg>';
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

function precio_temporada_para_fecha(array $temporadas, string $fechaIso): ?float
{
    foreach ($temporadas as $t) {
        if ($fechaIso >= $t['fecha_inicio'] && $fechaIso <= $t['fecha_fin']) {
            return (float) $t['precio_noche'];
        }
    }
    return null;
}

function calcular_valor_estadia(?float $precioNoche, ?float $precioFinSemana, string $fechaInicio, string $fechaFin, array $temporadas = []): ?float
{
    if ($precioNoche === null || $precioNoche <= 0) {
        return null;
    }
    $precioFinSemana = ($precioFinSemana !== null && $precioFinSemana > 0) ? $precioFinSemana : $precioNoche;

    $actual = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    $total = 0.0;

    while ($actual < $fin) {
        $precioTemporada = $temporadas ? precio_temporada_para_fecha($temporadas, $actual->format('Y-m-d')) : null;
        if ($precioTemporada !== null) {
            $total += $precioTemporada;
            $actual->modify('+1 day');
            continue;
        }
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
