<?php
// ============================================================
//  Auth API – NurSync
// ============================================================

require_once __DIR__ . '/config/db.php';

session_start();

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { respond([], 200); }

$action = $_GET['action'] ?? '';
$input  = getInput();

switch ($action) {
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);

        $email    = trim($input['email']    ?? '');
        $password =      $input['password'] ?? '';

        if (!$email || !$password) {
            respond(['success'=>false,'message'=>'Email and password are required.'], 422);
        }

        $pdo  = getDB();
        $ip   = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $cleanEmail = strtolower(trim($email));

        // ── Server-Side Rate Limiting & Brute Force Protection (5 attempts / 15 mins) ──
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS login_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    ip_address VARCHAR(45) NOT NULL,
                    email VARCHAR(150) NOT NULL,
                    attempt_time DATETIME NOT NULL,
                    INDEX idx_ip_time (ip_address, attempt_time),
                    INDEX idx_email_time (email, attempt_time)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            // Purge old attempts (> 1 hour)
            $pdo->exec("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

            // Check failed attempts for this IP or Email within last 15 minutes
            $chkAttempts = $pdo->prepare("
                SELECT COUNT(*) FROM login_attempts 
                WHERE (ip_address = ? OR email = ?) 
                  AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $chkAttempts->execute([$ip, $cleanEmail]);
            if ((int)$chkAttempts->fetchColumn() >= 5) {
                respond([
                    'success' => false,
                    'message' => 'Too many failed login attempts. Please try again after 15 minutes.'
                ], 429);
            }
        } catch (Exception $e) {
            // Fail-safe: continue without blocking legitimate users if table operation glitches
        }

        $stmt = $pdo->prepare(
            "SELECT u.id, u.full_name, u.email, u.password_hash, u.hospital_id, u.phone, u.employee_id, u.department, u.is_active,
                    COALESCE(r.name, 'admin') AS role, h.short_name AS hospital_name
             FROM users u
             LEFT JOIN roles r ON u.role_id = r.id
             LEFT JOIN hospitals h ON u.hospital_id = h.id
             WHERE LOWER(TRIM(u.email)) = LOWER(?)"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Record failed attempt
            try {
                $recStmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())");
                $recStmt->execute([$ip, $cleanEmail]);
            } catch (Exception $e) {}

            respond(['success'=>false,'message'=>'Invalid email or password.'], 401);
        }

        // On successful authentication, reset failed attempts
        try {
            $clearStmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ? OR email = ?");
            $clearStmt->execute([$ip, $cleanEmail]);
        } catch (Exception $e) {}

        session_regenerate_id(true);

        if ($user['is_active'] == 0) {
            respond(['success'=>false,'message'=>'Your account is pending approval from the AHC Coordinator.'], 403);
        }

        // Block regular nurse/staff accounts from logging in
        if ($user['role'] === 'staff' || $user['role'] === 'nurse') {
            respond(['success'=>false,'message'=>'Nurses do not need to log in to use the platform. Access is restricted to Coordinators & Administrators.'], 403);
        }

        // Grant full admin privileges to any AHC hospital staff (requested by user)
        if (strtolower($user['hospital_id']) === 'ahc') {
            $user['role'] = 'admin';
        }

        // Update last login
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

        // Store session
        $_SESSION['user_id']          = $user['id'];
        $_SESSION['user_name']        = $user['full_name'];
        $_SESSION['user_role']        = $user['role'];
        $_SESSION['user_hospital_id'] = $user['hospital_id'];

        unset($user['password_hash']); // Never return hash
        respond(['success'=>true,'user'=>$user]);

    case 'update_profile':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);
        $userId = (int)($_SESSION['user_id'] ?? $input['user_id'] ?? 0);
        if (!$userId) respond(['success'=>false,'message'=>'Unauthorized. Please login.'], 401);

        $fullName = htmlspecialchars(strip_tags(trim($input['full_name'] ?? '')), ENT_QUOTES, 'UTF-8');
        $phone    = htmlspecialchars(strip_tags(trim($input['phone'] ?? '')), ENT_QUOTES, 'UTF-8');

        if (!$fullName || !$phone) {
            respond(['success'=>false,'message'=>'Name and phone are required.'], 422);
        }

        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$fullName, $phone, $userId]);
        
        $_SESSION['user_name'] = $fullName;
        
        respond(['success'=>true,'message'=>'Profile updated successfully.']);

    case 'update_password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);
        $userId = (int)($_SESSION['user_id'] ?? $input['user_id'] ?? 0);
        if (!$userId) respond(['success'=>false,'message'=>'Unauthorized. Please login.'], 401);

        $currentPassword = $input['current_password'] ?? '';
        $newPassword     = $input['new_password'] ?? '';

        if (!$currentPassword || !$newPassword) {
            respond(['success'=>false,'message'=>'Both current and new passwords are required.'], 422);
        }

        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            respond(['success'=>false,'message'=>'Incorrect current password.'], 401);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $updateStmt->execute([$newHash, $userId]);

        respond(['success'=>true,'message'=>'Password updated successfully.']);

    case 'logout':
        session_destroy();
        respond(['success'=>true,'message'=>'Logged out.']);

    case 'me':
        $userId = (int)($_SESSION['user_id'] ?? $_GET['user_id'] ?? 0);
        if (!$userId) {
            respond(['success'=>false,'logged_in'=>false]);
        }
        $pdo  = getDB();
        $stmt = $pdo->prepare(
            "SELECT u.id, u.full_name, u.email, u.hospital_id, u.phone, u.employee_id, u.department,
                    r.name AS role, h.short_name AS hospital_name
             FROM users u
             JOIN roles r ON u.role_id = r.id
             LEFT JOIN hospitals h ON u.hospital_id = h.id
             WHERE u.id = ?"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) { 
            respond(['success'=>false,'logged_in'=>false]); 
        }
        if (strtolower((string)$user['hospital_id']) === 'ahc') {
            $user['role'] = 'admin';
        }
        respond(['success'=>true,'logged_in'=>true,'user'=>$user]);

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'message'=>'POST required'], 405);

        $name       = htmlspecialchars(strip_tags(trim($input['full_name']   ?? '')), ENT_QUOTES, 'UTF-8');
        $email      = htmlspecialchars(strip_tags(trim($input['email']       ?? '')), ENT_QUOTES, 'UTF-8');
        $password   =      $input['password']    ?? '';
        $hospital   =      $input['hospital_id'] ?? null;
        $emp_id     = null; // Cancelled
        $phone      = htmlspecialchars(strip_tags(trim($input['phone']       ?? '')), ENT_QUOTES, 'UTF-8');
        $department = null; // Cancelled

        if (!$name || !$email || !$password) {
            respond(['success'=>false,'message'=>'Name, email and password are required.'], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            respond(['success'=>false,'message'=>'Invalid email format.'], 422);
        }

        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            respond(['success'=>false,'message'=>'Password must be at least 8 characters long and contain both letters and numbers.'], 422);
        }

        $pdo  = getDB();
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            respond(['success'=>false,'message'=>'Email already registered.'], 409);
        }

        $role_id = 3;
        $is_active = 0;
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, password_hash, hospital_id, phone, employee_id, role_id, department, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $email, $hash, $hospital ?: null, $phone, $emp_id, $role_id, $department, $is_active]);

        respond(['success'=>true,'message'=>'Account created successfully.'], 201);

    default:
        respond(['success'=>false,'message'=>'Unknown action.'], 400);
}