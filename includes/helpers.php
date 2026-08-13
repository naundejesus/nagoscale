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

function plataformaLabel(string $plataforma): string
{
    $labels = [
        'airbnb' => 'Airbnb',
        'booking' => 'Booking',
        'web' => 'Publicidad web',
    ];
    return $labels[$plataforma] ?? $plataforma;
}
