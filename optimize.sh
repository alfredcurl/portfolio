#!/bin/bash
# Portfolio Performance Optimization Script

echo "🚀 Starting Portfolio Performance Optimization..."
echo ""

# 1. Create cache directory
if [ ! -d ".cache" ]; then
    echo "📁 Creating cache directory..."
    mkdir -p .cache
    chmod 755 .cache
    echo "✅ Cache directory created"
else
    echo "✅ Cache directory already exists"
fi

# 2. Check .env file
if [ ! -f ".env" ]; then
    echo ""
    echo "⚠️  .env file not found!"
    echo "📋 Creating .env from .env.example..."
    cp .env.example .env
    echo "✏️  Please edit .env and add your database credentials"
    echo "📝 File location: .env"
else
    echo "✅ .env file exists"
fi

# 3. Add .env to .gitignore
if ! grep -q "^\.env$" .gitignore 2>/dev/null; then
    echo ""
    echo "🔒 Adding .env to .gitignore..."
    echo ".env" >> .gitignore
    echo "✅ .env added to .gitignore"
else
    echo "✅ .env already in .gitignore"
fi

# 4. Add cache to .gitignore
if ! grep -q "^\.cache" .gitignore 2>/dev/null; then
    echo "🔒 Adding .cache to .gitignore..."
    echo ".cache" >> .gitignore
    echo "✅ .cache added to .gitignore"
else
    echo "✅ .cache already in .gitignore"
fi

# 5. Check file permissions
echo ""
echo "🔐 Setting secure file permissions..."
chmod 644 .env.example 2>/dev/null
chmod 644 .gitignore 2>/dev/null
echo "✅ File permissions updated"

# 6. Summary
echo ""
echo "================================"
echo "✨ Optimization Setup Complete!"
echo "================================"
echo ""
echo "📋 Next steps:"
echo "1. Edit .env with your database credentials"
echo "2. Check .cache/ directory is writable (chmod 755)"
echo "3. Test the site and check .cache/ for files"
echo "4. Update public/index.php with lazy loading (see PERFORMANCE_GUIDE.md)"
echo "5. Consider building Tailwind CSS locally"
echo ""
echo "📊 Monitor performance at: https://pagespeed.web.dev/"
echo ""
