
<?php
require_once 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$old_name = trim($input['old_name'] ?? '');
$new_name = trim($input['new_name'] ?? '');

if ($old_name === '' || $new_name === '') {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $sql = "UPDATE patients SET box = :new_name WHERE box = :old_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':new_name' => $new_name,
        ':old_name' => $old_name
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Error al renombrar caja: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en el servidor']);
}
