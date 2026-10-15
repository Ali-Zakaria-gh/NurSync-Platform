<?php
// ============================================================
//  Database Migration Script — NurSync
//  Run this file to set up the new roles and tables.
// ============================================================

// Protect against unauthenticated public web execution
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Forbidden: Migration scripts can only be executed via CLI.');
}

require_once __DIR__ . '/db.php';

try {
    $pdo = getDB();

    echo "Starting database migrations...\n";

    // 0. Seed Health Cluster into hospitals table
    echo "Seeding 'ahc' and 'phc' into hospitals table...\n";
    $pdo->exec("INSERT INTO hospitals (id, full_name, short_name, color_hex) VALUES ('ahc', 'Alahsa Health Cluster', 'Health Cluster', '#185289') ON DUPLICATE KEY UPDATE full_name='Alahsa Health Cluster', short_name='Health Cluster'");
    $pdo->exec("INSERT INTO hospitals (id, full_name, short_name, color_hex) VALUES ('phc', 'Primary Health Center', 'Primary Health Center (PHC)', '#16a085') ON DUPLICATE KEY UPDATE full_name='Primary Health Center', short_name='Primary Health Center (PHC)'");

    // Update KFGH and OGH to more distinct colors (Violet-Indigo and Warm Cocoa)
    echo "Updating 'kfgh' and 'ogh' colors to distinct medical color variables...\n";
    $pdo->exec("UPDATE hospitals SET color_hex='#4753a2' WHERE id='kfgh'");
    $pdo->exec("UPDATE hospitals SET color_hex='#8d6e63' WHERE id='ogh'");

    // 1. Setup new roles
    echo "Updating and seeding roles table...\n";

    // Ensure the table handles custom insertion IDs or just updates names
    $pdo->exec("INSERT INTO roles (id, name) VALUES (1, 'admin') ON DUPLICATE KEY UPDATE name='admin'");
    $pdo->exec("INSERT INTO roles (id, name) VALUES (2, 'staff') ON DUPLICATE KEY UPDATE name='staff'");
    $pdo->exec("INSERT INTO roles (id, name) VALUES (3, 'headnurse') ON DUPLICATE KEY UPDATE name='headnurse'");
    $pdo->exec("INSERT INTO roles (id, name) VALUES (4, 'manager') ON DUPLICATE KEY UPDATE name='manager'");

    echo "Roles seeded successfully.\n";

    // 2. Create bulletins table
    echo "Creating 'bulletins' table if it does not exist...\n";
    $sqlBulletins = "
    CREATE TABLE IF NOT EXISTS bulletins (
        id          INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
        file_path   VARCHAR(255)    NOT NULL,
        uploaded_by INT UNSIGNED,
        uploaded_at TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by)  REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($sqlBulletins);
    echo "'bulletins' table checked/created successfully.\n";

    // 3. Update registrations table for supervisor approvals and double approvals
    echo "Checking/updating 'registrations' table structure...\n";
    $q1 = $pdo->query("SHOW COLUMNS FROM registrations LIKE 'is_approved'");
    if (!$q1->fetch()) {
        $pdo->exec("ALTER TABLE registrations ADD COLUMN is_approved BOOLEAN NOT NULL DEFAULT FALSE");
        echo "Added column 'is_approved' to registrations table.\n";
    } else {
        echo "Column 'is_approved' already exists.\n";
    }

    $q2 = $pdo->query("SHOW COLUMNS FROM registrations LIKE 'approver_id'");
    if (!$q2->fetch()) {
        // First add the column, then add the foreign key
        $pdo->exec("ALTER TABLE registrations ADD COLUMN approver_id INT UNSIGNED DEFAULT NULL");
        $pdo->exec("ALTER TABLE registrations ADD CONSTRAINT fk_registrations_approver FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE SET NULL");
        echo "Added column 'approver_id' and foreign key to registrations table.\n";
    } else {
        echo "Column 'approver_id' already exists.\n";
    }

    $qCheckSender = $pdo->query("SHOW COLUMNS FROM registrations LIKE 'approved_by_sender'");
    if (!$qCheckSender->fetch()) {
        $pdo->exec("ALTER TABLE registrations ADD COLUMN approved_by_sender TINYINT NOT NULL DEFAULT 0");
        echo "Added column 'approved_by_sender' to registrations table.\n";
    } else {
        echo "Column 'approved_by_sender' already exists.\n";
    }

    $qCheckHost = $pdo->query("SHOW COLUMNS FROM registrations LIKE 'approved_by_host'");
    if (!$qCheckHost->fetch()) {
        $pdo->exec("ALTER TABLE registrations ADD COLUMN approved_by_host TINYINT NOT NULL DEFAULT 0");
        echo "Added column 'approved_by_host' to registrations table.\n";
    } else {
        echo "Column 'approved_by_host' already exists.\n";
    }

    // 4. Update users table for department field
    echo "Checking/updating 'users' table structure...\n";
    $q3 = $pdo->query("SHOW COLUMNS FROM users LIKE 'department'");
    if (!$q3->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL");
        echo "Added column 'department' to users table.\n";
    } else {
        echo "Column 'department' already exists.\n";
    }

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
