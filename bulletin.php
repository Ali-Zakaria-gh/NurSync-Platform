<?php
// ============================================================
//  Bulletin API — NurSync
//  Endpoints:
//    GET  /api/bulletin.php?action=latest    → latest bulletin file
//    POST /api/bulletin.php?action=upload    → upload new bulletin PDF (manager/admin only)
//    POST /api/bulletin.php?action=delete    → delete bulletin
// ============================================================

require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { respond([], 200); }

$action = $_GET['action'] ?? 'latest';
$input  = getInput();

function ensureBulletinsTable($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS bulletins (
            id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            file_path VARCHAR(255) NOT NULL,
            uploaded_by INT UNSIGNED NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    } catch (Exception $e) {
        // Ignore if already exists
    }
}

switch ($action) {
    case 'latest':
        try {
            $pdo = getDB();
            ensureBulletinsTable($pdo);
            $stmt = $pdo->query("SELECT b.*, u.full_name AS uploaded_by_name FROM bulletins b LEFT JOIN users u ON b.uploaded_by = u.id ORDER BY b.id DESC LIMIT 1");
            $bulletin = $stmt->fetch();
            respond(['success' => true, 'data' => $bulletin ? $bulletin : null]);
        } catch (Exception $e) {
            respond(['success' => true, 'data' => null, 'error' => $e->getMessage()]);
        }
        break;

    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);

        $userRole = $_SESSION['user_role'] ?? 'guest';
        if ($userRole !== 'admin' && $userRole !== 'manager' && $userRole !== 'headnurse') {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        if (empty($_FILES['bulletin_file'])) respond(['success'=>false,'message'=>'No file uploaded'], 422);

        $file = $_FILES['bulletin_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') {
            respond(['success'=>false,'message'=>'Only PDF files are allowed.'], 422);
        }

        $uploadDir = __DIR__ . '/../uploads/bulletins/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $newFileName = 'bulletin_' . time() . '.' . $ext;
        $dest = $uploadDir . $newFileName;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            respond(['success'=>false,'message'=>'Failed to save file.'], 500);
        }

        $file_url = 'uploads/bulletins/' . $newFileName;

        try {
            $pdo = getDB();
            ensureBulletinsTable($pdo);
            
            // --- AUTO-REPLACE FEATURE ---
            $oldStmt = $pdo->query("SELECT id, file_path FROM bulletins");
            $oldBulletins = $oldStmt->fetchAll();
            foreach ($oldBulletins as $oldB) {
                $oldFilePath = __DIR__ . '/../' . $oldB['file_path'];
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }
                $pdo->prepare("DELETE FROM bulletins WHERE id = ?")->execute([$oldB['id']]);
            }

            $stmt = $pdo->prepare("INSERT INTO bulletins (file_path, uploaded_by) VALUES (?, ?)");
            $stmt->execute([$file_url, $_SESSION['user_id']]);

            respond(['success'=>true,'file_path'=>$file_url,'message'=>'Bulletin uploaded successfully.']);
        } catch (Exception $e) {
            respond(['success'=>false,'message'=>'Database error: ' . $e->getMessage()], 500);
        }
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);

        $userRole = $_SESSION['user_role'] ?? 'guest';
        if ($userRole !== 'admin' && $userRole !== 'manager' && $userRole !== 'headnurse') {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        try {
            $pdo = getDB();
            ensureBulletinsTable($pdo);
            $oldStmt = $pdo->query("SELECT id, file_path FROM bulletins");
            $oldBulletins = $oldStmt->fetchAll();
            
            $deletedCount = 0;
            foreach ($oldBulletins as $oldB) {
                $oldFilePath = __DIR__ . '/../' . $oldB['file_path'];
                if (file_exists($oldFilePath)) {
                    @unlink($oldFilePath);
                }
                $pdo->prepare("DELETE FROM bulletins WHERE id = ?")->execute([$oldB['id']]);
                $deletedCount++;
            }

            respond(['success'=>true, 'message'=>'Bulletin deleted successfully.', 'count'=>$deletedCount]);
        } catch (Exception $e) {
            respond(['success'=>false,'message'=>'Database error: ' . $e->getMessage()], 500);
        }
        break;

    default:
        respond(['success' => false, 'message' => 'Invalid action'], 400);
}
