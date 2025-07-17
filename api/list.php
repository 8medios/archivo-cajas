<?php
// api/list.php - Listar pacientes con filtros y ordenamiento

require_once 'config.php';

// Solo aceptar GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Método no permitido');
}

// Obtener parámetros de filtrado y ordenamiento
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'dni';
$sortOrder = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'asc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

// Validar parámetros de ordenamiento
$allowedSortColumns = ['dni', 'box', 'created_at', 'updated_at'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'dni';
}

$allowedSortOrders = ['asc', 'desc'];
if (!in_array($sortOrder, $allowedSortOrders)) {
    $sortOrder = 'asc';
}

// Validar límite
if ($limit > 500) {
    $limit = 500;
}

// Calcular offset para paginación
$offset = ($page - 1) * $limit;

try {
    // Construir query base
    $whereClause = '';
    $params = [];
    
    // Agregar filtro de búsqueda si existe
    if (!empty($search)) {
        $whereClause = "WHERE dni LIKE ? OR box LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm];
    }
    
    // Query para obtener el total de registros
    $countSql = "SELECT COUNT(*) FROM patients $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    
    // Query para obtener los registros paginados
    $sql = "SELECT dni, box, created_at, updated_at 
            FROM patients 
            $whereClause 
            ORDER BY $sortBy $sortOrder 
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Agregar parámetros de limit y offset
    $queryParams = array_merge($params, [$limit, $offset]);
    $stmt->execute($queryParams);
    
    $patients = $stmt->fetchAll();
    
    // Calcular información de paginación
    $totalPages = ceil($totalRecords / $limit);
    $hasNextPage = $page < $totalPages;
    $hasPrevPage = $page > 1;
    
    jsonResponse(true, 'Pacientes obtenidos correctamente', [
        'patients' => $patients,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'records_per_page' => $limit,
            'has_next_page' => $hasNextPage,
            'has_prev_page' => $hasPrevPage
        ],
        'filters' => [
            'search' => $search,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error en list.php: " . $e->getMessage());
    jsonResponse(false, 'Error interno del servidor');
}
?>