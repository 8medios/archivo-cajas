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
    $params = []; // Array para los parámetros de la cláusula WHERE
    
    // Agregar filtro de búsqueda si existe
    if (!empty($search)) {
        $whereClause = "WHERE dni LIKE :searchDni OR box LIKE :searchBox"; // Usamos parámetros con nombre
        $searchTerm = "%$search%";
        $params[':searchDni'] = $searchTerm; // Asigna al nombre del parámetro
        $params[':searchBox'] = $searchTerm; // Asigna al nombre del parámetro
    }
    
    // Query para obtener el total de registros
    $countSql = "SELECT COUNT(*) FROM patients $whereClause";
    $countStmt = $pdo->prepare($countSql);
    
    // Enlazar parámetros para la consulta de conteo
    foreach ($params as $key => $value) {
        $countStmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();
    
    // Query para obtener los registros paginados
    $sql = "SELECT dni, box, created_at, updated_at 
            FROM patients 
            $whereClause 
            ORDER BY $sortBy $sortOrder 
            LIMIT :limit OFFSET :offset"; // Usamos parámetros con nombre para LIMIT y OFFSET
    
    $stmt = $pdo->prepare($sql);
    
    // Enlazar los parámetros de la cláusula WHERE (si hay)
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }

    // Enlazar LIMIT y OFFSET explícitamente como enteros
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    // Ejecutar la consulta (sin pasar parámetros aquí, ya están enlazados con bindValue)
    $stmt->execute();
    
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