<?php
require_once __DIR__ . '/conexion.php';

// ID de la incidencia recibido por la URL (editar_incidencia.php?id=5)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('ID de incidencia no válido.');
}

$msj = '';

// Comprobar si se ha enviado el formulario (botón "enviar")
$enviar = filter_input(INPUT_POST, 'enviar', FILTER_SANITIZE_SPECIAL_CHARS);

if ($enviar) {
    $titulo = filter_input(INPUT_POST, 'titulo');
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS);
    $prioridad = filter_input(INPUT_POST, 'prioridad', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email_cliente', FILTER_VALIDATE_EMAIL);
    $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!$titulo || !$descripcion || !$prioridad || !$email || !$estado) {
        die('Datos inválidos. Por favor, revisa el formulario.');
    }

    try {
        $sql = 'UPDATE incidencias
                SET titulo = :titulo,
                    descripcion = :descripcion,
                    prioridad = :prioridad,
                    email_cliente = :email_cliente,
                    estado = :estado
                WHERE id = :id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':prioridad' => $prioridad,
            ':email_cliente' => $email,
            ':estado' => $estado,
            ':id' => $id,
        ]);

        // Verificar filas afectadas
        if ($stmt->rowCount() > 0) {
            $msj = 'Incidencia actualizada correctamente.';
        } else {
            $msj = 'No se ha modificado ningún registro.';
        }

    } catch (PDOException $e) {
        error_log('Error UPDATE incidencias: ' . $e->getMessage());
        $msj = 'No se ha podido actualizar la incidencia. Inténtalo de nuevo.';
    }
}

// Verificar que la incidencia existe y recuperar sus datos
try {
    $stmt = $pdo->prepare('SELECT id, titulo, descripcion, prioridad, email_cliente, estado
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

$prioridades = ['Baja', 'Media', 'Alta', 'Urgente'];
$estados = ['Pendiente', 'En Proceso', 'Resuelta'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar incidencia</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1>Editar incidencia <? $incidencia['id'] ?></h1>
    <p><?= $msj ?></p>
    <form action="editar_incidencia.php?id=<?= $incidencia['id'] ?>" method="post">
        <p>
            <label for="titulo">Título</label><br>
            <input type="text" id="titulo" name="titulo"
                value="<?= htmlspecialchars($incidencia['titulo'], ENT_QUOTES, 'UTF-8') ?>">
        </p>
        <p>
            <label for="descripcion">Descripción</label><br>
            <textarea id="descripcion" name="descripcion" rows="5"
                cols="50"><?= htmlspecialchars($incidencia['descripcion'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </p>
        <p>
            <label for="prioridad">Prioridad</label><br>
            <select id="prioridad" name="prioridad">
                <?php foreach ($prioridades as $opcion): ?>
                    <option value="<?= $opcion ?>" <?= $opcion === $incidencia['prioridad'] ? ' selected' : '' ?>>
                        <?= $opcion ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="email_cliente">Email del cliente</label><br>
            <input type="email" id="email_cliente" name="email_cliente"
                value="<?= htmlspecialchars($incidencia['email_cliente'], ENT_QUOTES, 'UTF-8') ?>">
        </p>
        <p>
            <label for="estado">Estado</label><br>
            <select id="estado" name="estado">
                <?php foreach ($estados as $opcion): ?>
                    <option value="<?= $opcion ?>" <?= $opcion === $incidencia['estado'] ? ' selected' : '' ?>><?= $opcion ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <button type="submit" name="enviar" value="1">Guardar cambios</button>
    </form>
    <p><a href="index.php">Volver al panel</a></p>
</body>

</html>