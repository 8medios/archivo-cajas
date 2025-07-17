<?php
// api/register.php - Registrar nuevo paciente

require_once 'config.php';

// Solo aceptar POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(false, 'Datos inválidos');
}

$dni = isset($input['dni']) ? trim($input['dni']) : '';
$box = isset($input['box']) ? trim($input['box']) : '';

// Validar datos
if (empty($dni) || empty($box)) {
    jsonResponse(false, 'DNI y caja son requeridos');
}

if (!validateDNI($dni)) {
    jsonResponse(false, 'DNI inválido. Debe contener solo números (7-8 dígitos)');
}

if (!validateBox($box)) {
    jsonResponse(false, 'Número de caja inválido');
}

try {
    // Verificar si el DNI ya existe
    $stmt = $pdo->prepare("SELECT dni FROM patients WHERE dni = ?");
    $stmt->execute([$dni]);
    
    if ($stmt->fetch()) {
        jsonResponse(false, 'El DNI ya está registrado');
    }
    
    // Insertar nuevo paciente
    $stmt = $pdo->prepare("INSERT INTO patients (dni, box) VALUES (?, ?)");
    $stmt->execute([$dni, $box]);
    
    jsonResponse(true, 'Paciente registrado correctamente');
    
} catch (PDOException $e) {
    error_log("Error en register.php: " . $e->getMessage());
    jsonResponse(false, 'Error interno del servidor');
}
?>