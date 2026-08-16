<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$filtro = obtener_reservas_filtradas($pdo);
$reservas = $filtro['reservas'];
$totales = $filtro['totales'];

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="reservas_' . date('Y-m-d_His') . '.csv"');

echo "\xEF\xBB\xBF"; // BOM para que Excel reconozca UTF-8 correctamente

$salida = fopen('php://output', 'w');

fputcsv($salida, ['Check-in', 'Check-out', 'Alojamiento', 'Propietario', 'Plataforma', 'Valor total', '75% Propietario', '25% ' . brand_name(), 'Notas'], ';');

foreach ($reservas as $r) {
    fputcsv($salida, [
        (new DateTime($r['fecha_inicio']))->format('d/m/Y'),
        (new DateTime($r['fecha_fin']))->format('d/m/Y'),
        $r['apartamento_nombre'],
        $r['apartamento_propietario'] ?? '',
        plataformaLabel($r['plataforma']),
        $r['valor_total'],
        $r['valor_propietario'],
        $r['valor_comision'],
        $r['notas'] ?? '',
    ], ';');
}

fputcsv($salida, [], ';');
fputcsv($salida, ['', '', '', '', 'TOTALES', $totales['total'], $totales['propietario'], $totales['comision'], ''], ';');

fclose($salida);
exit;
