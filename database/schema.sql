    -- ============================================
-- LOGIN SYSTEM DATABASE SCHEMA
-- ============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS login_system;
USE login_system;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    user_level ENUM('admin', 'user') DEFAULT 'user',
    is_active TINYINT(1) DEFAULT 1,
    is_locked TINYINT(1) DEFAULT 0,
    failed_attempts INT DEFAULT 0,
    last_failed_attempt DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_is_active (is_active),
    INDEX idx_is_locked (is_locked)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- USER RESTRICTIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS user_restrictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    can_add TINYINT(1) DEFAULT 0,
    can_edit TINYINT(1) DEFAULT 0,
    can_view TINYINT(1) DEFAULT 1,
    can_delete TINYINT(1) DEFAULT 0,
    can_edit_users TINYINT(1) DEFAULT 0,
    can_activate_users TINYINT(1) DEFAULT 0,
    can_unlock_users TINYINT(1) DEFAULT 0,
    can_reset_passwords TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- LOGIN ATTEMPTS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    failure_reason VARCHAR(100) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempt_time (attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- USER SESSIONS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ACTIVITY LOG TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- USER NOTES TABLE (For testing permissions)
-- ============================================
CREATE TABLE IF NOT EXISTS user_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERT DEFAULT ADMIN USER
-- Password: Admin@123 (hashed with PASSWORD_DEFAULT)
-- ============================================
INSERT INTO users (username, password, email, full_name, user_level, is_active, is_locked)
VALUES (
    'admin',
    '$2y$10$UdWvl/38DktaoY5JDtF/G.LFw5BGD8651P2vSLpPl3HlKUoc93xD2',
    'admin@localhost.com',
    'System Administrator',
    'admin',
    1,
    0
);

-- Insert admin restrictions (full access)
INSERT INTO user_restrictions (user_id, can_add, can_edit, can_view, can_delete, can_edit_users, can_activate_users, can_unlock_users, can_reset_passwords) 
VALUES (1, 1, 1, 1, 1, 1, 1, 1, 1);

-- ============================================
-- INSERT SAMPLE REGULAR USER
-- Password: User@123 (hashed with PASSWORD_DEFAULT)
-- ============================================
INSERT INTO users (username, password, email, full_name, user_level, is_active, is_locked)
VALUES (
    'testuser',
    '$2y$10$YNMMS2KZcZhJBCxRxTeVkeZiht0cmP/zGhaECCEosjfCnSyC2.5ue',
    'user@localhost.com',
    'Test User',
    'user',
    1,
    0
);

-- Insert user restrictions (view only)
INSERT INTO user_restrictions (user_id, can_add, can_edit, can_view, can_delete, can_edit_users, can_activate_users, can_unlock_users, can_reset_passwords) 
VALUES (2, 0, 0, 1, 0, 0, 0, 0, 0);

