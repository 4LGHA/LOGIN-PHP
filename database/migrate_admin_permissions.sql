-- Add new admin permission columns to user_restrictions table if they don't exist
-- This migration handles both new and existing installations

-- Check and add can_edit_users column
ALTER TABLE user_restrictions ADD COLUMN IF NOT EXISTS can_edit_users TINYINT(1) DEFAULT 0;

-- Check and add can_activate_users column
ALTER TABLE user_restrictions ADD COLUMN IF NOT EXISTS can_activate_users TINYINT(1) DEFAULT 0;

-- Check and add can_unlock_users column
ALTER TABLE user_restrictions ADD COLUMN IF NOT EXISTS can_unlock_users TINYINT(1) DEFAULT 0;

-- Check and add can_reset_passwords column
ALTER TABLE user_restrictions ADD COLUMN IF NOT EXISTS can_reset_passwords TINYINT(1) DEFAULT 0;

-- Update admin user (ID 1) to have all admin permissions
UPDATE user_restrictions 
SET can_edit_users = 1, can_activate_users = 1, can_unlock_users = 1, can_reset_passwords = 1 
WHERE user_id = 1;
