<?php
/**
 * Alta de incidencias.
 * Muestra el formulario y, al enviarlo, valida los datos con filter_input,
 * inserta la incidencia y redirige usando el ID generado (lastInsertId).
 */
require_once __DIR__ . '/conexion.php';

// Comprobar si se ha enviado el formulario (botón "enviar")
$enviar = filter_input(INPUT_POST, 'enviar', FILTER_SANITIZE_SPECIAL_CHARS);

if ($enviar) {
    // Datos recibidos y validados previamente
    $titulo = filter_input(INPUT_POST, 'titulo', FILTER_SANITIZE_SPECIAL_CHARS);
    $descripcion = filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_SPECIAL_CHARS);
    $prioridad = filter_input(INPUT_POST, 'prioridad', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email_cliente', FILTER_VALIDATE_EMAIL);

    if (!$titulo || !$descripcion || !$prioridad || !$email) {
        die('Datos inválidos. Por favor, revisa el formulario.');
    }

    try {
        $sql = 'INSERT INTO incidencias (titulo, descripcion, prioridad, email_cliente)
                VALUES (:titulo, :descripcion, :prioridad, :email_cliente)';

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':titulo' => $titulo,
            ':descripcion' => $descripcion,
            ':prioridad' => $prioridad,
            ':email_cliente' => $email,
        ]);

        // Recuperar el ID generado automáticamente 
        $nuevoId = $pdo->lastInsertId();

        // Redirigir a la incidencia recién creada
        header('Location: editar_incidencia.php?id=' . $nuevoId);
        exit;

    } catch (PDOException $e) {
        error_log('Error INSERT incidencias: ' . $e->getMessage());
        echo 'No se ha podido registrar la incidencia. Inténtalo de nuevo.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nueva incidencia</title>
    <link rel="stylesheet" href="style.css">

</head>

<body>
    <h1>Nueva incidencia</h1>

    <form action="nueva_incidencia.php" method="post">
        <p>
            <label for="titulo">Título</label><br>
            <input type="text" id="titulo" name="titulo">
        </p>

        <p>
            <label for="descripcion">Descripción</label><br>
            <textarea id="descripcion" name="descripcion" rows="5" cols="50"></textarea>
        </p>

        <p>
            <label for="prioridad">Prioridad</label><br>
            <select id="prioridad" name="prioridad">
                <option value="Baja">Baja</option>
                <option value="Media" selected>Media</option>
                <option value="Alta">Alta</option>
                <option value="Urgente">Urgente</option>
            </select>
        </p>

        <p>
            <label for="email_cliente">Email del cliente</label><br>
            <input type="text" id="email_cliente" name="email_cliente">
        </p>

        <button type="submit" name="enviar" value="1">Registrar incidencia</button>
    </form>

    <p><a href="index.php">Volver al panel</a></p>
</body>

</html>