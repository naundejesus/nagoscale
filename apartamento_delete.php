<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: apartamentos.php');
    exit;
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    try {
        $uploadDir = __DIR__ . '/assets/uploads/apartamentos/';
        $stmt = $pdo->prepare('SELECT archivo FROM apartamento_fotos WHERE apartamento_id = ?');
        $stmt->execute([$id]);
        $archivos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare('DELETE FROM apartamentos WHERE id = ?');
        $stmt->execute([$id]);

        foreach ($archivos as $archivo) {
            $ruta = $uploadDir . $archivo;
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }

        header('Location: apartamentos.php?guardado=1');
        exit;
    } catch (PDOException $e) {
        header('Location: apartamentos.php?error=' . urlencode('No se puede eliminar: tiene reservas asociadas. Elimina primero esas reservas.'));
        exit;
    }
}

header('Location: apartamentos.php');
exit;
