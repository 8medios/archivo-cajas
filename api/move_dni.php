<?php
require_once 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$id = intval($input['id'] ?? 0);
$newBox = trim($input['new_box'] ?? '');

if ($id <= 0 || $newBox === '') {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE patients SET box = :box WHERE id = :id");
    $stmt->execute([
        ':box' => $newBox,
        ':id' => $id
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Error al mover paciente: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al mover']);
}
