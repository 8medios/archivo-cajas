<?php
// Configuración de la base de datos
$host = 'localhost';
$database = 'archivo_pacientes';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()
    ]));
}

// Headers para API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Función para validar DNI
function validateDNI($dni) {
    $dni = trim($dni);
    
    // Verificar que solo contenga números
    if (!preg_match('/^\d+$/', $dni)) {
        return false;
    }
    
    // Verificar longitud (7-8 dígitos típicamente en Argentina)
    if (strlen($dni) < 7 || strlen($dni) > 8) {
        return false;
    }
    
    return true;
}

// Función para validar número de caja
function validateBox($box) {
    $box = trim($box);
    
    // Verificar que no esté vacío
    if (empty($box)) {
        return false;
    }
    
    // Verificar longitud máxima
    if (strlen($box) > 50) {
        return false;
    }
    
    return true;
}

// Función para respuesta JSON
function jsonResponse($success, $message, $data = null) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}
?>