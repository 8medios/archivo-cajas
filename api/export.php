<?php
require_once 'config.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=pacientes_export.csv');

$output = fopen('php://output', 'w');
fputcsv($output, ['DNI', 'Caja', 'Fecha de Registro']);

try {
    $sql = "
        SELECT p.dni, p.box, p.created_at
        FROM patients p
        INNER JOIN (
            SELECT MAX(id) as id
            FROM patients
            GROUP BY dni
        ) latest ON p.id = latest.id
        ORDER BY p.dni ASC
    ";

    $stmt = $pdo->query($sql);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['dni'],
            $row['box'],
            date('d/m/Y H:i', strtotime($row['created_at']))
        ]);
    }
} catch (PDOException $e) {
    error_log("Error en export.php: " . $e->getMessage());
    fputcsv($output, ['Error al generar el CSV']);
}

fclose($output);
exit;
?>
