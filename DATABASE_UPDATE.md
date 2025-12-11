# Database Schema Update - Admin Permissions

## Issue
When editing users, the page displayed "No Content Found" error. This was caused by the database schema being out of sync with the application code.

## Root Cause
The application was updated to include 4 new admin permission columns in the `user_restrictions` table:
- `can_edit_users`
- `can_activate_users`  
- `can_unlock_users`
- `can_reset_passwords`

However, the database table didn't have these columns, causing the SELECT query in `admin/edit-user.php` to fail.

## Solution Applied
The following changes were made to the database:

### 1. Added Missing Columns
Four new TINYINT(1) columns were added to the `user_restrictions` table:
```sql
ALTER TABLE user_restrictions ADD COLUMN can_edit_users TINYINT(1) DEFAULT 0;
ALTER TABLE user_restrictions ADD COLUMN can_activate_users TINYINT(1) DEFAULT 0;
ALTER TABLE user_restrictions ADD COLUMN can_unlock_users TINYINT(1) DEFAULT 0;
ALTER TABLE user_restrictions ADD COLUMN can_reset_passwords TINYINT(1) DEFAULT 0;
```

### 2. Updated Admin Permissions
Set all admin permissions to 1 for the admin user (user_id=1):
```sql
UPDATE user_restrictions SET can_edit_users=1, can_activate_users=1, can_unlock_users=1, can_reset_passwords=1 WHERE user_id=1;
```

### 3. Fixed Data Issues
Removed duplicate entries in the `user_restrictions` table for user_id=4 that were created during earlier development.

## Code Changes
Modified `admin/edit-user.php` to gracefully handle missing columns using COALESCE:
```php
// Uses COALESCE to default NULL values to 0
// This allows the code to work even if columns don't exist yet
SELECT u.*, 
    COALESCE(ur.can_add, 0) as can_add, 
    COALESCE(ur.can_edit, 0) as can_edit, 
    COALESCE(ur.can_view, 1) as can_view, 
    COALESCE(ur.can_delete, 0) as can_delete, 
    COALESCE(ur.can_edit_users, 0) as can_edit_users, 
    COALESCE(ur.can_activate_users, 0) as can_activate_users, 
    COALESCE(ur.can_unlock_users, 0) as can_unlock_users, 
    COALESCE(ur.can_reset_passwords, 0) as can_reset_passwords
FROM users u
LEFT JOIN user_restrictions ur ON u.id = ur.user_id
WHERE u.id = ?
```

Also added exception handling to fall back to default permissions if the new columns don't exist.

## Verification
Current database schema is now up to date:
```
Field               | Type
--------------------|----------
id                  | int(11)
user_id             | int(11)
can_add             | tinyint(1)
can_edit            | tinyint(1)
can_view            | tinyint(1)
can_delete          | tinyint(1)
can_edit_users      | tinyint(1)  ← NEW
can_activate_users  | tinyint(1)  ← NEW
can_unlock_users    | tinyint(1)  ← NEW
can_reset_passwords | tinyint(1)  ← NEW
created_at          | timestamp
updated_at          | timestamp
```

## Deployment Notes
For other installations:
1. Run the migration script in `database/migrate_admin_permissions.sql`
2. Or use the `database/add_admin_permissions.sql` file
3. Execute: `mysql -u root login_system < database/add_admin_permissions.sql`

The application code now has fallback handling for older databases missing these columns.
