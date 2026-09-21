<?php
declare(strict_types=1);

function obtener_reservas_filtradas(PDO $pdo): array
{
    $plataformasValidas = ['airbnb', 'booking', 'web'];

    $desde = $_GET['desde'] ?? '';
    $hasta = $_GET['hasta'] ?? '';
    $apartamentoId = $_GET['apartamento_id'] ?? '';
    $plataforma = $_GET['plataforma'] ?? '';

    $where = ['a.user_id = ?'];
    $params = [current_user_id()];

    if ($desde !== '' && DateTime::createFromFormat('Y-m-d', $desde) !== false) {
        $where[] = 'r.fecha_fin >= ?';
        $params[] = $desde;
    }
    if ($hasta !== '' && DateTime::createFromFormat('Y-m-d', $hasta) !== false) {
        $where[] = 'r.fecha_inicio <= ?';
        $params[] = $hasta;
    }
    if ($apartamentoId !== '' && ctype_digit((string) $apartamentoId)) {
        $where[] = 'r.apartamento_id = ?';
        $params[] = $apartamentoId;
    }
    if ($plataforma !== '' && in_array($plataforma, $plataformasValidas, true)) {
        $where[] = 'r.plataforma = ?';
        $params[] = $plataforma;
    }

    $sql = 'SELECT r.*, a.nombre AS apartamento_nombre, a.propietario AS apartamento_propietario
            FROM reservas r
            JOIN apartamentos a ON a.id = r.apartamento_id';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY r.fecha_inicio DESC, r.id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reservas = $stmt->fetchAll();

    $totales = ['total' => 0.0, 'propietario' => 0.0, 'comision' => 0.0];
    foreach ($reservas as $r) {
        $totales['total'] += (float) $r['valor_total'];
        $totales['propietario'] += (float) $r['valor_propietario'];
        $totales['comision'] += (float) $r['valor_comision'];
    }

    return [
        'desde' => $desde,
        'hasta' => $hasta,
        'apartamento_id' => $apartamentoId,
        'plataforma' => $plataforma,
        'reservas' => $reservas,
        'totales' => $totales,
    ];
}
