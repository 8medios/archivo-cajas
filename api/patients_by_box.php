<?php

require_once 'config.php';

// Solo aceptar GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Método no permitido');
}

try {
    // Query para obtener el recuento de pacientes por caja
    $sql = "SELECT box, COUNT(*) as patient_count
            FROM patients
            GROUP BY box
            ORDER BY box ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $patientsByBox = $stmt->fetchAll();

    jsonResponse(true, 'Pacientes por caja obtenidos', [
        'patients_by_box' => $patientsByBox
    ]);

} catch (PDOException $e) {
    error_log("Error en patients_by_box.php: " . $e->getMessage());
    jsonResponse(false, 'Error interno del servidor');
}
?>