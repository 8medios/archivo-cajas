<?php
// api/export.php - Exportar pacientes a CSV

require_once 'config.php';

// Solo aceptar GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener parámetros opcionales de filtrado
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'dni';
$sortOrder = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'asc';

// Validar parámetros
$allowedSortColumns = ['dni', 'box', 'created_at'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'dni';
}

$allowedSortOrders = ['asc', 'desc'];
if (!in_array($sortOrder, $allowedSortOrders)) {
    $sortOrder = 'asc';
}

try {
    // Configurar headers para descarga de archivo CSV
    $filename = 'pacientes_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Crear output stream
    $output = fopen('php://output', 'w');
    
    // Escribir BOM para UTF-8 (para que Excel lo reconozca correctamente)
    fwrite($output, "\xEF\xBB\xBF");
    
    // Escribir encabezados del CSV
    fputcsv($output, ['DNI', 'Caja', 'Fecha de Registro', 'Última Actualización'], ',');
    
    // Construir query
    $whereClause = '';
    $params = [];
    
    if (!empty($search)) {
        $whereClause = "WHERE dni LIKE ? OR box LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm];
    }
    
    $sql = "SELECT dni, box, created_at, updated_at 
            FROM patients 
            $whereClause 
            ORDER BY $sortBy $sortOrder";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Escribir datos
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Formatear fechas
        $createdAt = date('d/m/Y H:i:s', strtotime($row['created_at']));
        $updatedAt = date('d/m/Y H:i:s', strtotime($row['updated_at']));
        
        fputcsv($output, [
            $row['dni'],
            $row['box'],
            $createdAt,
            $updatedAt
        ], ',');
    }
    
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    // En caso de error, enviar respuesta JSON
    header('Content-Type: application/json');
    error_log("Error en export.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    exit;
}
?>