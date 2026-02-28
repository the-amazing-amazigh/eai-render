<?php
// EAI API for Render.com - Uses environment variables for database connection
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Database configuration from environment variables
$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: '',
    'name' => getenv('DB_NAME') ?: 'cweai_db'
];

// Connect using MySQLi
$mysqli = new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], $dbConfig['name'], $dbConfig['port']);

if ($mysqli->connect_error) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $mysqli->connect_error,
        'config' => [
            'host' => $dbConfig['host'],
            'port' => $dbConfig['port'],
            'user' => $dbConfig['user'],
            'name' => $dbConfig['name']
        ]
    ]);
    exit;
}

$mysqli->set_charset("utf8");

$table = $_GET['table'] ?? '';
$action = $_GET['action'] ?? 'list';
$limit = intval($_GET['limit'] ?? 100);
$offset = intval($_GET['offset'] ?? 0);
$id = $_GET['id'] ?? null;

// Tables autorisées
$allowedTables = [
    'cwReturnVehiclePositions',
    'cwReturnVehicleStatus',
    'cwReturnTrips',
    'cwReturnTripDetails',
    'cwReturnTripStatus',
    'cwReturnDrivers',
    'cwReturnDriverStatus',
    'cwReturnGeofences',
    'cwReturnAlarms',
    'cwReturnActivities',
    'cwReturnTelemetry',
    'cwRequestVehicles',
    'cwRequestRoutes',
    'cwRequestDrivers'
];

try {
    switch($action) {
        case 'latest_positions':
            getLatestPositions($mysqli);
            break;
        case 'stats':
            getDatabaseStats($mysqli);
            break;
        case 'list':
            if (!in_array($table, $allowedTables)) {
                echo json_encode(['status' => 'error', 'message' => 'Table not allowed']);
                exit;
            }
            getTableData($mysqli, $table, $limit, $offset);
            break;
        case 'count':
            if (!in_array($table, $allowedTables)) {
                echo json_encode(['status' => 'error', 'message' => 'Table not allowed']);
                exit;
            }
            getTableCount($mysqli, $table);
            break;
        case 'schema':
            if (!in_array($table, $allowedTables)) {
                echo json_encode(['status' => 'error', 'message' => 'Table not allowed']);
                exit;
            }
            getTableSchema($mysqli, $table);
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function getTableData($mysqli, $table, $limit, $offset) {
    $colsResult = $mysqli->query("SHOW COLUMNS FROM $table");
    $columns = [];
    if ($colsResult) {
        while ($row = $colsResult->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    
    $orderBy = '';
    if (in_array('MeasuredTime', $columns)) {
        $orderBy = 'ORDER BY MeasuredTime DESC';
    } elseif (in_array('ID', $columns)) {
        $orderBy = 'ORDER BY ID DESC';
    }
    
    $sql = "SELECT * FROM $table $orderBy LIMIT $limit OFFSET $offset";
    $result = $mysqli->query($sql);
    
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode(['status' => 'success', 'data' => $data, 'columns' => $columns]);
}

function getTableCount($mysqli, $table) {
    $sql = "SELECT COUNT(*) as total FROM $table";
    $result = $mysqli->query($sql);
    $count = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $count = intval($row['total']);
    }
    echo json_encode(['status' => 'success', 'count' => $count]);
}

function getTableSchema($mysqli, $table) {
    $result = $mysqli->query("SHOW COLUMNS FROM $table");
    $columns = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = [
                'name' => $row['Field'],
                'type' => $row['Type'],
                'nullable' => $row['Null'] === 'YES',
                'key' => $row['Key'],
                'default' => $row['Default']
            ];
        }
    }
    echo json_encode(['status' => 'success', 'columns' => $columns]);
}

function getLatestPositions($mysqli) {
    $sql = "SELECT v.* FROM cwReturnVehiclePositions v
            INNER JOIN (
                SELECT CWVehicleID, MAX(MeasuredTime) as maxTime
                FROM cwReturnVehiclePositions
                GROUP BY CWVehicleID
            ) latest ON v.CWVehicleID = latest.CWVehicleID AND v.MeasuredTime = latest.maxTime
            LIMIT 200";
    $result = $mysqli->query($sql);
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
}

function getDatabaseStats($mysqli) {
    $tables = [
        'cwReturnVehiclePositions',
        'cwReturnVehicleStatus',
        'cwReturnTrips',
        'cwReturnDrivers',
        'cwRequestVehicles'
    ];
    
    $stats = [];
    foreach ($tables as $table) {
        $sql = "SELECT COUNT(*) as total FROM $table";
        $result = $mysqli->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $stats[$table] = intval($row['total']);
        } else {
            $stats[$table] = 0;
        }
    }
    
    $sql = "SELECT COUNT(DISTINCT CWVehicleID) as active FROM cwReturnVehiclePositions WHERE MeasuredTime > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $result = $mysqli->query($sql);
    $activeVehicles = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $activeVehicles = intval($row['active']);
    }
    $stats['activeVehicles24h'] = $activeVehicles;
    
    echo json_encode(['status' => 'success', 'stats' => $stats]);
}

$mysqli->close();
