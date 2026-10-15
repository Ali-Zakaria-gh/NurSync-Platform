-- ============================================================
--  NurSync Medical Dashboard — MySQL Database Schema
--  Alahsa Health Cluster
-- ============================================================


-- Database tables follow:

-- ============================================================
-- 1. HOSPITALS
-- ============================================================
CREATE TABLE hospitals (
    id          VARCHAR(20)  PRIMARY KEY,          -- e.g. 'kfgh', 'mch'
    full_name   VARCHAR(100) NOT NULL,
    short_name  VARCHAR(60)  NOT NULL,
    color_hex   VARCHAR(7)   NOT NULL DEFAULT '#185289',
    address     VARCHAR(200),
    phone       VARCHAR(20),
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO hospitals (id, full_name, short_name, color_hex) VALUES
    ('kfgh',     'King Faisal General Hospital',            'King Faisal Gen.',         '#4753a2'),
    ('kfhh',     'King Fahad Hospital',                     'King Fahad',               '#5a8e63'),
    ('pscc',     'Prince Sultan Cardiac Center',            'Prince Sultan Cardiac',    '#e74c3c'),
    ('psbj',     'Prince Saud Bin Jalawy Hospital',         'Prince Saud Bin Jalawy',   '#1abc9c'),
    ('mch',      'Maternity & Children Hospital',           'Maternity & Children',     '#e84393'),
    ('mhh',      'Mental Health Hospital',                  'Mental Health',            '#8e44ad'),
    ('oyun',     'Oyun Hospital',                           'Oyun Hospital',            '#d35400'),
    ('jgh',      'Aljafar General Hospital',                'Aljafar General',          '#2c3e50'),
    ('ogh',      'Alomran General Hospital',                'Alomran General',          '#8d6e63'),
    ('alafaleq', 'Alafaleq Hospital',                       'Alafaleq Hospital',        '#f39c12'),
    ('bhdc',     'Blood & Hereditary Disease Center',       'Blood Hereditary Center',  '#c0392b'),
    ('ent',      'Aljabr Eye & ENT Hospital',               'Aljabr Eye & ENT',         '#3498db');


-- ============================================================
-- 2. ROLES
-- ============================================================
CREATE TABLE roles (
    id          TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(40) NOT NULL UNIQUE   -- 'admin', 'staff', 'headnurse', 'manager'
);

INSERT INTO roles (id, name) VALUES (1, 'admin'), (2, 'staff'), (3, 'headnurse'), (4, 'manager');


-- ============================================================
-- 3. USERS
-- ============================================================
CREATE TABLE users (
    id              INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    full_name       VARCHAR(120)    NOT NULL,
    email           VARCHAR(150)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,          -- bcrypt hash
    role_id         TINYINT UNSIGNED NOT NULL DEFAULT 2,
    hospital_id     VARCHAR(20),
    phone           VARCHAR(20),
    employee_id     VARCHAR(50),                   -- user employee id / number
    is_active       BOOLEAN         DEFAULT TRUE,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    last_login      TIMESTAMP       NULL,

    FOREIGN KEY (role_id)     REFERENCES roles(id),
    FOREIGN KEY (hospital_id) REFERENCES hospitals(id)
);

CREATE INDEX idx_users_email      ON users(email);
CREATE INDEX idx_users_hospital   ON users(hospital_id);


-- ============================================================
-- 4. ACTIVITIES (Events)
-- ============================================================
CREATE TABLE activities (
    id              INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    title           VARCHAR(150)    NOT NULL,
    poster_path     VARCHAR(255)    NULL,
    description     TEXT,
    hospital_id     VARCHAR(20)     NOT NULL,
    activity_date   DATE            NOT NULL,
    start_time      TIME,
    end_time        TIME,
    location        VARCHAR(200),
    max_attendees   SMALLINT UNSIGNED DEFAULT 0,   -- 0 = unlimited
    status          ENUM('upcoming','ongoing','completed','cancelled')
                                    DEFAULT 'upcoming',
    created_by      INT UNSIGNED,                  -- user who created it
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (hospital_id) REFERENCES hospitals(id),
    FOREIGN KEY (created_by)  REFERENCES users(id)
);

CREATE INDEX idx_activities_date      ON activities(activity_date);
CREATE INDEX idx_activities_hospital  ON activities(hospital_id);
CREATE INDEX idx_activities_status    ON activities(status);

-- Seed data matching the current hardcoded events
INSERT INTO activities (title, hospital_id, activity_date, status) VALUES
    ('Blood Donation Drive',    'bhdc',     '2026-04-02', 'upcoming'),
    ('IV Therapy Workshop',     'kfgh',     '2026-04-04', 'upcoming'),
    ('Nursing Orientation',     'mch',      '2026-04-04', 'upcoming'),
    ('Infection Control',       'kfhh',     '2026-04-07', 'upcoming'),
    ('Cardiac Care Basics',     'pscc',     '2026-04-11', 'upcoming'),
    ('ENT Equipment Training',  'ent',      '2026-04-11', 'upcoming'),
    ('Staff Meeting',           'psbj',     '2026-04-13', 'upcoming'),
    ('Annual Review',           'oyun',     '2026-04-15', 'upcoming'),
    ('Patient Safety Training', 'jgh',      '2026-05-06', 'upcoming'),
    ('Maternal Care',           'mch',      '2026-05-14', 'upcoming'),
    ('Leadership Workshop',     'ogh',      '2026-05-20', 'upcoming'),
    ('Mental Health Workshop',  'mhh',      '2026-06-03', 'upcoming'),
    ('Emergency Protocols',     'alafaleq', '2026-06-18', 'upcoming');


-- ============================================================
-- 5. REGISTRATIONS (Join Activity)
-- ============================================================
CREATE TABLE registrations (
    id              INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    activity_id     INT UNSIGNED    NOT NULL,
    user_id         INT UNSIGNED,                  -- NULL = guest (no login)
    guest_name      VARCHAR(120),                  -- if guest
    guest_email     VARCHAR(150),                  -- if guest
    employee_id     VARCHAR(50),                   -- user employee id
    phone           VARCHAR(20),                   -- user phone number
    guest_hospital  VARCHAR(150),                  -- hospital if guest
    department      VARCHAR(100),                  -- department
    registered_at   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    attended        BOOLEAN         DEFAULT FALSE, -- marked after event
    is_approved     TINYINT         NOT NULL DEFAULT 0, -- 0 = pending, 1 = approved, 2 = rejected
    approver_id     INT UNSIGNED    DEFAULT NULL,
    approved_by_sender TINYINT      NOT NULL DEFAULT 0,
    approved_by_host   TINYINT      NOT NULL DEFAULT 0,
    notes           TEXT,

    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE SET NULL,
    FOREIGN KEY (approver_id) REFERENCES users(id)      ON DELETE SET NULL,

    -- Prevent duplicate registrations for logged-in users
    UNIQUE KEY uq_user_activity (user_id, activity_id)
);

CREATE INDEX idx_reg_activity ON registrations(activity_id);
CREATE INDEX idx_reg_user     ON registrations(user_id);


-- ============================================================
-- 6. SLIDES (Resource Library)
-- ============================================================
CREATE TABLE slides (
    id              INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    title           VARCHAR(200)    NOT NULL,
    file_path       VARCHAR(500)    NOT NULL,       -- stored path/URL
    file_type       ENUM('pdf','ppt','pptx','doc','docx','other') DEFAULT 'pdf',
    file_size_kb    INT UNSIGNED,
    activity_id     INT UNSIGNED,                  -- linked activity (nullable)
    hospital_id     VARCHAR(20),
    uploaded_by     INT UNSIGNED,
    upload_date     TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    is_public       BOOLEAN         DEFAULT TRUE,
    download_count  INT UNSIGNED    DEFAULT 0,

    FOREIGN KEY (activity_id)  REFERENCES activities(id) ON DELETE SET NULL,
    FOREIGN KEY (hospital_id)  REFERENCES hospitals(id),
    FOREIGN KEY (uploaded_by)  REFERENCES users(id)
);

CREATE INDEX idx_slides_hospital  ON slides(hospital_id);
CREATE INDEX idx_slides_activity  ON slides(activity_id);


-- ============================================================
-- 6.5. BULLETINS (Periodical Bulletin)
-- ============================================================
CREATE TABLE bulletins (
    id          INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    file_path   VARCHAR(255)    NOT NULL,
    uploaded_by INT UNSIGNED,
    uploaded_at TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by)  REFERENCES users(id) ON DELETE SET NULL
);


-- ============================================================
-- 7. NOTIFICATIONS (Optional – future use)
-- ============================================================
CREATE TABLE notifications (
    id          INT UNSIGNED    PRIMARY KEY AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    message     VARCHAR(300)    NOT NULL,
    link        VARCHAR(300),
    is_read     BOOLEAN         DEFAULT FALSE,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_notif_user ON notifications(user_id, is_read);


-- ============================================================
-- 10. BULLETINS
-- ============================================================
CREATE TABLE IF NOT EXISTS bulletins (
    id          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    file_path   VARCHAR(255) NOT NULL,
    uploaded_by INT UNSIGNED NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);


-- ============================================================
-- USEFUL VIEWS
-- ============================================================

-- Upcoming activities with hospital name + attendee count
CREATE VIEW v_upcoming_activities AS
SELECT
    a.id,
    a.title,
    a.activity_date,
    a.start_time,
    a.location,
    a.max_attendees,
    a.status,
    h.id           AS hospital_id,
    h.short_name   AS hospital_name,
    h.color_hex,
    COUNT(r.id)    AS registered_count,
    DATEDIFF(a.activity_date, CURDATE()) AS days_until
FROM activities a
JOIN hospitals h ON a.hospital_id = h.id
LEFT JOIN registrations r ON a.id = r.activity_id
WHERE a.activity_date >= CURDATE()
  AND a.status != 'cancelled'
GROUP BY a.id, h.id
ORDER BY a.activity_date ASC;

-- Slides with related info
CREATE VIEW v_slides_library AS
SELECT
    s.id,
    s.title,
    s.file_path,
    s.file_type,
    s.file_size_kb,
    s.upload_date,
    s.download_count,
    h.short_name  AS hospital_name,
    h.color_hex,
    a.title       AS activity_title,
    a.activity_date,
    u.full_name   AS uploaded_by_name
FROM slides s
LEFT JOIN hospitals h ON s.hospital_id = h.id
LEFT JOIN activities a ON s.activity_id = a.id
LEFT JOIN users u ON s.uploaded_by = u.id
WHERE s.is_public = TRUE
ORDER BY s.upload_date DESC;
