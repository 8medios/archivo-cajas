<?php

$logFilePath = 'C:\\xampp\\apache\\logs\\error.log';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: error_log_viewer.php?error=' . urlencode('La limpieza del log debería realizarse mediante una petición POST para mayor seguridad. Por favor, usa el botón de "Limpiar" en el visor.'));
    exit;
}

try {

    $fileHandle = fopen($logFilePath, 'w');
    if ($fileHandle === false) {
        throw new Exception("No se pudo abrir el archivo de log para escribir. Revisa los permisos de escritura de PHP para el archivo: " . htmlspecialchars($logFilePath));
    }
    fclose($fileHandle);

    // Redirigir de vuelta al visor con un mensaje de éxito
    header('Location: error_log_viewer.php?message=' . urlencode('Log limpiado exitosamente'));
    exit;

} catch (Exception $e) {
    // En caso de error, registrarlo (si es posible) y redirigir con un mensaje de error
    error_log("Error al limpiar el log: " . $e->getMessage()); // Esto lo registrará en el php_error_log
    header('Location: error_log_viewer.php?error=' . urlencode("Error al limpiar el log: " . $e->getMessage()));
    exit;
}
?>