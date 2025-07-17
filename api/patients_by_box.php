<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Subconsulta: quedarse con la entrada más reciente por DNI
    $sql = "
        SELECT p.box, COUNT(*) as patient_count
        FROM patients p
        INNER JOIN (
            SELECT MAX(id) as id
            FROM patients
            GROUP BY dni
        ) latest ON p.id = latest.id
        GROUP BY p.box
        ORDER BY p.box ASC
    ";

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
