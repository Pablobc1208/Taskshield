<?php
/**
 * Borrado de una incidencia.
 * Recibe el ID por la URL, pide confirmación y elimina la incidencia,
 * comprobando con rowCount() que el borrado se ha realizado.
 */
require_once __DIR__ . '/conexion.php';

// ID de la incidencia recibido por la URL (eliminar_incidencia.php?id=5)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('ID de incidencia no válido.');
}


// Comprobar si se ha confirmado el borrado (botón "enviar")
$enviar = filter_input(INPUT_POST, 'enviar', FILTER_SANITIZE_SPECIAL_CHARS);

if ($enviar) {
    // Borrado confirmado: eliminar la incidencia
    try {
        $sql = 'DELETE FROM incidencias
                WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        // Verificar si se eliminó algo
        if ($stmt->rowCount() > 0) {
            $msj = 'Incidencia eliminada correctamente.';
        } else {
            $msj = 'No se encontró la incidencia.';
        }

    } catch (PDOException $e) {
        error_log('Error DELETE incidencias: ' . $e->getMessage());
        $msj = 'No se ha podido eliminar la incidencia. Inténtalo de nuevo.';
    }

} else {
    // Todavía sin confirmar: recuperar la incidencia para mostrarla
    try {
        $stmt = $pdo->prepare('SELECT id, titulo, estado
                               FROM incidencias
                               WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log('Error SELECT incidencias: ' . $e->getMessage());
        die('Ha ocurrido un error. Contacta con el soporte.');
    }

    if (!$incidencia) {
        die('La incidencia no existe.');
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Eliminar incidencia</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1>Eliminar incidencia</h1>

    <?php if ($enviar): ?>
        <p><?= $msj ?></p>
    <?php else: ?>
        <p>¿Seguro que quieres eliminar esta incidencia?</p>

        <p>ID: <?= $incidencia['id'] ?></p>
        <p>Título: <?= htmlspecialchars($incidencia['titulo'], ENT_QUOTES, 'UTF-8', false) ?></p>
        <p>Estado: <?= $incidencia['estado'] ?></p>

        <form action="eliminar_incidencia.php?id=<?= $incidencia['id'] ?>" method="post">
            <button type="submit" name="enviar" value="1">Sí, eliminar</button>
        </form>
    <?php endif; ?>

    <p><a href="index.php">Volver al panel</a></p>
</body>

</html>