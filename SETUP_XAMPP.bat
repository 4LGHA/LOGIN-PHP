@echo off
echo ================================================================================
echo                    SETUP PROJECT IN XAMPP
echo ================================================================================
echo.
echo This will copy your project to C:\xampp\htdocs\login-system\
echo.
echo Current location: %~dp0
echo Target location: C:\xampp\htdocs\login-system\
echo.
pause

echo.
echo Checking if XAMPP exists...
if not exist "C:\xampp\" (
    echo ERROR: XAMPP not found at C:\xampp\
    echo Please install XAMPP first!
    pause
    exit /b 1
)

echo ✓ XAMPP found
echo.

echo Checking if htdocs exists...
if not exist "C:\xampp\htdocs\" (
    echo ERROR: htdocs folder not found!
    pause
    exit /b 1
)

echo ✓ htdocs found
echo.

echo Removing old login-system folder (if exists)...
if exist "C:\xampp\htdocs\login-system\" (
    rmdir /s /q "C:\xampp\htdocs\login-system\"
    echo ✓ Old folder removed
)

echo.
echo Creating new login-system folder...
mkdir "C:\xampp\htdocs\login-system\"

echo.
echo Copying files...
xcopy /E /I /Y "%~dp0*" "C:\xampp\htdocs\login-system\" /EXCLUDE:%~dp0exclude.txt

echo.
echo ================================================================================
echo                    ✓ SUCCESS!
echo ================================================================================
echo.
echo Your project has been copied to: C:\xampp\htdocs\login-system\
echo.
echo NEXT STEPS:
echo.
echo 1. Make sure XAMPP Apache and MySQL are running
echo 2. Open browser and go to: http://localhost/login-system/
echo 3. You should see the login page
echo.
echo AVAILABLE PAGES:
echo   - Login:        http://localhost/login-system/login.php
echo   - Register:     http://localhost/login-system/register.php
echo   - Admin Panel:  http://localhost/login-system/admin/dashboard.php
echo   - User Panel:   http://localhost/login-system/user/dashboard.php
echo.
echo DEFAULT CREDENTIALS:
echo   Admin:     admin / Admin@123
echo   Test User: testuser / User@123
echo.
echo ================================================================================
echo.
pause

echo.
echo Opening browser...
start http://localhost/login-system/

