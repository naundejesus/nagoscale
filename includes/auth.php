<?php
declare(strict_types=1);

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function require_login(): void
{
    if (!current_user_id()) {
        header('Location: login.php');
        exit;
    }
}

const LOGIN_MAX_INTENTOS = 5;
const LOGIN_BLOQUEO_MINUTOS = 15;

function login_bloqueado_segundos(PDO $pdo, string $username): ?int
{
    $stmt = $pdo->prepare('SELECT bloqueado_hasta FROM login_intentos WHERE username = ?');
    $stmt->execute([$username]);
    $bloqueadoHasta = $stmt->fetchColumn();
    if (!$bloqueadoHasta) {
        return null;
    }
    $restante = strtotime((string) $bloqueadoHasta) - time();
    return $restante > 0 ? $restante : null;
}

function registrar_intento_fallido(PDO $pdo, string $username): void
{
    $stmt = $pdo->prepare('SELECT intentos FROM login_intentos WHERE username = ?');
    $stmt->execute([$username]);
    $intentos = (int) $stmt->fetchColumn() + 1;

    $bloqueadoHasta = $intentos >= LOGIN_MAX_INTENTOS
        ? (new DateTime('+' . LOGIN_BLOQUEO_MINUTOS . ' minutes'))->format('Y-m-d H:i:s')
        : null;

    $stmt = $pdo->prepare(
        'INSERT INTO login_intentos (username, intentos, bloqueado_hasta) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE intentos = VALUES(intentos), bloqueado_hasta = VALUES(bloqueado_hasta)'
    );
    $stmt->execute([$username, $intentos, $bloqueadoHasta]);
}

function limpiar_intentos_login(PDO $pdo, string $username): void
{
    $stmt = $pdo->prepare('DELETE FROM login_intentos WHERE username = ?');
    $stmt->execute([$username]);
}
