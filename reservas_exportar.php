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

$encabezados = ['Check-in', 'Check-out', 'Alojamiento', 'Propietario', 'Plataforma', 'Valor total'];
if (mostrar_split_comision()) {
    $encabezados[] = '75% Propietario';
    $encabezados[] = '25% ' . brand_name();
}
$encabezados[] = 'Notas';
fputcsv($salida, $encabezados, ';');

foreach ($reservas as $r) {
    $fila = [
        (new DateTime($r['fecha_inicio']))->format('d/m/Y'),
        (new DateTime($r['fecha_fin']))->format('d/m/Y'),
        $r['apartamento_nombre'],
        $r['apartamento_propietario'] ?? '',
        plataformaLabel($r['plataforma']),
        $r['valor_total'],
    ];
    if (mostrar_split_comision()) {
        $fila[] = $r['valor_propietario'];
        $fila[] = $r['valor_comision'];
    }
    $fila[] = $r['notas'] ?? '';
    fputcsv($salida, $fila, ';');
}

fputcsv($salida, [], ';');
$filaTotales = ['', '', '', '', 'TOTALES', $totales['total']];
if (mostrar_split_comision()) {
    $filaTotales[] = $totales['propietario'];
    $filaTotales[] = $totales['comision'];
}
$filaTotales[] = '';
fputcsv($salida, $filaTotales, ';');

fclose($salida);
exit;
