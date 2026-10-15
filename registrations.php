<?php
// ============================================================
//  Registrations API - NurSync
// ============================================================

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mailer.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {
    case 'join':
        $activityId = (int)($input['activity_id'] ?? 0);
        $guestName  = htmlspecialchars(strip_tags(trim($input['guest_name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $guestEmail = htmlspecialchars(strip_tags(trim($input['guest_email'] ?? '')), ENT_QUOTES, 'UTF-8');
        $guestPhone = htmlspecialchars(strip_tags(trim($input['phone'] ?? '')), ENT_QUOTES, 'UTF-8');
        $guestHosp  = htmlspecialchars(strip_tags(trim($input['guest_hospital'] ?? '')), ENT_QUOTES, 'UTF-8');

        if (!$activityId || !$guestName || !$guestEmail || !$guestHosp) {
            respond(['success'=>false,'message'=>'All fields are required'], 422);
        }

        $pdo = getDB();
        
        $chk = $pdo->prepare("SELECT id FROM registrations WHERE guest_email = ? AND activity_id = ?");
        $chk->execute([$guestEmail, $activityId]);
        if ($chk->fetch()) respond(['success'=>false,'message'=>'Already registered.']);

        $userId = $_SESSION['user_id'] ?? null;

        $stmt = $pdo->prepare("
            INSERT INTO registrations (activity_id, user_id, guest_name, guest_email, employee_id, phone, department, guest_hospital, registered_at, attended, approved_by_sender, approved_by_host, is_approved)
            VALUES (?, ?, ?, ?, NULL, ?, NULL, ?, NOW(), 0, 0, 0, 0)
        ");
        $stmt->execute([
            $activityId,
            $userId,
            $guestName,
            $guestEmail,
            $guestPhone,
            $guestHosp
        ]);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE activity_id = ?");
        $countStmt->execute([$activityId]);
        respond(['success'=>true,'message'=>'Joined successfully.','count'=>(int)$countStmt->fetchColumn()]);

    case 'leave':
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);
        $activityId = (int)($input['activity_id'] ?? 0);
        
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM registrations WHERE user_id = ? AND activity_id = ?");
        $stmt->execute([$_SESSION['user_id'], $activityId]);
        
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE activity_id = ?");
        $countStmt->execute([$activityId]);
        respond(['success'=>true,'message'=>'Left activity.','count'=>(int)$countStmt->fetchColumn()]);

    case 'check':
        if (empty($_SESSION['user_id'])) respond(['success'=>true,'joined'=>false,'count'=>0]);
        $activityId = (int)($_GET['activity_id'] ?? 0);
        
        $pdo = getDB();
        $chk = $pdo->prepare("SELECT id FROM registrations WHERE user_id = ? AND activity_id = ?");
        $chk->execute([$_SESSION['user_id'], $activityId]);
        $joined = (bool)$chk->fetch();

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE activity_id = ?");
        $countStmt->execute([$activityId]);
        respond(['success'=>true,'joined'=>$joined,'count'=>(int)$countStmt->fetchColumn()]);

    case 'my_registrations':
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);
        $userId = $_SESSION['user_id'];
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT r.*, a.title AS activity_title, a.activity_date, h.short_name AS host_hospital_name
            FROM registrations r
            JOIN activities a ON r.activity_id = a.id
            LEFT JOIN hospitals h ON a.hospital_id = h.id
            WHERE r.user_id = ?
            ORDER BY a.activity_date ASC
        ");
        $stmt->execute([$userId]);
        respond(['success'=>true,'data'=>$stmt->fetchAll()]);

    case 'list_attendees':
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);
        $activityId = (int)($_GET['activity_id'] ?? 0);
        if (!$activityId) respond(['success'=>false,'message'=>'activity_id required'], 422);

        $pdo = getDB();
        $actStmt = $pdo->prepare("SELECT hospital_id FROM activities WHERE id = ?");
        $actStmt->execute([$activityId]);
        $activity = $actStmt->fetch();
        if (!$activity) respond(['success'=>false,'message'=>'Activity not found.'], 404);

        $userRole = $_SESSION['user_role'] ?? 'guest';
        $userHosp = $_SESSION['user_hospital_id'] ?? null;

        if ($userRole !== 'admin' && $userRole !== 'manager' && ($userRole !== 'headnurse' || $activity['hospital_id'] != $userHosp)) {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        $sql = "SELECT r.id, r.guest_name, r.guest_email, r.employee_id, r.phone, 
                       r.department, r.registered_at, r.attended, r.is_approved,
                       u.full_name AS registered_user_name, u.email AS registered_user_email
                FROM registrations r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.activity_id = ?
                ORDER BY r.registered_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$activityId]);
        respond(['success'=>true, 'data'=>$stmt->fetchAll()]);

    case 'toggle_attendance':
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);
        $regId = (int)($input['id'] ?? 0);
        if (!$regId) respond(['success'=>false,'message'=>'id required'], 422);

        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE registrations SET attended = NOT attended WHERE id = ?");
        $stmt->execute([$regId]);
        respond(['success'=>true, 'message'=>'Attendance toggled.']);

    case 'get_hospital_staff':
        $hospitalId = (int)($_GET['hospital_id'] ?? 0);
        if (!$hospitalId) respond(['success'=>false,'message'=>'hospital_id required'], 422);
        
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.department, u.phone, u.employee_id, u.email 
            FROM users u
            WHERE u.hospital_id = ? AND u.role IN ('staff', 'headnurse')
            ORDER BY u.full_name ASC
        ");
        $stmt->execute([$hospitalId]);
        respond(['success'=>true, 'data'=>$stmt->fetchAll()]);

    case 'list_pending_staff':
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);
        
        $userRole = $_SESSION['user_role'] ?? 'guest';
        $userId   = $_SESSION['user_id'];
        $userHosp = $_SESSION['user_hospital_id'] ?? null;

        if ($userRole !== 'headnurse' && $userRole !== 'manager' && $userRole !== 'admin') {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        $pdo = getDB();
        
        $sql = "
            SELECT r.id, r.guest_name, r.guest_email, r.registered_at, r.is_approved, r.approved_by_sender, r.approved_by_host,
                   a.title AS activity_title, a.activity_date,
                   u.full_name AS registered_user_name, u.email AS registered_user_email,
                   sh.short_name AS sender_hospital_name,
                   hh.short_name AS host_hospital_name,
                   r.guest_hospital AS sender_hospital_id,
                   a.hospital_id AS host_hospital_id
            FROM registrations r
            JOIN activities a ON r.activity_id = a.id
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN hospitals sh ON r.guest_hospital = sh.id
            LEFT JOIN hospitals hh ON a.hospital_id = hh.id
            WHERE 1=1
        ";
        
        $params = [];
        if ($userRole === 'headnurse' && $userHosp) {
            $sql .= " AND (r.guest_hospital = ? OR a.hospital_id = ?)";
            $params[] = $userHosp;
            $params[] = $userHosp;
        }
        $sql .= " ORDER BY r.registered_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        respond(['success'=>true, 'data'=>$stmt->fetchAll()]);

    case 'approve':
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);
        $regId = (int)($input['id'] ?? 0);
        if (!$regId) respond(['success'=>false,'message'=>'id required'], 422);

        $userRole = $_SESSION['user_role'] ?? 'guest';
        $userId   = $_SESSION['user_id'];
        $userHosp = $_SESSION['user_hospital_id'] ?? null;

        if ($userRole !== 'headnurse' && $userRole !== 'manager' && $userRole !== 'admin') {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        $pdo = getDB();

        $regStmt = $pdo->prepare("
            SELECT r.guest_hospital, a.hospital_id AS activity_hospital_id
            FROM registrations r
            JOIN activities a ON r.activity_id = a.id
            WHERE r.id = ?
        ");
        $regStmt->execute([$regId]);
        $reg = $regStmt->fetch();
        if (!$reg) respond(['success'=>false,'message'=>'Registration not found.'], 404);

        $allowed = false;
        if ($userRole === 'admin' || $userRole === 'manager') {
            $allowed = true;
        } elseif ($userRole === 'headnurse') {
            if ($userHosp && ($userHosp == $reg['guest_hospital'] || $userHosp == $reg['activity_hospital_id'])) {
                $allowed = true;
            }
        }

        if (!$allowed) respond(['success'=>false,'message'=>'Forbidden: You are not authorized to manage this registration.'], 403);

        $isSenderCoord = ($userHosp && $userHosp == $reg['guest_hospital']);
        $isHostCoord   = ($userHosp && $userHosp == $reg['activity_hospital_id']);

        if ($userRole === 'admin' || $userRole === 'manager') {
            $stmt = $pdo->prepare("UPDATE registrations SET approved_by_sender = 1, approved_by_host = 1, is_approved = 1 WHERE id = ?");
            $stmt->execute([$regId]);
            
            $emailStmt = $pdo->prepare("
                SELECT COALESCE(NULLIF(r.guest_email, ''), u.email) AS email,
                       COALESCE(NULLIF(r.guest_name, ''), u.full_name) AS full_name,
                       a.title AS activity_name,
                       a.activity_date AS date,
                       h.short_name AS hospital_name
                FROM registrations r
                LEFT JOIN users u ON r.user_id = u.id
                JOIN activities a ON r.activity_id = a.id
                LEFT JOIN hospitals h ON a.hospital_id = h.id
                WHERE r.id = ?
            ");
            $emailStmt->execute([$regId]);
            $emailPayload = null;
            if ($emailData && !empty($emailData['email'])) {
                $debug = sendDualApprovalEmail($emailData['email'], $emailData['full_name'], $emailData['activity_name'], $emailData['date'], $emailData['hospital_name']);
                if (!empty($debug['payload_array'])) {
                    $emailPayload = [
                        'url' => $debug['url'] ?? 'https://script.google.com/macros/s/AKfycbx7NChMox05dsyh0AjlHyYjiLKg6kSHWKLLhHbo64Uj5Cmh-LZbmMdbK_aBLS7a8OA1Cw/exec',
                        'payload' => $debug['payload_array']
                    ];
                }
            }
            respond(['success'=>true, 'message'=>'Registration approved successfully.', 'email_payload'=>$emailPayload]);
        } else {
            if ($isSenderCoord && $isHostCoord) {
                $stmt = $pdo->prepare("UPDATE registrations SET approved_by_sender = 1, approved_by_host = 1 WHERE id = ?");
                $stmt->execute([$regId]);
            } elseif ($isSenderCoord) {
                $stmt = $pdo->prepare("UPDATE registrations SET approved_by_sender = 1 WHERE id = ?");
                $stmt->execute([$regId]);
            } elseif ($isHostCoord) {
                $stmt = $pdo->prepare("UPDATE registrations SET approved_by_host = 1 WHERE id = ?");
                $stmt->execute([$regId]);
            }

            $checkStmt = $pdo->prepare("SELECT approved_by_sender, approved_by_host FROM registrations WHERE id = ?");
            $checkStmt->execute([$regId]);
            $approvals = $checkStmt->fetch();
            
            $emailPayload = null;
            if ($approvals && $approvals['approved_by_sender'] == 1 && $approvals['approved_by_host'] == 1) {
                $pdo->prepare("UPDATE registrations SET is_approved = 1 WHERE id = ?")->execute([$regId]);
                
                $emailStmt = $pdo->prepare("
                    SELECT COALESCE(NULLIF(r.guest_email, ''), u.email) AS email,
                           COALESCE(NULLIF(r.guest_name, ''), u.full_name) AS full_name,
                           a.title AS activity_name,
                           a.activity_date AS date,
                           h.short_name AS hospital_name
                    FROM registrations r
                    LEFT JOIN users u ON r.user_id = u.id
                    JOIN activities a ON r.activity_id = a.id
                    LEFT JOIN hospitals h ON a.hospital_id = h.id
                    WHERE r.id = ?
                ");
                $emailStmt->execute([$regId]);
                $emailData = $emailStmt->fetch();
                if ($emailData && !empty($emailData['email'])) {
                    $debug = sendDualApprovalEmail($emailData['email'], $emailData['full_name'], $emailData['activity_name'], $emailData['date'], $emailData['hospital_name']);
                    if (!empty($debug['payload_array'])) {
                        $emailPayload = [
                            'url' => $debug['url'] ?? 'https://script.google.com/macros/s/AKfycbx7NChMox05dsyh0AjlHyYjiLKg6kSHWKLLhHbo64Uj5Cmh-LZbmMdbK_aBLS7a8OA1Cw/exec',
                            'payload' => $debug['payload_array']
                        ];
                    }
                }
            }
            respond(['success'=>true, 'message'=>'Registration approved successfully.', 'email_payload'=>$emailPayload]);
        }
        respond(['success'=>true, 'message'=>'Registration approved successfully.']);

    case 'unapprove':
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);
        $regId = (int)($input['id'] ?? 0);
        if (!$regId) respond(['success'=>false,'message'=>'id required'], 422);

        $userRole = $_SESSION['user_role'] ?? 'guest';
        $userId   = $_SESSION['user_id'];
        $userHosp = $_SESSION['user_hospital_id'] ?? null;

        if ($userRole !== 'headnurse' && $userRole !== 'manager' && $userRole !== 'admin') {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        $pdo = getDB();
        $regStmt = $pdo->prepare("
            SELECT r.guest_hospital, a.hospital_id AS activity_hospital_id
            FROM registrations r
            JOIN activities a ON r.activity_id = a.id
            WHERE r.id = ?
        ");
        $regStmt->execute([$regId]);
        $reg = $regStmt->fetch();
        if (!$reg) respond(['success'=>false,'message'=>'Registration not found.'], 404);

        $isSenderCoord = ($userHosp && $userHosp == $reg['guest_hospital']);
        $isHostCoord   = ($userHosp && $userHosp == $reg['activity_hospital_id']);

        if ($userRole === 'admin' || $userRole === 'manager') {
            $stmt = $pdo->prepare("UPDATE registrations SET approved_by_sender = 0, approved_by_host = 0, is_approved = 0, attended = 0 WHERE id = ?");
            $stmt->execute([$regId]);
        } else {
            if ($isSenderCoord && $isHostCoord) {
                $stmt = $pdo->prepare("UPDATE registrations SET approved_by_sender = 0, approved_by_host = 0 WHERE id = ?");
                $stmt->execute([$regId]);
            } elseif ($isSenderCoord) {
                $stmt = $pdo->prepare("UPDATE registrations SET approved_by_sender = 0 WHERE id = ?");
                $stmt->execute([$regId]);
            } elseif ($isHostCoord) {
                $stmt = $pdo->prepare("UPDATE registrations SET approved_by_host = 0 WHERE id = ?");
                $stmt->execute([$regId]);
            }
            $pdo->prepare("UPDATE registrations SET is_approved = 0, attended = 0 WHERE id = ?")->execute([$regId]);
        }
        respond(['success'=>true, 'message'=>'Registration approval revoked.']);

    case 'reject':
        if (empty($_SESSION['user_id'])) respond(['success'=>false,'message'=>'Unauthorized'], 401);
        $regId = (int)($input['id'] ?? 0);
        if (!$regId) respond(['success'=>false,'message'=>'id required'], 422);

        $userRole = $_SESSION['user_role'] ?? 'guest';
        $userId   = $_SESSION['user_id'];
        $userHosp = $_SESSION['user_hospital_id'] ?? null;

        if ($userRole !== 'headnurse' && $userRole !== 'manager' && $userRole !== 'admin') {
            respond(['success'=>false,'message'=>'Forbidden'], 403);
        }

        $pdo = getDB();
        $regStmt = $pdo->prepare("
            SELECT r.guest_hospital, a.hospital_id AS activity_hospital_id
            FROM registrations r
            JOIN activities a ON r.activity_id = a.id
            WHERE r.id = ?
        ");
        $regStmt->execute([$regId]);
        $reg = $regStmt->fetch();
        if (!$reg) respond(['success'=>false,'message'=>'Registration not found.'], 404);

        $isSenderCoord = ($userHosp && $userHosp == $reg['guest_hospital']);
        $isHostCoord   = ($userHosp && $userHosp == $reg['activity_hospital_id']);

        if ($userRole === 'admin' || $userRole === 'manager') {
            $stmt = $pdo->prepare("UPDATE registrations SET is_approved = 2, approved_by_sender = 2, approved_by_host = 2 WHERE id = ?");
            $stmt->execute([$regId]);
        } else {
            if ($isSenderCoord && $isHostCoord) {
                $stmt = $pdo->prepare("UPDATE registrations SET is_approved = 2, approved_by_sender = 2, approved_by_host = 2 WHERE id = ?");
                $stmt->execute([$regId]);
            } elseif ($isSenderCoord) {
                $stmt = $pdo->prepare("UPDATE registrations SET is_approved = 2, approved_by_sender = 2 WHERE id = ?");
                $stmt->execute([$regId]);
            } elseif ($isHostCoord) {
                $stmt = $pdo->prepare("UPDATE registrations SET is_approved = 2, approved_by_host = 2 WHERE id = ?");
                $stmt->execute([$regId]);
            }
        }
        respond(['success'=>true, 'message'=>'Registration rejected successfully.']);

    default:
        respond(['success'=>false,'message'=>'Invalid action'], 400);
}