<?php
// error_log_clear.php - Script para limpiar el log de errores de Apache (compatible con Windows)

// CONFIGURACIÓN: AJUSTA ESTA RUTA SEGÚN TU INSTALACIÓN DE XAMPP
// Por ejemplo, si XAMPP está en C:\xampp, la ruta sería:
$logFilePath = 'C:\\xampp\\apache\\logs\\error.log';
// Si quieres limpiar los logs de PHP en XAMPP, podría ser:
// $logFilePath = 'C:\\xampp\\php\\logs\\php_error_log';
// Asegúrate de usar doble barra invertida (\\) para las rutas de Windows en PHP.

// --- ADVERTENCIA DE SEGURIDAD EXTREMADAMENTE IMPORTANTE ---
// --- Este script NO TIENE AUTENTICACIÓN. Esto es MUY PELIGROSO en producción.
// --- Cualquier persona que conozca esta URL podrá limpiar tu log.
// --- Considera agregar:
// --- 1. Autenticación de usuario.
// --- 2. Restricciones por IP (solo permitir tu IP).
// --- 3. Un token CSRF para peticiones POST.
// --- ESTO ES SOLO PARA USO LOCAL DE DESARROLLO Y CON ALTO RIESGO.

// Asegúrate de que solo se pueda limpiar el log si se envía una petición POST
// (El link en el visor es un GET, lo cual no es seguro. Se recomienda cambiarlo a un formulario POST).
// Por ahora, si es un GET, simplemente redirige de vuelta con una advertencia.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Location: error_log_viewer.php?error=' . urlencode('La limpieza del log debería realizarse mediante una petición POST para mayor seguridad. Por favor, usa el botón de "Limpiar" en el visor.'));
    exit;
}

try {
    // Intentar abrir el archivo en modo escritura ('w') lo truncará a 0 bytes
    // Esto es compatible con Windows y es el método recomendado en PHP.
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