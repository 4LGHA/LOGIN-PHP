# 🔐 Secure PHP Login System with XAMPP

A comprehensive and secure login system built with PHP and MySQL for XAMPP localhost environment. This system includes advanced security features, user management, and activity tracking.

## ✨ Features

### 1. **Password Security with Strength Checker**
- ✅ Minimum 8 characters
- ✅ At least one uppercase letter
- ✅ At least one lowercase letter
- ✅ At least one number
- ✅ At least one special character
- ✅ Real-time password strength indicator (Weak/Medium/Strong)
- ✅ Visual feedback with color-coded progress bar

### 2. **Account Lockout Policy (Login Attempts System)**
- ✅ Maximum 3 failed login attempts
- ✅ Automatic account lockout after 3 failed attempts
- ✅ Only admin can unlock locked accounts
- ✅ Failed attempts counter displayed to users
- ✅ Complete login attempt history tracking

### 3. **Admin User Management Module**
- ✅ View all users with detailed information
- ✅ Register new users with custom permissions
- ✅ Edit user information (username, email, full name)
- ✅ Activate/Deactivate user accounts
- ✅ Unlock locked user accounts
- ✅ Reset user passwords
- ✅ Delete users (with protection against self-deletion)
- ✅ Manage user restrictions (Add, Edit, View, Delete permissions)

### 4. **User Self-Registration**
- ✅ Public registration page for new users
- ✅ Users can create their own accounts
- ✅ Choose username and password
- ✅ Real-time password strength meter
- ✅ Select user level (Admin/Regular User)
- ✅ Set custom restrictions (Add, Edit, View, Delete)
- ✅ Email validation
- ✅ Duplicate username/email prevention
- ✅ Immediate account activation

### 5. **Additional Security Features**
- ✅ Password hashing with bcrypt (PASSWORD_DEFAULT)
- ✅ SQL injection prevention using PDO prepared statements
- ✅ CSRF token protection
- ✅ XSS protection with input sanitization
- ✅ Session management
- ✅ Activity logging
- ✅ IP address tracking

### 6. **User Interface**
- ✅ Modern, responsive Bootstrap 5 design
- ✅ Separate Admin and User dashboards
- ✅ Real-time form validation
- ✅ Flash messages for user feedback
- ✅ DataTables for sortable, searchable tables
- ✅ Mobile-friendly responsive layout

## 📋 Requirements

- XAMPP (Apache + MySQL + PHP 7.4 or higher)
- Web browser (Chrome, Firefox, Edge, Safari)

## 🚀 Installation Instructions

### Step 1: Install XAMPP
1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP on your computer
3. Start Apache and MySQL from XAMPP Control Panel

### Step 2: Setup Project Files
1. Copy the entire project folder to `C:\xampp\htdocs\`
2. Rename the folder to `login-system` (or your preferred name)
3. Your project path should be: `C:\xampp\htdocs\login-system\`

### Step 3: Create Database
1. Open your web browser
2. Go to: `http://localhost/phpmyadmin`
3. Click on "Import" tab
4. Click "Choose File" and select `database/schema.sql` from the project folder
5. Click "Go" to import the database

**OR** you can create manually:
1. Go to: `http://localhost/phpmyadmin`
2. Click "New" to create a new database
3. Name it: `login_system`
4. Click "SQL" tab
5. Copy and paste the contents of `database/schema.sql`
6. Click "Go"

**⚠️ IMPORTANT:** If you experience login issues, the password hashes may need to be updated. See `QUICK_FIX.txt` or `FIX_LOGIN_ISSUE.txt` for solutions.

### Step 4: Configure Database Connection (Optional)
The default configuration works with XAMPP. If you need to change it:
1. Open `config/database.php`
2. Modify these settings if needed:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'login_system');
```

### Step 5: Access the System
1. Open your web browser
2. Go to: `http://localhost/login-system/`
3. You will be redirected to the login page

## 🔑 Default Login Credentials

### Administrator Account
- **Username:** `admin`
- **Password:** `Admin@123`
- **Access:** Full system access, user management

### Regular User Account
- **Username:** `testuser`
- **Password:** `User@123`
- **Access:** Limited user dashboard

## 📁 Project Structure

```
login-system/
├── admin/                      # Admin panel files
│   ├── includes/              # Admin header/footer
│   ├── dashboard.php          # Admin dashboard
│   ├── users.php              # User management
│   ├── add-user.php           # Add new user
│   ├── edit-user.php          # Edit user
│   ├── login-attempts.php     # (REMOVED - Use Dashboard & Activity Log instead)
│   └── activity-log.php       # Activity log
├── user/                       # User panel files
│   ├── includes/              # User header/footer
│   ├── dashboard.php          # User dashboard
│   └── change-password.php    # Change password
├── assets/                     # Static assets
│   ├── css/
│   │   └── style.css          # Custom styles
│   └── js/
│       ├── main.js            # Main JavaScript
│       └── password-strength.js # Password checker
├── config/                     # Configuration files
│   └── database.php           # Database connection
├── database/                   # Database files
│   └── schema.sql             # Database schema
├── includes/                   # Shared PHP files
│   ├── auth.php               # Authentication functions
│   └── functions.php          # Helper functions
├── index.php                   # Entry point (redirects to login)
├── login.php                   # Login page
├── logout.php                  # Logout handler
└── README.md                   # This file
```

## 🎯 Usage Guide

### For Administrators

1. **Login** with admin credentials
2. **Dashboard** shows system statistics and recent activities
3. **Manage Users:**
   - Click "Manage Users" in sidebar
   - View all users with their status and permissions
   - Add new users with custom permissions
   - Edit existing users
   - Activate/Deactivate accounts
   - Unlock locked accounts
   - Reset passwords (default: Password@123)
4. **Monitor Activity:**
   - View login attempts
   - Track user activities
   - Monitor security events

### For Regular Users

1. **Login** with user credentials
2. **Dashboard** shows account information and recent activities
3. **Change Password:**
   - Click "Change Password" in sidebar
   - Enter current password
   - Enter new password (must meet requirements)
   - Confirm new password

## 🔒 Security Features Explained

### Password Requirements
- Enforced both client-side (JavaScript) and server-side (PHP)
- Real-time visual feedback
- Prevents weak passwords

### Account Lockout
- Tracks failed login attempts per user
- Locks account after 3 consecutive failures
- Requires admin intervention to unlock
- Prevents brute force attacks

### CSRF Protection
- Tokens generated for each session
- Validated on all form submissions
- Prevents cross-site request forgery

### SQL Injection Prevention
- PDO with prepared statements
- Parameter binding for all queries
- No direct SQL string concatenation

### XSS Protection
- All output is escaped with htmlspecialchars()
- Input sanitization on all user data
- Prevents script injection

## 📊 Database Tables

- **users** - User accounts and authentication
- **user_restrictions** - User permissions (Add, Edit, View, Delete)
- **login_attempts** - Login attempt history
- **user_sessions** - Active user sessions
- **activity_log** - System activity tracking

## 🛠️ Troubleshooting

### Database Connection Error
- Ensure MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Verify database `login_system` exists

### Page Not Found (404)
- Check project folder is in `htdocs`
- Verify URL: `http://localhost/login-system/`
- Ensure Apache is running

### Login Not Working
- Clear browser cache and cookies
- Check database has default users
- Verify password: `Admin@123` or `User@123`

### Permission Denied
- Check file permissions
- Ensure PHP has write access to session directory

## 📝 Customization

### Change Default Passwords
1. Login as admin
2. Go to "Manage Users"
3. Click edit on the user
4. Enter new password
5. Save changes

### Modify Password Requirements
Edit `includes/functions.php` - `validatePassword()` function

### Change Lockout Attempts
Edit `includes/auth.php` - Change `3` to desired number in `attemptLogin()` function

### Customize Appearance
Edit `assets/css/style.css` for styling changes

## 📄 License

This project is open-source and free to use for educational and commercial purposes.

## 👨‍💻 Support

For issues or questions:
1. Check the troubleshooting section
2. Review the code comments
3. Check XAMPP error logs in `C:\xampp\apache\logs\`

## 🎉 Features Checklist

- ✅ Password template with strength checker
- ✅ Login attempts system (3 max attempts)
- ✅ Account lockout policy
- ✅ Admin user management module
- ✅ User registration with restrictions
- ✅ Activate/Deactivate accounts
- ✅ Unlock locked users
- ✅ Reset passwords
- ✅ Change username and password
- ✅ User levels (Admin/User)
- ✅ Custom restrictions (Add/Edit/View/Delete)

---

**Developed with ❤️ for secure authentication**

