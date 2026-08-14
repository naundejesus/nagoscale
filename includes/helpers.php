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
