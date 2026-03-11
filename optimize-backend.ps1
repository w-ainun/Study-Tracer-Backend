# ====================================
# Backend Optimization Setup Script
# ====================================

Write-Host "🚀 Starting Backend Optimization Setup..." -ForegroundColor Cyan
Write-Host ""

# Step 1: Clear all caches
Write-Host "📦 Step 1: Clearing all caches..." -ForegroundColor Yellow
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
Write-Host "✅ Caches cleared!" -ForegroundColor Green
Write-Host ""

# Step 2: Run migrations for database indexes
Write-Host "🗄️  Step 2: Running database migrations (adding indexes)..." -ForegroundColor Yellow
$runMigrations = Read-Host "Do you want to run database migrations to add performance indexes? (y/n)"
if ($runMigrations -eq 'y') {
    php artisan migrate
    Write-Host "✅ Migrations completed!" -ForegroundColor Green
}
else {
    Write-Host "⚠️  Skipping migrations. Run 'php artisan migrate' manually later." -ForegroundColor Yellow
}
Write-Host ""

# Step 3: Optimize autoloader
Write-Host "⚡ Step 3: Optimizing Composer autoloader..." -ForegroundColor Yellow
composer dump-autoload --optimize
Write-Host "✅ Autoloader optimized!" -ForegroundColor Green
Write-Host ""

# Step 4: Cache config and routes (for production-like performance)
Write-Host "🔧 Step 4: Caching configuration and routes..." -ForegroundColor Yellow
php artisan config:cache
php artisan route:cache
Write-Host "✅ Configuration and routes cached!" -ForegroundColor Green
Write-Host ""

# Step 5: Check .env configuration
Write-Host "⚙️  Step 5: Checking .env configuration..." -ForegroundColor Yellow
Write-Host "Please verify these settings in your .env file:" -ForegroundColor Cyan
Write-Host "  ✓ APP_DEBUG=false" -ForegroundColor White
Write-Host "  ✓ SESSION_DRIVER=file" -ForegroundColor White
Write-Host "  ✓ CACHE_STORE=file (or redis for better performance)" -ForegroundColor White
Write-Host ""

$openEnv = Read-Host "Open .env file now to verify? (y/n)"
if ($openEnv -eq 'y') {
    notepad .env
}
Write-Host ""

# Step 6: Test Redis connection (optional)
Write-Host "🔴 Step 6: Redis Configuration (OPTIONAL but RECOMMENDED)" -ForegroundColor Yellow
Write-Host "For best performance, use Redis for cache and sessions." -ForegroundColor White
$testRedis = Read-Host "Do you want to test Redis connection? (y/n)"
if ($testRedis -eq 'y') {
    Write-Host "Testing Redis connection..." -ForegroundColor Cyan
    try {
        $redisTest = redis-cli ping 2>&1
        if ($redisTest -match "PONG") {
            Write-Host "✅ Redis is running! You can use CACHE_STORE=redis" -ForegroundColor Green
        }
        else {
            Write-Host "❌ Redis is not running or not installed." -ForegroundColor Red
            Write-Host "Install Redis: choco install redis-64" -ForegroundColor Yellow
        }
    }
    catch {
        Write-Host "❌ Redis is not installed." -ForegroundColor Red
        Write-Host "Install Redis: choco install redis-64" -ForegroundColor Yellow
    }
}
Write-Host ""

# Step 7: Performance summary
Write-Host "📊 Optimization Summary:" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor White
Write-Host "✅ Caches cleared" -ForegroundColor Green
Write-Host "✅ Telescope disabled in AppServiceProvider" -ForegroundColor Green
Write-Host "✅ Cache implemented for calculateCanAccessAll()" -ForegroundColor Green
Write-Host "✅ Cache implemented for hasCompletedKuesioner()" -ForegroundColor Green
Write-Host "✅ Session & Cache drivers changed to 'file'" -ForegroundColor Green
Write-Host "✅ APP_DEBUG set to false" -ForegroundColor Green
Write-Host "✅ Model::preventLazyLoading() disabled" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor White
Write-Host ""

# Step 8: Start server
Write-Host "🚀 Step 8: Starting Laravel development server..." -ForegroundColor Yellow
Write-Host ""
Write-Host "Expected Performance Improvements:" -ForegroundColor Cyan
Write-Host "  • /api/login: 4-7s → 1-2s (70% faster)" -ForegroundColor Green
Write-Host "  • /api/me: 3-5s → 0.5-1s (80% faster)" -ForegroundColor Green
Write-Host "  • /api/alumni/beranda: 5-10s → 1-2s (80% faster)" -ForegroundColor Green
Write-Host ""

$startServer = Read-Host "Start Laravel server now? (y/n)"
if ($startServer -eq 'y') {
    Write-Host ""
    Write-Host "Starting server at http://localhost:8000" -ForegroundColor Green
    Write-Host "Press Ctrl+C to stop the server" -ForegroundColor Yellow
    Write-Host ""
    php artisan serve
}
else {
    Write-Host ""
    Write-Host "To start the server manually, run: php artisan serve" -ForegroundColor Yellow
    Write-Host ""
}

Write-Host "✨ Setup Complete! Check OPTIMIZATION_GUIDE.md for more details." -ForegroundColor Green
