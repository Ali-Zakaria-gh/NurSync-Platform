<?php
// ============================================================
//  Database Configuration — NurSync
//  Automatically detects environment (Local vs Production)
// ============================================================

// Check if running on localhost (CLI or HTTP)
$isLocal = false;
if (php_sapi_name() === 'cli') {
    $isLocal = true;
} else {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false) {
        $isLocal = true;
    }
}

if ($isLocal) {
    // Local development configuration (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'nursync_db');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // Production (InfinityFree) configuration
    define('DB_HOST', 'sql212.infinityfree.com');
    define('DB_NAME', 'if0_41735757_nursync_db');
    define('DB_USER', 'if0_41735757');
    define('DB_PASS', ''); // Password removed for security - please set your production password securely
}

define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
            exit;
        }
    }
    return $pdo;
}

// Standard JSON response helpers
function respond(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function getInput(): array
{
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    return array_merge($_GET, $_POST, $body);
}
