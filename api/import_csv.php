<?php
require_once 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método no permitido');
}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'Error al subir el archivo');
}

$file = $_FILES['csv_file'];

if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(false, 'El archivo es demasiado grande (máximo 5MB)');
}

$imported = 0;
$duplicates = 0;
$errors = 0;
$errorMessages = [];

try {
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        jsonResponse(false, 'No se pudo leer el archivo');
    }

    $stmt = $pdo->prepare("INSERT INTO patients (dni, box) VALUES (:dni, :box)");
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE dni = :dni");

    $lineNumber = 0;

    while (($data = fgetcsv($handle, 1000, ';')) !== false) {
        $lineNumber++;

        // Saltar encabezado
        if ($lineNumber === 1 && preg_match('/dni/i', $data[0])) {
            continue;
        }

        if (count($data) < 2) {
            $errors++;
            $errorMessages[] = "Línea $lineNumber: Faltan columnas";
            continue;
        }

        $dni = preg_replace('/\D/', '', trim($data[0] ?? '')); // Solo números
        $box = strtoupper(trim($data[1] ?? ''));

        if ($dni === '' || $box === '') {
            $errors++;
            $errorMessages[] = "Línea $lineNumber: DNI o caja vacíos";
            continue;
        }

        // Validaciones básicas
        if (strlen($dni) < 6 || strlen($dni) > 10) {
            $errors++;
            $errorMessages[] = "Línea $lineNumber: DNI inválido ($dni)";
            continue;
        }

        if (!preg_match('/^[A-Z0-9\-]+$/', $box)) {
            $errors++;
            $errorMessages[] = "Línea $lineNumber: Caja inválida ($box)";
            continue;
        }

        // Verificar duplicado
        $checkStmt->execute([':dni' => $dni]);
        if ($checkStmt->fetchColumn() > 0) {
            $duplicates++;
            continue;
        }

        try {
            $stmt->execute([
                ':dni' => $dni,
                ':box' => $box
            ]);
            $imported++;
        } catch (PDOException $e) {
            $errors++;
            $errorMessages[] = "Línea $lineNumber: Error al insertar ($dni)";
        }
    }

    fclose($handle);

    $msg = "Importación completada: $imported registros";
    if ($duplicates) $msg .= ", $duplicates duplicados";
    if ($errors) $msg .= ", $errors errores";

    jsonResponse(true, $msg, [
        'imported' => $imported,
        'duplicates' => $duplicates,
        'errors' => $errors,
        'error_messages' => array_slice($errorMessages, 0, 10)
    ]);

} catch (Exception $e) {
    error_log("Error en import_csv.php: " . $e->getMessage());
    jsonResponse(false, 'Error interno al procesar el CSV');
}
