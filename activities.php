<?php
// ============================================================
//  Activities API — NurSync
//  Endpoints:
//    GET  /api/activities.php?action=upcoming              → upcoming activities
//    GET  /api/activities.php?action=all&hospital=kfgh     → all (optional filter)
//    GET  /api/activities.php?action=calendar&year=Y&month=M
//    POST /api/activities.php?action=create               → create (admin only)
// ============================================================

require_once __DIR__ . '/config/db.php';
session_start();

// Automatically clean up activities older than 1 year
try {
    $cleanupPdo = getDB();
    $cleanupPdo->exec("DELETE FROM activities WHERE activity_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)");
} catch (Exception $e) {
    // Fail silently to avoid interrupting user requests
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { respond([], 200); }

$action = $_GET['action'] ?? 'upcoming';
$input  = getInput();

switch ($action) {

    // ── UPCOMING (for upcoming.html) ───────────────────────
    case 'upcoming':
        $pdo  = getDB();
        $hospital = $input['hospital'] ?? 'all';

        $sql = "SELECT a.id, a.title, a.activity_date, a.start_time,
                       a.end_time, a.location, a.max_attendees, a.description,
                       h.id AS hospital_id, h.short_name AS hospital_name, h.color_hex,
                       COUNT(r.id) AS registered_count,
                       DATEDIFF(a.activity_date, CURDATE()) AS days_until
                FROM activities a
                JOIN hospitals h ON a.hospital_id = h.id
                LEFT JOIN registrations r ON a.id = r.activity_id
                WHERE a.activity_date >= CURDATE()
                  AND a.status != 'cancelled'";

        $params = [];
        if ($hospital !== 'all') {
            $sql    .= " AND a.hospital_id = ?";
            $params[] = $hospital;
        }

        $sql .= " GROUP BY a.id, h.id ORDER BY a.activity_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        respond(['success'=>true,'data'=>$stmt->fetchAll()]);


    // ── ALL activities (for admin / full calendar) ─────────
    case 'all':
        $pdo      = getDB();
        $hospital = $input['hospital'] ?? 'all';

        $sql = "SELECT a.id, a.title, a.activity_date, a.status, a.start_time, a.description, a.poster_path,
                       h.id AS hospital_id, h.short_name AS hospital_name, h.color_hex,
                       (SELECT id FROM slides s WHERE s.activity_id = a.id ORDER BY id DESC LIMIT 1) AS slide_id,
                       (SELECT file_path FROM slides s WHERE s.activity_id = a.id ORDER BY id DESC LIMIT 1) AS slide_path,
                       COUNT(r.id) AS registered_count
                FROM activities a
                JOIN hospitals h ON a.hospital_id = h.id
                LEFT JOIN registrations r ON a.id = r.activity_id";

        $params = [];
        if ($hospital !== 'all') {
            $sql    .= " WHERE a.hospital_id = ?";
            $params[] = $hospital;
        }

        $sql .= " GROUP BY a.id, h.id 
                 ORDER BY 
                    CASE WHEN a.activity_date >= CURDATE() THEN 0 ELSE 1 END,
                    CASE WHEN a.activity_date >= CURDATE() THEN a.activity_date END ASC,
                    CASE WHEN a.activity_date < CURDATE() THEN a.activity_date END DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        respond(['success'=>true,'data'=>$stmt->fetchAll()]);


    // ── CALENDAR view (for index.html grid) ───────────────
    case 'calendar':
        $pdo   = getDB();
        $year  = (int)($input['year']  ?? date('Y'));
        $month = (int)($input['month'] ?? date('n'));
        $hospital = $input['hospital'] ?? 'all';

        $sql = "SELECT a.id, a.title, a.activity_date, a.poster_path,
                       h.id AS hospital_id, h.short_name AS hospital_name, h.color_hex
                FROM activities a
                JOIN hospitals h ON a.hospital_id = h.id
                WHERE YEAR(a.activity_date) = ?
                  AND MONTH(a.activity_date) = ?
                  AND a.status != 'cancelled'";

        $params = [$year, $month];
        if ($hospital !== 'all') {
            $sql    .= " AND a.hospital_id = ?";
            $params[] = $hospital;
        }

        $sql .= " ORDER BY a.activity_date ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Group by day of month for easy calendar rendering
        $byDay = [];
        foreach ($stmt->fetchAll() as $row) {
            $day = (int)date('j', strtotime($row['activity_date']));
            $byDay[$day][] = $row;
        }

        respond(['success'=>true,'year'=>$year,'month'=>$month,'data'=>$byDay]);


    // ── CREATE (admin only) ────────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);

        $userRole = $_SESSION['user_role'] ?? 'guest';
        $userHosp = $_SESSION['user_hospital_id'] ?? null;
        
        if ($userRole !== 'admin' && $userRole !== 'manager' && $userRole !== 'headnurse') {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        if ($userRole === 'headnurse') {
            $input['hospital_id'] = $userHosp;
        }

        $required = ['title','hospital_id','activity_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) respond(['success'=>false,'message'=>"$field is required"], 422);
        }

        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "INSERT INTO activities (title, description, hospital_id, activity_date,
                                     start_time, end_time, location, max_attendees, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $input['title'],
            $input['description']   ?? null,
            $input['hospital_id'],
            $input['activity_date'],
            $input['start_time']    ?? null,
            $input['end_time']      ?? null,
            $input['location']      ?? null,
            $input['max_attendees'] ?? 0,
            $_SESSION['user_id']    ?? 0
        ]);

        respond(['success'=>true,'id'=>$pdo->lastInsertId(),'message'=>'Activity created.'], 201);


    // ── UPDATE (admin only) ────────────────────────────────
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);

        $id = (int)($input['id'] ?? 0);
        if (!$id) respond(['success'=>false,'message'=>'id required'], 422);

        $pdo  = getDB();

        $userRole = $_SESSION['user_role'] ?? 'guest';
        $userHosp = $_SESSION['user_hospital_id'] ?? null;

        if ($userRole !== 'admin' && $userRole !== 'manager' && $userRole !== 'headnurse') {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        if ($userRole === 'headnurse') {
            $check = $pdo->prepare("SELECT hospital_id FROM activities WHERE id = ?");
            $check->execute([$id]);
            $act = $check->fetch();
            if (!$act || $act['hospital_id'] !== $userHosp) {
                respond(['success'=>false,'message'=>'Unauthorized: You can only edit activities for your own hospital.'], 403);
            }
        }
        $fields = [];
        $params = [];
        
        $allowedFields = ['title', 'description', 'hospital_id', 'activity_date', 'start_time', 'end_time', 'location', 'max_attendees', 'status'];
        foreach ($allowedFields as $f) {
            if (isset($input[$f])) {
                $fields[] = "$f = ?";
                $params[] = $input[$f] === '' ? null : $input[$f]; // Handle empty string as null
            }
        }
        
        if (empty($fields)) {
            respond(['success'=>false,'message'=>'No valid fields provided'], 422);
        }

        $params[] = $id;

        $sql = "UPDATE activities SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        respond(['success'=>true,'message'=>'Activity updated.']);

    // ── DELETE (admin only) ────────────────────────────────
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);

        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        if (!$id) respond(['success'=>false,'message'=>'id required'], 422);

        $pdo  = getDB();

        $userRole = $_SESSION['user_role'] ?? 'guest';
        $userHosp = $_SESSION['user_hospital_id'] ?? null;

        if ($userRole !== 'admin' && $userRole !== 'manager' && $userRole !== 'headnurse') {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        if ($userRole === 'headnurse') {
            $check = $pdo->prepare("SELECT hospital_id FROM activities WHERE id = ?");
            $check->execute([$id]);
            $act = $check->fetch();
            if (!$act || $act['hospital_id'] !== $userHosp) {
                respond(['success'=>false,'message'=>'Unauthorized: You can only delete activities for your own hospital.'], 403);
            }
        }
        $stmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
        $stmt->execute([$id]);

        respond(['success'=>true,'message'=>'Activity deleted.']);

    // ── UPLOAD POSTER ──────────────────────────────────────
    case 'upload_poster':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);
        if (empty($_FILES['poster'])) respond(['success'=>false,'message'=>'No poster uploaded'], 422);
        
        $activity_id = (int)($input['activity_id'] ?? $_POST['activity_id'] ?? 0);
        if (!$activity_id) respond(['success'=>false,'message'=>'Activity ID required'], 422);

        $poster = $_FILES['poster'];
        $ext = strtolower(pathinfo($poster['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            respond(['success'=>false,'message'=>'Invalid image format'], 422);
        }

        $uploadDir = __DIR__ . '/../uploads/posters/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $newFileName = 'poster_' . $activity_id . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $newFileName;

        if (!move_uploaded_file($poster['tmp_name'], $dest)) {
            respond(['success'=>false,'message'=>'Failed to save poster'], 500);
        }

        $poster_url = 'uploads/posters/' . $newFileName;

        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE activities SET poster_path = ? WHERE id = ?");
        $stmt->execute([$poster_url, $activity_id]);

        respond(['success'=>true,'poster_path'=>$poster_url,'message'=>'Poster uploaded.']);

    // ── DELETE POSTER ──────────────────────────────────────
    case 'delete_poster':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);
        $activity_id = (int)($input['activity_id'] ?? $_POST['activity_id'] ?? 0);
        if (!$activity_id) respond(['success'=>false,'message'=>'Activity ID required'], 422);

        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE activities SET poster_path = NULL WHERE id = ?");
        $stmt->execute([$activity_id]);

        respond(['success'=>true,'message'=>'Poster deleted.']);

    // ── UPCOMING SIDEBAR (Lightweight) ─────────────────────
    case 'upcoming_widget':
        $pdo = getDB();
        $today = date('Y-m-d');
        $sql = "SELECT a.id, a.title, a.activity_date, a.start_time, a.poster_path, h.id AS hospital_id, h.short_name AS hospital_name, h.color_hex 
                FROM activities a 
                JOIN hospitals h ON a.hospital_id = h.id 
                WHERE a.activity_date >= ? 
                ORDER BY a.activity_date ASC, COALESCE(a.start_time, '23:59:59') ASC 
                LIMIT 4";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$today]);
        respond(['success'=>true, 'data'=>$stmt->fetchAll()]);

    // ── MY HOSPITAL ACTIVITIES (for management) ────────────
    case 'my_hospital_activities':
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);
        $userRole = $_SESSION['user_role'] ?? 'guest';
        $userHosp = $_SESSION['user_hospital_id'] ?? null;

        if ($userRole !== 'admin' && $userRole !== 'manager' && $userRole !== 'headnurse') {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        $pdo = getDB();
        $sql = "SELECT a.id, a.title, a.activity_date, a.status, a.hospital_id,
                       h.short_name AS hospital_name, h.color_hex,
                       COUNT(r.id) AS registered_count
                FROM activities a
                JOIN hospitals h ON a.hospital_id = h.id
                LEFT JOIN registrations r ON a.id = r.activity_id";
        
        $params = [];
        if ($userRole === 'headnurse') {
            $sql .= " WHERE a.hospital_id = ?";
            $params[] = $userHosp;
        }

        $sql .= " GROUP BY a.id, h.id ORDER BY a.activity_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        respond(['success'=>true, 'data'=>$stmt->fetchAll()]);

    default:
        respond(['success'=>false,'message'=>'Unknown action.'], 400);
}
