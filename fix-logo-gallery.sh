#!/bin/bash

echo "🔧 Logo Gallery Fix Script"
echo "=========================="
echo ""

echo "Step 1: Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "Step 2: Restarting queue worker..."
php artisan queue:restart

echo ""
echo "✅ Server-side caches cleared!"
echo ""
echo "📋 NEXT STEPS FOR YOU:"
echo "======================"
echo ""
echo "1. ⚠️  RESTART LARAVEL HERD (critical!)"
echo "   - Stop Herd completely"
echo "   - Start Herd again"
echo ""
echo "2. 🌐 Access site via HTTP (not HTTPS)"
echo "   - Use: http://project-namer.test"
echo "   - NOT: https://project-namer.test"
echo ""
echo "3. 🧹 Clear your browser cache"
echo "   - Press Cmd+Shift+R to hard refresh"
echo "   - Or clear cache in browser settings"
echo ""
echo "4. ▶️  Start the queue worker"
echo "   - Run: php artisan queue:work"
echo "   - Keep it running in a separate terminal"
echo ""
echo "5. 🎨 Generate logos and verify they appear!"
echo ""
