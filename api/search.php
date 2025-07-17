<?php
// api/search.php - Buscar paciente por DNI

require_once 'config.php';

// Solo aceptar GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Método no permitido');
}

// Obtener DNI del query string
$dni = isset($_GET['dni']) ? trim($_GET['dni']) : '';

// Validar DNI
if (empty($dni)) {
    jsonResponse(false, 'DNI es requerido');
}

if (!validateDNI($dni)) {
    jsonResponse(false, 'DNI inválido. Debe contener solo números (7-8 dígitos)');
}

try {
    // Buscar paciente
    $stmt = $pdo->prepare("SELECT dni, box, created_at FROM patients WHERE dni = ?");
    $stmt->execute([$dni]);
    
    $patient = $stmt->fetch();
    
    if ($patient) {
        jsonResponse(true, 'Paciente encontrado', [
            'patient' => [
                'dni' => $patient['dni'],
                'box' => $patient['box'],
                'created_at' => $patient['created_at']
            ]
        ]);
    } else {
        jsonResponse(false, 'Paciente no encontrado');
    }
    
} catch (PDOException $e) {
    error_log("Error en search.php: " . $e->getMessage());
    jsonResponse(false, 'Error interno del servidor');
}
?>