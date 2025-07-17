<?php
// api/import_csv.php - Importar pacientes desde CSV

require_once 'config.php';

// Solo aceptar POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

// Verificar si se subió un archivo
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'Error al subir el archivo');
}

$file = $_FILES['csv_file'];

// Validar tipo de archivo
if ($file['type'] !== 'text/csv' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
    jsonResponse(false, 'Solo se permiten archivos CSV');
}

// Validar tamaño del archivo (máximo 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(false, 'El archivo es demasiado grande (máximo 5MB)');
}

$imported = 0;
$duplicates = 0;
$errors = 0;
$errorMessages = [];

try {
    // Leer archivo CSV
    $handle = fopen($file['tmp_name'], 'r');
    
    if (!$handle) {
        jsonResponse(false, 'No se pudo leer el archivo');
    }
    
    // Preparar statement para inserción
    $stmt = $pdo->prepare("INSERT IGNORE INTO patients (dni, box) VALUES (?, ?)");
    
    // Leer línea por línea
    $lineNumber = 0;
    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
        $lineNumber++;
        
        // Saltar línea de encabezado si existe
        if ($lineNumber === 1 && (strtolower($data[0]) === 'dni' || strtolower($data[0]) === 'documento')) {
            continue;
        }
        
        // Verificar que tenga al menos 2 columnas
        if (count($data) < 2) {
            $errors++;
            $errorMessages[] = "Línea $lineNumber: Faltan columnas";
            continue;
        }
        
        $dni = trim($data[0]);
        $box = trim($data[1]);
        
        // Validar datos
        if (empty($dni) || empty($box)) {
            $errors++;
            $errorMessages[] = "Línea $lineNumber: DNI o caja vacíos";
            continue;
        }
        
        if (!validateDNI($dni)) {
            $errors++;
            $errorMessages[] = "Línea $lineNumber: DNI inválido ($dni)";
            continue;
        }
        
        if (!validateBox($box)) {
            $errors++;
            $errorMessages[] = "Línea $lineNumber: Caja inválida ($box)";
            continue;
        }
        
        // Verificar si ya existe
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE dni = ?");
        $checkStmt->execute([$dni]);
        
        if ($checkStmt->fetchColumn() > 0) {
            $duplicates++;
            continue;
        }
        
        // Insertar registro
        if ($stmt->execute([$dni, $box])) {
            $imported++;
        } else {
            $errors++;
            $errorMessages[] = "Línea $lineNumber: Error al insertar ($dni)";
        }
    }
    
    fclose($handle);
    
    // Preparar mensaje de respuesta
    $message = "Importación completada: $imported registros importados";
    
    if ($duplicates > 0) {
        $message .= ", $duplicates duplicados omitidos";
    }
    
    if ($errors > 0) {
        $message .= ", $errors errores";
    }
    
    jsonResponse(true, $message, [
        'imported' => $imported,
        'duplicates' => $duplicates,
        'errors' => $errors,
        'error_messages' => array_slice($errorMessages, 0, 10) // Mostrar solo los primeros 10 errores
    ]);
    
} catch (Exception $e) {
    error_log("Error en import_csv.php: " . $e->getMessage());
    jsonResponse(false, 'Error al procesar el archivo CSV');
}
?>