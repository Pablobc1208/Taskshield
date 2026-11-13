<?php
/**
 * Asignación avanzada: asigna una incidencia a un técnico dentro de una transacción.
 */
require_once __DIR__ . '/conexion.php';

/**
 * Escapa un texto para mostrarlo en HTML sin volver a codificar
 * las entidades que ya guarda FILTER_SANITIZE_SPECIAL_CHARS.
 *
 * @param string $texto Texto a mostrar.
 * @return string Texto seguro para imprimir en HTML.
 */
function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8', false);
}

/**
 * Asigna una incidencia a un técnico. Las dos actualizaciones se ejecutan
 * en una transacción: si falla cualquiera de ellas, no se guarda ninguna.
 *
 * @param PDO $pdo          Conexión a la base de datos.
 * @param int $idIncidencia ID de la incidencia a asignar.
 * @param int $idTecnico    ID del técnico que la recibe.
 * @return bool true si se han guardado los dos cambios, false si se ha hecho rollBack.
 */
function asignarIncidencia(PDO $pdo, int $idIncidencia, int $idTecnico): bool
{
    try {
        $pdo->beginTransaction();

        // 1. Pasar la incidencia a 'En Proceso' y asignarle el técnico
        $stmt = $pdo->prepare('UPDATE incidencias
                               SET estado = :estado, tecnico_id = :tecnico_id
                               WHERE id = :id');
        $stmt->execute([
            ':estado' => 'En Proceso',
            ':tecnico_id' => $idTecnico,
            ':id' => $idIncidencia,
        ]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("La incidencia {$idIncidencia} no se ha actualizado.");
        }

        // 2. Sumar una tarea activa al técnico
        $stmt = $pdo->prepare('UPDATE tecnicos
                               SET tareas_activas = tareas_activas + 1
                               WHERE id = :id');
        $stmt->execute([':id' => $idTecnico]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException("El técnico {$idTecnico} no existe.");
        }

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        // Deshacer todo lo hecho en la transacción
        $pdo->rollBack();
        error_log('Error al asignar incidencia: ' . $e->getMessage());
        return false;
    }
}

// --- Lógica ---

// ID de la incidencia recibido por la URL (asignar_incidencia.php?id=5)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die('ID de incidencia no válido.');
}

$msj = '';

// Comprobar si se ha enviado el formulario (botón "enviar")
$enviar = filter_input(INPUT_POST, 'enviar', FILTER_SANITIZE_SPECIAL_CHARS);

if ($enviar) {
    $idTecnico = filter_input(INPUT_POST, 'tecnico_id', FILTER_VALIDATE_INT);

    if (!$idTecnico) {
        $msj = 'Técnico no válido.';
    } elseif (asignarIncidencia($pdo, $id, $idTecnico)) {
        $msj = 'Incidencia asignada correctamente.';
    } else {
        $msj = 'No se ha podido asignar la incidencia. No se ha guardado ningún cambio.';
    }
}

// Verificar que la incidencia existe y recuperar los técnicos
try {
    $stmt = $pdo->prepare('SELECT id, titulo, estado
                           FROM incidencias
                           WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $incidencia = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare('SELECT id, nombre, tareas_activas
                           FROM tecnicos
                           ORDER BY nombre');
    $stmt->execute();
    $tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('Error SELECT asignación: ' . $e->getMessage());
    die('Ha ocurrido un error. Contacta con el soporte.');
}

if (!$incidencia) {
    die('La incidencia no existe.');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Asignar incidencia</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1>Asignar incidencia <?= e((string) $incidencia['id']) ?></h1>

    <p><?= e($msj) ?></p>

    <p>Título: <?= e($incidencia['titulo']) ?></p>
    <p>Estado: <?= e($incidencia['estado']) ?></p>

    <form action="asignar_incidencia.php?id=<?= e((string) $incidencia['id']) ?>" method="post">
        <p>
            <label for="tecnico_id">Técnico</label><br>
            <select id="tecnico_id" name="tecnico_id">
                <?php foreach ($tecnicos as $tecnico): ?>
                    <option value="<?= e((string) $tecnico['id']) ?>">
                        <?= e($tecnico['nombre']) ?> (<?= e((string) $tecnico['tareas_activas']) ?> tareas activas)
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <button type="submit" name="enviar" value="1">Asignar</button>
    </form>

    <p><a href="index.php">Volver al panel</a></p>
</body>

</html>
