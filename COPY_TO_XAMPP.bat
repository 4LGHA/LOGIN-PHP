@echo off
echo ================================================================================
echo                    COPY PROJECT TO XAMPP
echo ================================================================================
echo.
echo This will copy your project to C:\xampp\htdocs\login-system\
echo.
pause

echo.
echo Copying files...
xcopy /E /I /Y "%~dp0*" "C:\xampp\htdocs\login-system\"

echo.
echo ================================================================================
echo                    SUCCESS!
echo ================================================================================
echo.
echo Your project has been copied to: C:\xampp\htdocs\login-system\
echo.
echo Access your system at:
echo   http://localhost/login-system/
echo.
echo Login with:
echo   Username: admin
echo   Password: Admin@123
echo.
echo ================================================================================
echo.
pause

echo.
echo Opening browser...
start http://localhost/login-system/

