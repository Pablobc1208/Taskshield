<?php
require_once '/config.php';

try {
    $opciones = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
} catch (PDOException $e) {
    // Registrar el error real en el log del servidor (no mostrarlo)
    error_log('Error de conexión: ' . $e->getMessage());
    // Mostrar al usuario un mensaje genérico y seguro
    die('No se ha podido establecer la conexión. Inténtalo más tarde.');
}
?>