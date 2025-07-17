<?php
// api/patients_by_box.php

require_once 'config.php';

// Solo aceptar GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Método no permitido');
}

try {
    $sql = "SELECT box, COUNT(*) AS patient_count
            FROM patients
            GROUP BY box
            ORDER BY box ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $patientsByBox = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(true, 'Pacientes por caja obtenidos', [
        'patients_by_box' => $patientsByBox
    ]);

} catch (PDOException $e) {
    error_log("Error en patients_by_box.php: " . $e->getMessage());
    jsonResponse(false, 'Error interno del servidor');
}


