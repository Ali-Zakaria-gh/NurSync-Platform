<?php
// ============================================================
//  Slides API — NurSync
//  Endpoints:
//    GET  /api/slides.php?action=list&hospital=kfgh → list slides
//    POST /api/slides.php?action=upload             → upload (admin, multipart)
//    POST /api/slides.php?action=download&id=X      → increment download count
// ============================================================

require_once __DIR__ . '/config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    respond([], 200);
}

$action = $_GET['action'] ?? 'list';
$input = getInput();

// Allowed file types
const ALLOWED_TYPES = [
    'application/pdf' => 'pdf',
    'application/vnd.ms-powerpoint' => 'ppt',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
];

switch ($action) {

    // ── LIST ──────────────────────────────────────────────
    case 'list':
        $pdo = getDB();
        $hospital = $input['hospital'] ?? 'all';

        $sql = "SELECT s.id, s.title, s.file_type, s.file_size_kb, s.upload_date,
                       s.download_count, s.file_path,
                       h.short_name AS hospital_name, h.color_hex,
                       a.title AS activity_title, a.activity_date
                FROM slides s
                LEFT JOIN hospitals  h ON s.hospital_id  = h.id
                LEFT JOIN activities a ON s.activity_id  = a.id
                WHERE s.is_public = 1";

        $params = [];
        if ($hospital !== 'all') {
            $sql .= " AND s.hospital_id = ?";
            $params[] = $hospital;
        }

        $sql .= " ORDER BY s.upload_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        respond(['success' => true, 'data' => $stmt->fetchAll()]);


    // ── UPLOAD ────────────────────────────────────────────
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            respond(['success' => false, 'message' => 'POST required'], 405);
        // if (empty($_SESSION['user_id']))            respond(['success'=>false,'message'=>'Login required'], 401);

        if (empty($_FILES['file']))
            respond(['success' => false, 'message' => 'No file uploaded'], 422);

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK)
            respond(['success' => false, 'message' => 'Upload error'], 500);

        // Validate type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!array_key_exists($mimeType, ALLOWED_TYPES)) {
            respond(['success' => false, 'message' => 'File type not allowed. Use PDF or PowerPoint.'], 422);
        }

        // Max 50MB
        if ($file['size'] > 50 * 1024 * 1024)
            respond(['success' => false, 'message' => 'File too large (max 50MB)'], 422);

        $ext = ALLOWED_TYPES[$mimeType];
        $filename = uniqid('slide_', true) . '.' . $ext;
        $uploadDir = __DIR__ . '/../uploads/slides/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0775, true);

        $dest = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            respond(['success' => false, 'message' => 'Failed to save file'], 500);
        }

        $pdo = getDB();
        $stmt = $pdo->prepare(
            "INSERT INTO slides (title, file_path, file_type, file_size_kb, hospital_id, activity_id, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $input['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME),
            'uploads/slides/' . $filename,
            $ext,
            round($file['size'] / 1024),
            $input['hospital_id'] ?? null,
            $input['activity_id'] ?? null,
            $_SESSION['user_id'] ?? 1
        ]);

        respond(['success' => true, 'message' => 'Slide uploaded.', 'id' => $pdo->lastInsertId()], 201);


    // ── INCREMENT DOWNLOAD COUNT ──────────────────────────
    case 'download':
        $id = (int) ($input['id'] ?? 0);
        if (!$id)
            respond(['success' => false, 'message' => 'id required'], 422);

        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT file_path FROM slides WHERE id = ? AND is_public = 1");
        $stmt->execute([$id]);
        $slide = $stmt->fetch();
        if (!$slide)
            respond(['success' => false, 'message' => 'Slide not found'], 404);

        $pdo->prepare("UPDATE slides SET download_count = download_count + 1 WHERE id = ?")
            ->execute([$id]);

        respond(['success' => true, 'file_path' => $slide['file_path']]);


    // ── DELETE SLIDE ──────────────────────────
    case 'delete':
        // if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Login required'], 401);

        $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        if (!$id)
            respond(['success' => false, 'message' => 'id required'], 422);

        $pdo = getDB();

        // Find file path to delete from disk
        $stmt = $pdo->prepare("SELECT file_path FROM slides WHERE id = ?");
        $stmt->execute([$id]);
        $slide = $stmt->fetch();

        if ($slide) {
            $path = __DIR__ . '/../' . $slide['file_path'];
            if (file_exists($path)) {
                @unlink($path);
            }

            // Delete from DB
            $pdo->prepare("DELETE FROM slides WHERE id = ?")->execute([$id]);
        }

        respond(['success' => true, 'message' => 'Slide deleted successfully']);

    default:
        respond(['success' => false, 'message' => 'Unknown action.'], 400);
}
