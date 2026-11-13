<?php
/**
 * Panel principal: lista las incidencias activas (las que no están resueltas).
 */
require_once __DIR__ . '/conexion.php';

// --- Lógica: recuperar los datos ---
$incidencias = [];
$error = '';

try {
    $sql = 'SELECT id, titulo, descripcion, prioridad, email_cliente, estado
            FROM incidencias
            WHERE estado <> :estado
            ORDER BY fecha_creacion DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':estado' => 'Resuelta']);

    $incidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Error SELECT incidencias: ' . $e->getMessage());
    $error = 'No se han podido cargar las incidencias. Inténtalo más tarde.';
}

/**
 * Escapa un texto para mostrarlo en HTML.
 * El último parámetro (false) evita volver a codificar las entidades
 * que ya guarda FILTER_SANITIZE_SPECIAL_CHARS al insertar.
 *
 * @param string $texto Texto recuperado de la base de datos.
 * @return string Texto seguro para imprimir en HTML.
 */
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8', false);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Incidencias activas</title>
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <h1>Incidencias activas</h1>

    <p><a href="nueva_incidencia.php">Nueva incidencia</a></p>

    <?php if ($error !== ''): ?>
        <p><?= e($error) ?></p>
    <?php elseif (!$incidencias): ?>
        <p>No hay incidencias activas.</p>
    <?php else: ?>
        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Descripción</th>
                    <th>Prioridad</th>
                    <th>Email del cliente</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($incidencias as $incidencia): ?>
                    <tr>
                        <td><?= e((string) $incidencia['id']) ?></td>
                        <td><?= e($incidencia['titulo']) ?></td>
                        <td><?= e($incidencia['descripcion']) ?></td>
                        <td><?= e($incidencia['prioridad']) ?></td>
                        <td><?= e($incidencia['email_cliente']) ?></td>
                        <td><?= e($incidencia['estado']) ?></td>
                        <td>
                            <a href="editar_incidencia.php?id=<?= e((string) $incidencia['id']) ?>">Editar</a>
                            <a href="asignar_incidencia.php?id=<?= e((string) $incidencia['id']) ?>">Asignar</a>
                            <a href="eliminar_incidencia.php?id=<?= e((string) $incidencia['id']) ?>">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>

</html>