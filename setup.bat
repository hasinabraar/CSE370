@echo off
echo 🚀 Setting up Accident Detection and Hospital Alert System
echo ==================================================

REM Check if PHP is installed
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ PHP is not installed. Please install PHP 8.0+ first.
    pause
    exit /b 1
)

REM Check if Node.js is installed
node --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Node.js is not installed. Please install Node.js 16+ first.
    pause
    exit /b 1
)

echo ✅ Prerequisites check passed!

REM Setup Backend
echo.
echo 📦 Setting up PHP Backend...
cd backend

REM Install Composer dependencies
if exist "composer.json" (
    echo Installing PHP dependencies...
    composer install
) else (
    echo ❌ composer.json not found in backend directory
    pause
    exit /b 1
)

REM Create .env file from example
if not exist ".env" if exist "env.example" (
    echo Creating .env file from template...
    copy env.example .env
    echo ⚠️  Please update the .env file with your database credentials
)

cd ..

REM Setup Frontend
echo.
echo 📦 Setting up React Frontend...
cd frontend

REM Install Node.js dependencies
if exist "package.json" (
    echo Installing Node.js dependencies...
    npm install
) else (
    echo ❌ package.json not found in frontend directory
    pause
    exit /b 1
)

cd ..

echo.
echo 🎉 Setup completed!
echo.
echo 📋 Next steps:
echo 1. Update backend/.env with your database credentials
echo 2. Start the PHP backend: cd backend ^&^& php -S localhost:8000
echo 3. Start the React frontend: cd frontend ^&^& npm start
echo.
echo 🌐 The application will be available at:
echo    Frontend: http://localhost:3000
echo    Backend API: http://localhost:8000
echo.
echo 🔑 Demo credentials:
echo    User: john@example.com / password
echo    Hospital: hospital@citygeneral.com / password
echo    Admin: admin@system.com / password
echo.
pause
