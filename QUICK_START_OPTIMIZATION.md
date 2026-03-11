# 🚀 Quick Start - Backend Optimization

## ⚡ Instant Setup (3 Steps)

### 1. Jalankan Script Optimasi

```powershell
cd "b:\Kuliah\Semester 6\Study-Tracer-Backend"
.\optimize-backend.ps1
```

Script ini akan otomatis:

- Clear semua cache
- Run migrations untuk database indexes
- Optimize composer autoloader
- Cache configuration dan routes
- Test Redis connection (optional)

### 2. Restart Server

```powershell
php artisan serve
```

### 3. Test Performance

Buka browser dan test endpoint:

- `http://localhost:8000/api/login`
- `http://localhost:8000/api/me` (dengan token)
- `http://localhost:8000/api/alumni/beranda` (dengan token)

---

## 📊 Expected Results

| Endpoint              | Before | After      | Improvement   |
| --------------------- | ------ | ---------- | ------------- |
| `/api/login`          | 4-7s   | **1-2s**   | 70% faster ⚡ |
| `/api/me`             | 3-5s   | **0.5-1s** | 80% faster ⚡ |
| `/api/alumni/beranda` | 5-10s  | **1-2s**   | 80% faster ⚡ |

---

## ✅ What's Changed

### 1. **Caching System**

- ✅ `calculateCanAccessAll()` cached for 10 minutes
- ✅ `hasCompletedKuesioner()` cached for 5 minutes
- ✅ Cache auto-invalidated when data changes

### 2. **Configuration**

- ✅ `APP_DEBUG=false` (reduced error overhead)
- ✅ `SESSION_DRIVER=file` (no DB queries for sessions)
- ✅ `CACHE_STORE=file` (faster cache storage)

### 3. **Performance**

- ✅ Laravel Telescope disabled
- ✅ `Model::preventLazyLoading()` disabled
- ✅ Database indexes added for slow queries

---

## 🔴 Upgrade to Redis (Recommended)

For **MAXIMUM PERFORMANCE**, install Redis:

### Windows Installation

```powershell
# Via Chocolatey
choco install redis-64

# Start Redis service
net start Redis
```

### Update .env

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Restart Server

```powershell
php artisan config:clear
php artisan serve
```

**Expected Result:** Additional **50-200ms faster** per request! 🚀

---

## 🐛 Troubleshooting

### Server Masih Lambat?

1. **Clear cache manual:**

    ```powershell
    php artisan cache:clear
    php artisan config:clear
    ```

2. **Check database:**

    ```powershell
    php artisan migrate
    ```

3. **Restart server:**
    ```powershell
    # Press Ctrl+C to stop
    php artisan serve
    ```

### Error "Class not found"?

```powershell
composer dump-autoload
```

### Cache tidak bekerja?

```powershell
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
```

---

## 📚 Full Documentation

Lihat **OPTIMIZATION_GUIDE.md** untuk:

- Penjelasan teknis lengkap
- Advanced optimizations
- Production deployment checklist
- Monitoring & maintenance

---

## ✨ Support

Jika masih ada masalah atau pertanyaan:

1. Check laravel.log: `storage/logs/laravel.log`
2. Enable query logging sementara
3. Monitor dengan Laravel Debugbar (development only)

---

**Setup Date:** March 10, 2026  
**Status:** ✅ Ready to Use  
**Next Step:** Run `.\optimize-backend.ps1`
