# Portfolio Performance Optimization Script (Windows)
# Run this in PowerShell or Command Prompt

@echo off
echo 🚀 Starting Portfolio Performance Optimization...
echo.

REM 1. Create cache directory
if not exist ".cache" (
    echo 📁 Creating cache directory...
    mkdir .cache
    echo ✅ Cache directory created
) else (
    echo ✅ Cache directory already exists
)

REM 2. Check .env file
if not exist ".env" (
    echo.
    echo ⚠️  .env file not found!
    echo 📋 Creating .env from .env.example...
    copy .env.example .env
    echo ✏️  Please edit .env and add your database credentials
    echo 📝 File location: .env
) else (
    echo ✅ .env file exists
)

REM 3. Check .gitignore
if not exist ".gitignore" (
    echo.
    echo 📝 Creating .gitignore...
    (
        echo .env
        echo .cache
    ) > .gitignore
    echo ✅ .gitignore created
) else (
    echo ✅ .gitignore exists
    findstr /M "\.env" .gitignore >nul
    if errorlevel 1 (
        echo 🔒 Adding .env to .gitignore...
        echo .env >> .gitignore
        echo ✅ .env added to .gitignore
    )
    
    findstr /M "\.cache" .gitignore >nul
    if errorlevel 1 (
        echo 🔒 Adding .cache to .gitignore...
        echo .cache >> .gitignore
        echo ✅ .cache added to .gitignore
    )
)

echo.
echo ================================
echo ✨ Optimization Setup Complete!
echo ================================
echo.
echo 📋 Next steps:
echo 1. Edit .env with your database credentials
echo 2. Ensure .cache directory is writable
echo 3. Test the site and check .cache/ for files
echo 4. Update public/index.php with lazy loading
echo 5. See PERFORMANCE_GUIDE.md for more info
echo.
echo 📊 Monitor performance at: https://pagespeed.web.dev/
echo.
pause
