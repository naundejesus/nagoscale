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
// Backoff progresivo: el primer bloqueo es corto (limita el daño de un ataque
// de bloqueo intencional contra un tercero); si el patrón de fallos continúa
// tras expirar, el bloqueo escala.
const LOGIN_BLOQUEOS_MINUTOS = [1, 5, 15];

const IP_MAX_INTENTOS = 20;
const IP_VENTANA_MINUTOS = 10;

// IMPORTANTE: no se confía en X-Forwarded-For. Esta app no está desplegada
// detrás de un proxy inverso de confianza verificado (Hostinger sirve PHP
// directo vía Apache/LiteSpeed), así que REMOTE_ADDR es el único origen fiable
// de la IP del cliente. Si en el futuro se despliega detrás de un proxy
// verificado, esta función es el único punto a ajustar.
function ip_cliente(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}

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
    $stmt = $pdo->prepare('SELECT intentos, nivel_bloqueo FROM login_intentos WHERE username = ?');
    $stmt->execute([$username]);
    $fila = $stmt->fetch();
    $intentos = ((int) ($fila['intentos'] ?? 0)) + 1;
    $nivel = (int) ($fila['nivel_bloqueo'] ?? 0);

    $bloqueadoHasta = null;
    if ($intentos >= LOGIN_MAX_INTENTOS) {
        $minutos = LOGIN_BLOQUEOS_MINUTOS[min($nivel, count(LOGIN_BLOQUEOS_MINUTOS) - 1)];
        $bloqueadoHasta = (new DateTime("+{$minutos} minutes"))->format('Y-m-d H:i:s');
        $nivel++;
        $intentos = 0;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO login_intentos (username, intentos, bloqueado_hasta, nivel_bloqueo) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE intentos = VALUES(intentos), bloqueado_hasta = VALUES(bloqueado_hasta), nivel_bloqueo = VALUES(nivel_bloqueo)'
    );
    $stmt->execute([$username, $intentos, $bloqueadoHasta, $nivel]);

    limpiar_intentos_antiguos($pdo);
}

function limpiar_intentos_login(PDO $pdo, string $username): void
{
    $stmt = $pdo->prepare('DELETE FROM login_intentos WHERE username = ?');
    $stmt->execute([$username]);
}

function ip_limitada(PDO $pdo, string $ip): bool
{
    if ($ip === '') {
        return false;
    }
    $stmt = $pdo->prepare('SELECT intentos, ventana_desde FROM login_intentos_ip WHERE ip = ?');
    $stmt->execute([$ip]);
    $fila = $stmt->fetch();
    if (!$fila) {
        return false;
    }
    $ventanaVigente = strtotime((string) $fila['ventana_desde']) >= time() - IP_VENTANA_MINUTOS * 60;
    return $ventanaVigente && (int) $fila['intentos'] >= IP_MAX_INTENTOS;
}

function registrar_intento_ip(PDO $pdo, string $ip): void
{
    if ($ip === '') {
        return;
    }
    $stmt = $pdo->prepare('SELECT intentos, ventana_desde FROM login_intentos_ip WHERE ip = ?');
    $stmt->execute([$ip]);
    $fila = $stmt->fetch();
    $ventanaVigente = $fila && strtotime((string) $fila['ventana_desde']) >= time() - IP_VENTANA_MINUTOS * 60;
    $intentos = $ventanaVigente ? (int) $fila['intentos'] + 1 : 1;
    $ventanaDesde = $ventanaVigente ? $fila['ventana_desde'] : date('Y-m-d H:i:s');

    $stmt = $pdo->prepare(
        'INSERT INTO login_intentos_ip (ip, intentos, ventana_desde) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE intentos = VALUES(intentos), ventana_desde = VALUES(ventana_desde)'
    );
    $stmt->execute([$ip, $intentos, $ventanaDesde]);
}

// Purga oportunista de registros ya inofensivos (evita crecimiento indefinido
// de las tablas). Se ejecuta con baja probabilidad para no añadir una consulta
// extra en cada intento fallido.
function limpiar_intentos_antiguos(PDO $pdo): void
{
    if (random_int(1, 100) > 2) {
        return;
    }
    $pdo->exec("DELETE FROM login_intentos WHERE bloqueado_hasta IS NOT NULL AND bloqueado_hasta < NOW() - INTERVAL 7 DAY");
    $pdo->exec("DELETE FROM login_intentos_ip WHERE ventana_desde < NOW() - INTERVAL 1 DAY");
}
