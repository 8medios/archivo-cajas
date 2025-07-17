<?php


require_once 'config.php';

// Solo aceptar GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Método no permitido');
}

try {
    // Obtener total de pacientes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM patients");
    $stmt->execute();
    $totalPatients = $stmt->fetchColumn();
    
    // Obtener total de cajas únicas
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT box) as total FROM patients");
    $stmt->execute();
    $totalBoxes = $stmt->fetchColumn();
    
    // Obtener últimos registros (opcional)
    $stmt = $pdo->prepare("SELECT dni, box, created_at FROM patients ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recentPatients = $stmt->fetchAll();
    
    jsonResponse(true, 'Estadísticas obtenidas', [
        'total_patients' => $totalPatients,
        'total_boxes' => $totalBoxes,
        'recent_patients' => $recentPatients
    ]);
    
} catch (PDOException $e) {
    error_log("Error en stats.php: " . $e->getMessage());
    jsonResponse(false, 'Error interno del servidor');
}
?>