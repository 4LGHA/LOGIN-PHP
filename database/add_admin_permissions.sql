-- Add admin permission columns to user_restrictions table
ALTER TABLE user_restrictions ADD COLUMN can_edit_users TINYINT(1) DEFAULT 0 AFTER can_delete;
ALTER TABLE user_restrictions ADD COLUMN can_activate_users TINYINT(1) DEFAULT 0 AFTER can_edit_users;
ALTER TABLE user_restrictions ADD COLUMN can_unlock_users TINYINT(1) DEFAULT 0 AFTER can_activate_users;
ALTER TABLE user_restrictions ADD COLUMN can_reset_passwords TINYINT(1) DEFAULT 0 AFTER can_unlock_users;

-- Set admin user permissions
UPDATE user_restrictions SET can_edit_users=1, can_activate_users=1, can_unlock_users=1, can_reset_passwords=1 WHERE user_id=1;
