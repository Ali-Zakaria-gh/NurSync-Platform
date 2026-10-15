<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? '';

// Resolve authenticated user ID
$userId = (int)($_SESSION['user_id'] ?? $_GET['auth_user_id'] ?? $_GET['user_id'] ?? $input['auth_user_id'] ?? 0);
if (!$userId && isset($input['user_id']) && !in_array($action, ['approve', 'reject'])) {
    $userId = (int)$input['user_id'];
}

if (!$userId) {
    respond(['success' => false, 'message' => 'Unauthorized. Please login.'], 401);
}

$pdo = getDB();

// Ensure profile_update_requests table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS profile_update_requests (
    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    hospital_id VARCHAR(20) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by INT UNSIGNED NULL
)");

// Fetch user data to verify and establish role/hospital
$uStmt = $pdo->prepare("SELECT id, full_name, email, hospital_id, role_id FROM users WHERE id = ?");
$uStmt->execute([$userId]);
$userData = $uStmt->fetch();

if (!$userData) {
    respond(['success' => false, 'message' => 'User account not found. Please log in again.'], 401);
}

$userHospital = $userData['hospital_id'];
$isAhcAdmin = ($userData['role_id'] == 1 || strtolower((string)$userHospital) === 'ahc' || ($_SESSION['user_role'] ?? '') === 'admin');

switch ($action) {
    // -------------------------------------------------------------------------
    // 1. Coordinator: Request Profile Update
    // -------------------------------------------------------------------------
    case 'request_profile_update':
        $name     = htmlspecialchars(strip_tags(trim($input['full_name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $email    = htmlspecialchars(strip_tags(trim($input['email'] ?? '')), ENT_QUOTES, 'UTF-8');
        $phone    = htmlspecialchars(strip_tags(trim($input['phone'] ?? '')), ENT_QUOTES, 'UTF-8');
        $hospital = htmlspecialchars(strip_tags(trim($input['hospital_id'] ?? '')), ENT_QUOTES, 'UTF-8');

        if (!$name || !$email || !$phone || !$hospital) {
            respond(['success' => false, 'message' => 'All fields are required.'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(['success' => false, 'message' => 'Invalid email address format.'], 422);
        }

        // Check if email belongs to someone else
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->execute([$email, $userId]);
        if ($chk->fetch()) {
            respond(['success' => false, 'message' => 'This email address is already registered to another user.'], 409);
        }

        // Check if user already has an existing pending request
        $existingStmt = $pdo->prepare("SELECT id FROM profile_update_requests WHERE user_id = ? AND status = 'pending' LIMIT 1");
        $existingStmt->execute([$userId]);
        $existingId = $existingStmt->fetchColumn();

        if ($existingId) {
            $upStmt = $pdo->prepare("UPDATE profile_update_requests SET full_name = ?, email = ?, phone = ?, hospital_id = ?, created_at = NOW() WHERE id = ?");
            $upStmt->execute([$name, $email, $phone, $hospital, $existingId]);
        } else {
            $insStmt = $pdo->prepare("INSERT INTO profile_update_requests (user_id, full_name, email, phone, hospital_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $insStmt->execute([$userId, $name, $email, $phone, $hospital]);
        }

        respond(['success' => true, 'message' => 'Your profile update request has been submitted for AHC approval.']);

    // -------------------------------------------------------------------------
    // 2. Coordinator: Check My Pending Update Status
    // -------------------------------------------------------------------------
    case 'my_pending_update':
        $pStmt = $pdo->prepare("SELECT r.*, h.short_name AS hospital_name 
                                FROM profile_update_requests r
                                LEFT JOIN hospitals h ON r.hospital_id = h.id
                                WHERE r.user_id = ? AND r.status = 'pending'
                                ORDER BY r.created_at DESC LIMIT 1");
        $pStmt->execute([$userId]);
        $req = $pStmt->fetch();

        respond([
            'success' => true,
            'has_pending' => !empty($req),
            'data' => $req ?: null
        ]);

    // -------------------------------------------------------------------------
    // 3. AHC Coordinator / Admin: List All Pending Requests (New Accounts & Profile Updates)
    // -------------------------------------------------------------------------
    case 'pending':
        if (!$isAhcAdmin) {
            respond(['success' => false, 'message' => 'Forbidden. Only AHC Coordinators and Admins can access this.'], 403);
        }

        // A) Pending New Account Registrations
        $newAccStmt = $pdo->query(
            "SELECT u.id, u.id AS user_id, u.full_name, u.email, u.phone, u.created_at, u.hospital_id,
                    h.short_name AS hospital_name, 'new_account' AS request_type
             FROM users u 
             LEFT JOIN hospitals h ON u.hospital_id = h.id 
             WHERE u.is_active = 0 AND u.role_id = 3
             ORDER BY u.created_at DESC"
        );
        $newAccounts = $newAccStmt->fetchAll();

        // B) Pending Profile Update Requests
        $profUpStmt = $pdo->query(
            "SELECT r.id AS request_id, r.user_id, r.full_name AS requested_name, r.email AS requested_email,
                    r.phone AS requested_phone, r.hospital_id AS requested_hospital_id,
                    rh.short_name AS requested_hospital_name, r.created_at, 'profile_update' AS request_type,
                    u.full_name AS current_name, u.email AS current_email, u.phone AS current_phone,
                    u.hospital_id AS current_hospital_id, ch.short_name AS current_hospital_name
             FROM profile_update_requests r
             JOIN users u ON r.user_id = u.id
             LEFT JOIN hospitals rh ON r.hospital_id = rh.id
             LEFT JOIN hospitals ch ON u.hospital_id = ch.id
             WHERE r.status = 'pending'
             ORDER BY r.created_at DESC"
        );
        $profileUpdates = $profUpStmt->fetchAll();

        $allRequests = array_merge($newAccounts, $profileUpdates);
        
        // Sort combined list by created_at DESC
        usort($allRequests, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        respond(['success' => true, 'data' => $allRequests]);

    // -------------------------------------------------------------------------
    // 4. AHC: Approve New Account
    // -------------------------------------------------------------------------
    case 'approve':
        if (!$isAhcAdmin) respond(['success' => false, 'message' => 'Forbidden'], 403);
        $targetUserId = (int)($input['user_id'] ?? 0);
        if (!$targetUserId) respond(['success' => false, 'message' => 'User ID required'], 400);

        $pdo->prepare("UPDATE users SET is_active = 1 WHERE id = ?")->execute([$targetUserId]);
        respond(['success' => true, 'message' => 'Coordinator account approved successfully.']);

    // -------------------------------------------------------------------------
    // 5. AHC: Reject New Account
    // -------------------------------------------------------------------------
    case 'reject':
        if (!$isAhcAdmin) respond(['success' => false, 'message' => 'Forbidden'], 403);
        $targetUserId = (int)($input['user_id'] ?? 0);
        if (!$targetUserId) respond(['success' => false, 'message' => 'User ID required'], 400);

        $pdo->prepare("DELETE FROM users WHERE id = ? AND is_active = 0")->execute([$targetUserId]);
        respond(['success' => true, 'message' => 'Account registration rejected.']);

    // -------------------------------------------------------------------------
    // 6. AHC: Approve Profile Update Request
    // -------------------------------------------------------------------------
    case 'approve_profile_update':
        if (!$isAhcAdmin) respond(['success' => false, 'message' => 'Forbidden'], 403);
        $requestId = (int)($input['request_id'] ?? 0);
        if (!$requestId) respond(['success' => false, 'message' => 'Request ID required'], 400);

        $reqStmt = $pdo->prepare("SELECT * FROM profile_update_requests WHERE id = ? AND status = 'pending'");
        $reqStmt->execute([$requestId]);
        $req = $reqStmt->fetch();

        if (!$req) {
            respond(['success' => false, 'message' => 'Profile update request not found or already processed.'], 404);
        }

        // Apply changes to users table
        $updateUser = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, hospital_id = ? WHERE id = ?");
        $updateUser->execute([
            $req['full_name'],
            $req['email'],
            $req['phone'],
            $req['hospital_id'],
            $req['user_id']
        ]);

        // Mark request as approved
        $pdo->prepare("UPDATE profile_update_requests SET status = 'approved', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?")
            ->execute([$userId, $requestId]);

        // If the approver is editing themselves, update session name
        if ($req['user_id'] == $userId) {
            $_SESSION['user_name'] = $req['full_name'];
            $_SESSION['user_hospital_id'] = $req['hospital_id'];
        }

        respond(['success' => true, 'message' => 'Profile update approved and applied successfully.']);

    // -------------------------------------------------------------------------
    // 7. AHC: Reject Profile Update Request
    // -------------------------------------------------------------------------
    case 'reject_profile_update':
        if (!$isAhcAdmin) respond(['success' => false, 'message' => 'Forbidden'], 403);
        $requestId = (int)($input['request_id'] ?? 0);
        if (!$requestId) respond(['success' => false, 'message' => 'Request ID required'], 400);

        $pdo->prepare("UPDATE profile_update_requests SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ? WHERE id = ? AND status = 'pending'")
            ->execute([$userId, $requestId]);

        respond(['success' => true, 'message' => 'Profile update request rejected.']);

    default:
        respond(['success' => false, 'message' => 'Invalid action.'], 400);
}
