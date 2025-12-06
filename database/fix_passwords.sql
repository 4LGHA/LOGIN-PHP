-- ============================================
-- FIX PASSWORD HASHES FOR DEFAULT ACCOUNTS
-- Run this if you already imported the database
-- ============================================

USE login_system;

-- Update admin password to: Admin@123
UPDATE users 
SET password = '$2y$10$UdWvl/38DktaoY5JDtF/G.LFw5BGD8651P2vSLpPl3HlKUoc93xD2'
WHERE username = 'admin';

-- Update testuser password to: User@123
UPDATE users 
SET password = '$2y$10$YNMMS2KZcZhJBCxRxTeVkeZiht0cmP/zGhaECCEosjfCnSyC2.5ue'
WHERE username = 'testuser';

-- Reset any locked accounts
UPDATE users 
SET is_locked = 0, failed_attempts = 0 
WHERE username IN ('admin', 'testuser');

-- Verify the update
SELECT username, email, user_level, is_active, is_locked, failed_attempts 
FROM users 
WHERE username IN ('admin', 'testuser');

