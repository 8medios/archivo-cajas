<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Método no permitido');
}

$search = isset($_GET['search']) ? trim($_GET['search']) : ''; 
$sortBy = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'dni';
$sortOrder = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'asc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;

// Validar columnas válidas
$allowedSortColumns = ['dni', 'box', 'created_at', 'updated_at'];
if (!in_array($sortBy, $allowedSortColumns)) $sortBy = 'dni';
if (!in_array($sortOrder, ['asc', 'desc'])) $sortOrder = 'asc';
if ($limit > 500) $limit = 500;

$offset = ($page - 1) * $limit;

try {
    $params = [];
    $where = '';

    // Si se especifica un nombre de caja exacto, se usa ese filtro
    if (!empty($boxName)) {
        $where = "WHERE p.box = :boxName";
        $params[':boxName'] = $boxName;
    } elseif (!empty($search)) { // Si no, y hay un parámetro de búsqueda general, se usa el LIKE
        $where = "WHERE p.dni LIKE :search OR p.box LIKE :search";
        $params[':search'] = "%$search%";
    }

    // Subconsulta que selecciona solo el último id por dni
    $sql = "
        SELECT p.id, p.dni, p.box, p.created_at, p.updated_at
        FROM patients p
        INNER JOIN (
            SELECT MAX(id) as id
            FROM patients
            GROUP BY dni
        ) latest ON p.id = latest.id
        $where
        ORDER BY $sortBy $sortOrder
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total de pacientes únicos 
    $countSql = "SELECT COUNT(*) FROM (SELECT MAX(id) as id FROM patients GROUP BY dni) AS sub";
    
    // Si hay un filtro de caja exacto, ajusto el conteo total para reflejar solo esa caja
    if (!empty($boxName)) {
        $countSql = "SELECT COUNT(*) FROM (SELECT MAX(p.id) FROM patients p WHERE p.box = :boxName GROUP BY p.dni) AS sub";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->bindValue(':boxName', $boxName, PDO::PARAM_STR);
    } elseif (!empty($search)) {
        $countSql = "SELECT COUNT(*) FROM (SELECT MAX(p.id) FROM patients p WHERE p.dni LIKE :search OR p.box LIKE :search GROUP BY p.dni) AS sub";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    } else {
        $countStmt = $pdo->prepare($countSql);
    }

    $countStmt->execute();
    $totalRecords = $countStmt->fetchColumn();

    jsonResponse(true, 'Pacientes obtenidos correctamente', [
        'patients' => $patients,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($totalRecords / $limit),
            'total_records' => $totalRecords,
            'records_per_page' => $limit,
            'has_next_page' => $page * $limit < $totalRecords,
            'has_prev_page' => $page > 1
        ],
        'filters' => [
            'search' => $search,
            'boxName' => $boxName, 
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ]
    ]);
} catch (PDOException $e) {
    error_log("Error en list.php: " . $e->getMessage());
    jsonResponse(false, 'Error interno del servidor');
}
?>