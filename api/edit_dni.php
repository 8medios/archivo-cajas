<?php
require_once 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$id = intval($input['id'] ?? 0);
$newDni = trim($input['new_dni'] ?? '');

if ($id <= 0 || $newDni === '') {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE patients SET dni = :dni WHERE id = :id");
    $stmt->execute([
        ':dni' => $newDni,
        ':id' => $id
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Error al editar DNI: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al editar']);
}
