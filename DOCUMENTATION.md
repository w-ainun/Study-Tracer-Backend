# 📘 Dokumentasi Backend — Study Tracer

> **Versi**: 1.0  
> **Framework**: Laravel 12 (PHP 8.2+)  
> **Auth**: Laravel Sanctum 4.0  
> **Database**: MySQL 8.0+  
> **Tanggal**: 23 Februari 2026

---

## DAFTAR ISI

1. [Arsitektur Sistem](#1-arsitektur-sistem)
   - [Design Pattern](#11-design-pattern--repository-service-pattern)
   - [Alur Data Request](#12-alur-data-request)
   - [Struktur Folder](#13-struktur-folder)
   - [Database Schema](#14-database-schema-erd)
   - [Relasi Antar Tabel](#15-relasi-antar-tabel)
2. [Dependensi & Konfigurasi](#2-dependensi--konfigurasi)
   - [Tech Stack](#21-tech-stack)
   - [Library / Package](#22-library--package)
   - [Konfigurasi Environment](#23-konfigurasi-environment-env)
   - [Konfigurasi CORS](#24-konfigurasi-cors)
   - [Konfigurasi Sanctum](#25-konfigurasi-sanctum)
3. [Panduan Pengembangan](#3-panduan-pengembangan)
   - [Instalasi](#31-instalasi)
   - [Setup Database](#32-setup-database)
   - [Menjalankan Server](#33-menjalankan-server)
   - [Testing](#34-testing)
   - [Artisan Commands](#35-artisan-commands-penting)
4. [Dokumentasi API](#4-dokumentasi-api)
   - [Format Response](#41-format-response-standar)
   - [Authentication](#42-authentication)
   - [Alumni Features](#43-alumni-features)
   - [Admin Features](#44-admin-features)
   - [Public / Master Data](#45-public--master-data)
   - [Daftar Error Codes](#46-daftar-error-codes)

---

## 1. ARSITEKTUR SISTEM

### 1.1 Design Pattern — Repository Service Pattern

Sistem menggunakan **SOLID Principle** dengan implementasi **Repository Service Pattern** untuk memisahkan concern:

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────────┐     ┌──────────┐
│   Client     │────▶│  Controller  │────▶│   Service    │────▶│  Repository  │────▶│  Model   │
│  (React)     │◀────│  (Request/   │◀────│  (Business   │◀────│  (Data       │◀────│(Eloquent)│
│              │     │   Response)  │     │   Logic)     │     │   Access)    │     │          │
└──────────────┘     └──────────────┘     └──────────────┘     └──────────────┘     └──────────┘
                            │                                         ▲
                            │                                         │
                     ┌──────────────┐                          ┌──────────────┐
                     │  Form        │                          │  Interface   │
                     │  Request     │                          │  (Contract)  │
                     │  (Validasi)  │                          │              │
                     └──────────────┘                          └──────────────┘
```

| Layer | Tanggung Jawab | File |
|-------|----------------|------|
| **Controller** | Menerima HTTP request, memanggil Service, mengembalikan JSON response | `app/Http/Controllers/Api/*.php` |
| **Form Request** | Validasi input request (rules, messages, custom fail response) | `app/Http/Requests/*.php` |
| **API Resource** | Transformasi model Eloquent ke format JSON output yang konsisten | `app/Http/Resources/*.php` |
| **Service** | Logika bisnis, transaksi DB, orkestrasi antar repository | `app/Services/*.php` |
| **Repository** | Query database (Eloquent), akses data murni tanpa logika bisnis | `app/Repositories/*.php` |
| **Interface** | Kontrak (contract) untuk Repository agar bisa di-swap implementasinya | `app/Interfaces/*.php` |
| **Model** | Representasi tabel database, relasi, fillable, casts | `app/Models/*.php` |
| **Trait** | Reusable method (ApiResponse) yang di-share antar Controller | `app/Traits/*.php` |
| **Middleware** | Filter request sebelum masuk Controller (auth, role check) | `app/Http/Middleware/*.php` |

### Binding Interface → Repository (AppServiceProvider)

```php
$this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
$this->app->bind(AlumniRepositoryInterface::class, AlumniRepository::class);
$this->app->bind(AdminRepositoryInterface::class, AdminRepository::class);
$this->app->bind(LowonganRepositoryInterface::class, LowonganRepository::class);
$this->app->bind(KuesionerRepositoryInterface::class, KuesionerRepository::class);
$this->app->bind(MasterDataRepositoryInterface::class, MasterDataRepository::class);
```

### 1.2 Alur Data Request

**Contoh: Login Flow**
```
1. POST /api/login  { email, password }
2. → LoginRequest (validasi: email required|email, password required|string)
3. → AuthController@login
4. → AuthService::login()
5. → AuthRepository::findUserByEmail() → query: users WHERE email_users = ?
6. → Hash::check(password, user.password)
7. → user->createToken('auth_token') → insert ke personal_access_tokens
8. ← Return { user: UserResource, token: "3|xxx..." }
```

**Contoh: Register Flow**
```
1. POST /api/register  { email, password, nama_alumni, id_jurusan, ... }
2. → RegisterAlumniRequest (validasi multi-step)
3. → AuthController@register
4. → AuthService::registerUserAndProfile()
5. → DB::transaction:
     a. AuthRepository::createUser() → insert ke tabel users
     b. AuthRepository::createAlumniProfile() → insert ke tabel alumni
     c. user->createToken() → return token
6. ← Return { token: "4|abc..." }
```

### 1.3 Struktur Folder

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AuthController.php        ← Login, Register, Me, Logout
│   │       ├── AlumniController.php      ← Profil alumni, update career status
│   │       ├── AdminController.php       ← Dashboard, approve/reject user, manage alumni
│   │       ├── LowonganController.php    ← CRUD lowongan, approve/reject, save
│   │       ├── KuesionerController.php   ← CRUD kuesioner, pertanyaan, jawaban
│   │       └── MasterDataController.php  ← CRUD semua master data
│   ├── Middleware/
│   │   └── RoleMiddleware.php            ← Cek role user (alumni/admin)
│   ├── Requests/
│   │   ├── LoginRequest.php
│   │   ├── RegisterAlumniRequest.php
│   │   ├── UpdateProfileRequest.php
│   │   ├── CareerStatusRequest.php
│   │   ├── StoreLowonganRequest.php
│   │   ├── UpdateLowonganRequest.php
│   │   ├── StoreKuesionerRequest.php
│   │   ├── UpdateKuesionerRequest.php
│   │   ├── StorePertanyaanRequest.php
│   │   └── AnswerKuesionerRequest.php
│   └── Resources/
│       ├── UserResource.php
│       ├── AlumniResource.php
│       ├── AdminResource.php
│       ├── LowonganResource.php
│       ├── KuesionerResource.php
│       ├── PertanyaanResource.php
│       ├── OpsiJawabanResource.php
│       ├── RiwayatStatusResource.php
│       ├── ProvinsiResource.php
│       ├── KotaResource.php
│       ├── JurusanResource.php
│       ├── JurusanKuliahResource.php
│       ├── SkillResource.php
│       ├── SocialMediaResource.php
│       ├── StatusResource.php
│       ├── BidangUsahaResource.php
│       └── PerusahaanResource.php
├── Interfaces/
│   ├── AuthRepositoryInterface.php
│   ├── AlumniRepositoryInterface.php
│   ├── AdminRepositoryInterface.php
│   ├── LowonganRepositoryInterface.php
│   ├── KuesionerRepositoryInterface.php
│   └── MasterDataRepositoryInterface.php
├── Models/                               ← 23 Model Eloquent
├── Providers/
│   └── AppServiceProvider.php            ← Binding Interface → Repository
├── Repositories/
│   ├── AuthRepository.php
│   ├── AlumniRepository.php
│   ├── AdminRepository.php
│   ├── LowonganRepository.php
│   ├── KuesionerRepository.php
│   └── MasterDataRepository.php
├── Services/
│   ├── AuthService.php
│   ├── AlumniService.php
│   ├── AdminService.php
│   ├── LowonganService.php
│   ├── KuesionerService.php
│   └── MasterDataService.php
└── Traits/
    └── ApiResponse.php                   ← Standar JSON response helper

routes/
└── api.php                               ← 74 route endpoints

database/
├── migrations/                           ← 27 migration files
├── factories/                            ← Model factory untuk testing/seeding
└── seeders/                              ← Database seeder
```

### 1.4 Database Schema (ERD)

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                              DATABASE: tracerstudy                                  │
└─────────────────────────────────────────────────────────────────────────────────────┘

┌────────────────┐        ┌──────────────────┐        ┌──────────────────┐
│     users      │───1:1──│     alumni       │───N:1──│    jurusan       │
│────────────────│        │──────────────────│        │──────────────────│
│ id_users (PK)  │        │ id_alumni (PK)   │        │ id_jurusan (PK)  │
│ email_users    │        │ nama_alumni      │        │ nama_jurusan     │
│ password       │        │ nis              │        └──────────────────┘
│ role (enum)    │        │ nisn             │
│ remember_token │        │ jenis_kelamin    │        ┌──────────────────┐
│ timestamps     │        │ tanggal_lahir    │───N:1──│    provinsi      │
└────────────────┘        │ tempat_lahir     │ (via   │──────────────────│
        │                 │ tahun_masuk      │  kota) │ id_provinsi (PK) │
        │                 │ foto             │        │ nama_provinsi    │
   ┌────┘                 │ alamat           │        └──────────────────┘
   │                      │ no_hp            │                │
   │                      │ id_jurusan (FK)  │        ┌──────────────────┐
   │                      │ tahun_lulus      │        │      kota        │
   │                      │ id_users (FK)    │        │──────────────────│
   │                      │ status_create    │        │ id_kota (PK)     │
   │                      │ timestamps       │        │ nama_kota        │
   │                      └──────────────────┘        │ id_provinsi (FK) │
   │                              │                   └──────────────────┘
   │                              │
┌──┴─────────────┐    ┌──────────────────────┐     ┌───────────────────┐
│     admin      │    │   alumni_skills       │     │   alumni_social_  │
│────────────────│    │──────────────────────│     │   media           │
│ id_admin (PK)  │    │ id_alumniSkills (PK) │     │───────────────────│
│ nama_admin     │    │ id_alumni (FK)       │     │ id_alumniSosmed   │
│ id_users (FK)  │    │ id_skills (FK)       │     │ id_alumni (FK)    │
└────────────────┘    └──────────────────────┘     │ id_sosmed (FK)    │
                              │                     │ url               │
                      ┌───────┴──────┐              └───────────────────┘
                      │    skills    │                      │
                      │──────────────│              ┌───────┴──────────┐
                      │ id_skills    │              │  social_media    │
                      │ name_skills  │              │──────────────────│
                      └──────────────┘              │ id_sosmed (PK)   │
                                                    │ nama_sosmed      │
                                                    │ icon_sosmed      │
                                                    └──────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      CAREER TRACKING                                │
└─────────────────────────────────────────────────────────────────────┘

       alumni
         │
         │ 1:N
         ▼
┌──────────────────┐     ┌──────────────┐
│  riwayat_status  │──N:1│    status    │
│──────────────────│     │──────────────│
│ id_riwayat (PK)  │     │ id_status    │
│ id_alumni (FK)   │     │ nama_status  │  ← Bekerja, Kuliah, Wirausaha, dll.
│ id_status (FK)   │     └──────────────┘
│ tahun_mulai      │
│ tahun_selesai    │
└──────────────────┘
     │          │           │
     │1:1       │1:1        │1:1
     ▼          ▼           ▼
┌──────────┐ ┌──────────┐ ┌──────────────┐
│ pekerjaan│ │universi- │ │  wirausaha   │
│──────────│ │tas       │ │──────────────│
│id_pekerja│ │──────────│ │id_wirausaha  │
│posisi    │ │id_univ   │ │id_bidang(FK) │
│id_perusa │ │nama_univ │ │nama_usaha    │
│haan (FK) │ │id_jurus- │ │id_riwayat(FK)│
│id_riwayat│ │anKuliah  │ └──────────────┘
│(FK)      │ │jalur_ma- │        │
└──────────┘ │suk       │  ┌─────┴────────┐
     │       │id_riwayat│  │ bidang_usaha │
     │       │jenjang   │  │──────────────│
     │       └──────────┘  │ id_bidang    │
     ▼              │      │ nama_bidang  │
┌──────────┐        ▼     └──────────────┘
│perusahaan│ ┌──────────────┐
│──────────│ │jurusan_kuliah│
│id_perusa │ │──────────────│
│haan (PK) │ │id_jurusanKu- │
│nama_per- │ │liah (PK)     │
│usahaan   │ │nama_jurusan  │
│id_kota   │ └──────────────┘
│jalan     │
└──────────┘

┌─────────────────────────────────────────────────────────────────────┐
│                      LOWONGAN & KUESIONER                          │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────┐            ┌──────────────────┐
│    lowongan      │            │ simpan_lowongan   │
│──────────────────│            │──────────────────│
│ id_lowongan (PK) │◀──────────│ id_simpan (PK)   │
│ judul_lowongan   │            │ id_user (FK)     │──▶ users
│ deskripsi        │            │ id_lowongan (FK) │
│ status           │            └──────────────────┘
│ approval_status  │
│ lowongan_selesai │    ┌──────────────────┐
│ id_pekerjaan(FK) │    │    kuesioner     │
│ foto_lowongan    │    │──────────────────│
│ id_perusahaan(FK)│    │ id_kuesioner(PK) │
└──────────────────┘    │ judul_kuesioner  │
                        │ deskripsi_kues.  │
                        │ status_kuesioner │
                        │ tanggal_publikasi│
                        └──────────────────┘
                                │ 1:N
                                ▼
                        ┌──────────────────────┐
                        │pertanyaan_kuesioner   │
                        │──────────────────────│
                        │id_pertanyaanKuis (PK) │
                        │id_kuesioner (FK)      │
                        │pertanyaan             │
                        └──────────────────────┘
                           │ 1:N          │ 1:N
                           ▼              ▼
                  ┌──────────────┐  ┌───────────────────┐
                  │ opsi_jawaban │  │jawaban_kuesioner   │
                  │──────────────│  │───────────────────│
                  │id_opsi (PK)  │  │id_jawabanKuis (PK)│
                  │id_pertanyaan │  │id_pertanyaan (FK)  │
                  │opsi          │  │id_user (FK)        │
                  └──────────────┘  │id_opsiJawaban (FK) │
                                    │jawaban             │
                                    └───────────────────┘
```

### 1.5 Relasi Antar Tabel

| Tabel Asal | Relasi | Tabel Tujuan | Keterangan |
|------------|--------|-------------|------------|
| `users` | 1:1 | `alumni` | Setiap user alumni punya satu profil alumni |
| `users` | 1:1 | `admin` | Setiap user admin punya satu profil admin |
| `alumni` | N:1 | `jurusan` | Alumni terdaftar di satu jurusan SMK |
| `alumni` | N:N | `skills` | Via pivot `alumni_skills` |
| `alumni` | N:N | `social_media` | Via pivot `alumni_social_media` (+ url) |
| `alumni` | 1:N | `riwayat_status` | Satu alumni bisa punya banyak riwayat karir |
| `riwayat_status` | N:1 | `status` | Referensi ke jenis status (Bekerja/Kuliah/Wirausaha) |
| `riwayat_status` | 1:1 | `pekerjaan` | Jika status = Bekerja |
| `riwayat_status` | 1:1 | `universitas` | Jika status = Kuliah |
| `riwayat_status` | 1:1 | `wirausaha` | Jika status = Wirausaha |
| `pekerjaan` | N:1 | `perusahaan` | Pekerjaan di perusahaan tertentu |
| `perusahaan` | N:1 | `kota` | Lokasi perusahaan |
| `kota` | N:1 | `provinsi` | Kota bagian dari provinsi |
| `universitas` | N:1 | `jurusan_kuliah` | Jurusan kuliah yang ditempuh |
| `wirausaha` | N:1 | `bidang_usaha` | Bidang usaha wirausaha |
| `lowongan` | N:1 | `perusahaan` | Lowongan dari perusahaan |
| `lowongan` | N:1 | `pekerjaan` | Opsional, terkait posisi pekerjaan |
| `simpan_lowongan` | N:1 | `users` | User yang menyimpan lowongan |
| `simpan_lowongan` | N:1 | `lowongan` | Lowongan yang disimpan |
| `kuesioner` | 1:N | `pertanyaan_kuesioner` | Kuesioner punya banyak pertanyaan |
| `pertanyaan_kuesioner` | 1:N | `opsi_jawaban` | Pertanyaan punya banyak opsi |
| `pertanyaan_kuesioner` | 1:N | `jawaban_kuesioner` | Jawaban user untuk pertanyaan |
| `jawaban_kuesioner` | N:1 | `users` | User yang menjawab |
| `jawaban_kuesioner` | N:1 | `opsi_jawaban` | Opsional, jika jawaban pilihan |

---

## 2. DEPENDENSI & KONFIGURASI

### 2.1 Tech Stack

| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| **Backend Framework** | Laravel | ^12.0 |
| **Bahasa** | PHP | >= 8.2 |
| **Database** | MySQL | 8.0+ |
| **Authentication** | Laravel Sanctum | ^4.0 |
| **Frontend** | React (Vite) | — |
| **Frontend Port** | localhost | 5173 |
| **Backend Port** | localhost | 8000 |

### 2.2 Library / Package

**Production:**
| Package | Versi | Fungsi |
|---------|-------|--------|
| `laravel/framework` | ^12.0 | Core framework |
| `laravel/sanctum` | ^4.0 | API token authentication (Bearer Token) |
| `laravel/tinker` | ^2.10.1 | REPL interaktif untuk debugging |

**Development:**
| Package | Versi | Fungsi |
|---------|-------|--------|
| `fakerphp/faker` | ^1.23 | Generate data palsu untuk factory/seeder |
| `laravel/pint` | ^1.24 | Code style fixer |
| `mockery/mockery` | ^1.6 | Mocking library untuk testing |
| `phpunit/phpunit` | ^11.5.3 | Unit & feature testing |
| `nunomaduro/collision` | ^8.6 | Better error reporting |

### 2.3 Konfigurasi Environment (.env)

```ini
APP_NAME=StudyTracer
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=base64:xxxxxxxxxx  # Generate via: php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tracerstudy
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=public  # Untuk upload foto alumni & lowongan
```

### 2.4 Konfigurasi CORS

File: `config/cors.php`

```php
return [
    'paths'                => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods'      => ['*'],
    'allowed_origins'      => ['http://localhost:5173'],  // Frontend React
    'allowed_origins_patterns' => [],
    'allowed_headers'      => ['*'],
    'exposed_headers'      => [],
    'max_age'              => 0,
    'supports_credentials' => true,  // WAJIB true untuk Sanctum
];
```

> **Penting:** Jika frontend dijalankan di port/domain lain, ubah `allowed_origins`.

### 2.5 Konfigurasi Sanctum

File: `config/sanctum.php`

- Token tidak memiliki expiry default (bisa dikonfigurasi)
- Guard: `web` (default Laravel)
- Middleware: `auth:sanctum` untuk route yang membutuhkan login

---

## 3. PANDUAN PENGEMBANGAN

### 3.1 Instalasi

```bash
# 1. Clone repository
git clone <repository_url>
cd study-tracer-backend

# 2. Install dependensi PHP
composer install

# 3. Copy file environment
cp .env.example .env

# 4. Generate Application Key
php artisan key:generate

# 5. Publish Sanctum migration
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 6. Buat symbolic link untuk storage (upload foto)
php artisan storage:link
```

### 3.2 Setup Database

```bash
# 1. Buat database di MySQL
mysql -u root -e "CREATE DATABASE tracerstudy;"

# 2. Jalankan migrasi (32 tabel)
php artisan migrate

# 3. (Opsional) Jalankan seeder untuk data dummy
php artisan db:seed

# 4. Jika ingin reset + reseed
php artisan migrate:fresh --seed
```

**Daftar Tabel yang Dibuat:**

| No | Tabel | Keterangan |
|----|-------|------------|
| 1 | `users` | Akun login (email, password, role) |
| 2 | `password_reset_tokens` | Token reset password |
| 3 | `sessions` | Session data |
| 4 | `cache` | Cache store |
| 5 | `cache_locks` | Cache lock mechanism |
| 6 | `jobs` | Queue jobs |
| 7 | `job_batches` | Batch jobs |
| 8 | `failed_jobs` | Failed queue jobs |
| 9 | `provinsi` | Data provinsi Indonesia |
| 10 | `jurusan` | Jurusan SMK |
| 11 | `jurusan_kuliah` | Jurusan perkuliahan |
| 12 | `skills` | Referensi skill/keahlian |
| 13 | `social_media` | Referensi social media |
| 14 | `status` | Jenis status karir (Bekerja, Kuliah, Wirausaha, dll.) |
| 15 | `bidang_usaha` | Referensi bidang usaha |
| 16 | `kuesioner` | Kuesioner tracer study |
| 17 | `kota` | Data kota (FK → provinsi) |
| 18 | `alumni` | Profil alumni (FK → users, jurusan) |
| 19 | `admin` | Profil admin (FK → users) |
| 20 | `alumni_skills` | Pivot alumni ↔ skills |
| 21 | `alumni_social_media` | Pivot alumni ↔ social_media + url |
| 22 | `riwayat_status` | Riwayat karir alumni (FK → alumni, status) |
| 23 | `perusahaan` | Data perusahaan (FK → kota) |
| 24 | `pekerjaan` | Data pekerjaan (FK → perusahaan, riwayat_status) |
| 25 | `universitas` | Data kuliah (FK → jurusan_kuliah, riwayat_status) |
| 26 | `wirausaha` | Data wirausaha (FK → bidang_usaha, riwayat_status) |
| 27 | `lowongan` | Lowongan kerja (FK → perusahaan, pekerjaan) |
| 28 | `simpan_lowongan` | Lowongan yang disimpan user |
| 29 | `pertanyaan_kuesioner` | Pertanyaan dalam kuesioner |
| 30 | `opsi_jawaban` | Opsi jawaban untuk pertanyaan |
| 31 | `jawaban_kuesioner` | Jawaban user terhadap kuesioner |
| 32 | `personal_access_tokens` | Token Sanctum (API auth) |

### 3.3 Menjalankan Server

```bash
# Development server
php artisan serve
# → http://localhost:8000

# Pastikan frontend berjalan di:
# → http://localhost:5173
```

### 3.4 Testing

```bash
# Jalankan semua test
php artisan test

# Atau dengan PHPUnit langsung
./vendor/bin/phpunit

# Test file tertentu
php artisan test --filter=AuthTest
```

### 3.5 Artisan Commands Penting

```bash
# Clear semua cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Lihat semua route
php artisan route:list --path=api

# Tinker (interactive shell)
php artisan tinker

# Publish Sanctum migration (jika belum)
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

---

## 4. DOKUMENTASI API

**Base URL:** `http://localhost:8000/api`

**Header Wajib untuk Semua Request:**
```
Accept: application/json
Content-Type: application/json
```

**Header Tambahan untuk Route Protected:**
```
Authorization: Bearer <token>
```

### 4.1 Format Response Standar

Semua endpoint menggunakan format JSON yang konsisten via `ApiResponse` trait:

**✅ Sukses (200/201):**
```json
{
    "status": "success",
    "message": "Pesan sukses",
    "data": { ... }
}
```

**❌ Error Validasi (422):**
```json
{
    "status": "error",
    "message": "Validasi gagal",
    "errors": {
        "email": ["Email wajib diisi."],
        "password": ["Password minimal 8 karakter."]
    }
}
```

**❌ Unauthorized (401):**
```json
{
    "message": "Unauthenticated."
}
```

**❌ Forbidden (403):**
```json
{
    "status": "error",
    "message": "Akses ditolak. Anda tidak memiliki izin untuk mengakses resource ini."
}
```

**❌ Not Found (404):**
```json
{
    "status": "error",
    "message": "Data tidak ditemukan"
}
```

**❌ Server Error (500):**
```json
{
    "status": "error",
    "message": "Terjadi kesalahan"
}
```

**Paginated Response:**
```json
{
    "status": "success",
    "message": "Berhasil",
    "data": {
        "data": [ ... ],
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 73,
        "from": 1,
        "to": 15,
        "links": { ... }
    }
}
```

---

### 4.2 Authentication

#### 4.2.1 Register Alumni

| | |
|---|---|
| **URL** | `POST /api/register` |
| **Auth** | Tidak perlu |
| **Content-Type** | `multipart/form-data` (jika upload foto) atau `application/json` |

**Request Body:**

| Field | Tipe | Wajib | Validasi | Keterangan |
|-------|------|-------|----------|------------|
| `email` | string | ✅ | email, unique:users,email_users | Email untuk login |
| `password` | string | ✅ | min:8, confirmed | Password |
| `password_confirmation` | string | ✅ | — | Konfirmasi password |
| `nama_alumni` | string | ✅ | max:255 | Nama lengkap alumni |
| `nis` | string | ❌ | max:20 | Nomor Induk Siswa |
| `nisn` | string | ❌ | max:20 | Nomor Induk Siswa Nasional |
| `jenis_kelamin` | string | ✅ | in:Laki-laki,Perempuan | — |
| `tanggal_lahir` | date | ❌ | format: Y-m-d | Contoh: `2004-05-15` |
| `tempat_lahir` | string | ❌ | max:255 | — |
| `tahun_masuk` | string | ❌ | digits:4 | Contoh: `2019` |
| `foto` | file | ❌ | image, mimes:jpeg,png,jpg, max:2048 | Maks 2MB |
| `alamat` | string | ❌ | — | Alamat lengkap |
| `no_hp` | string | ❌ | max:20 | Nomor telepon |
| `id_jurusan` | integer | ✅ | exists:jurusan,id_jurusan | ID jurusan SMK |
| `tahun_lulus` | string | ❌ | digits:4 | Contoh: `2025` |
| `skills` | array | ❌ | — | Array ID skill |
| `skills.*` | integer | — | exists:skills,id_skills | — |
| `social_media` | array | ❌ | — | Array object social media |
| `social_media.*.id_sosmed` | integer | — | exists:social_media,id_sosmed | — |
| `social_media.*.url` | string | — | url | Link profil |
| `id_status` | integer | ❌ | exists:status,id_status | Status karir awal |
| `tahun_mulai` | integer | ❌ | — | Tahun mulai karir |
| `tahun_selesai` | integer | ❌ | — | Tahun selesai |
| `pekerjaan` | object | ❌ | — | Jika status = Bekerja |
| `pekerjaan.posisi` | string | — | required_with:pekerjaan | — |
| `pekerjaan.nama_perusahaan` | string | — | required_with:pekerjaan | — |
| `pekerjaan.id_kota` | integer | — | exists:kota,id_kota | — |
| `pekerjaan.jalan` | string | — | — | Alamat perusahaan |
| `universitas` | object | ❌ | — | Jika status = Kuliah |
| `universitas.nama_universitas` | string | — | required_with:universitas | — |
| `universitas.id_jurusanKuliah` | integer | — | exists:jurusan_kuliah,id_jurusanKuliah | — |
| `universitas.jalur_masuk` | string | — | in:SNBP,SNBT,Mandiri,Beasiswa,lainnya | — |
| `universitas.jenjang` | string | — | in:D3,D4,S1,S2,S3 | — |
| `wirausaha` | object | ❌ | — | Jika status = Wirausaha |
| `wirausaha.id_bidang` | integer | — | exists:bidang_usaha,id_bidang | — |
| `wirausaha.nama_usaha` | string | — | required_with:wirausaha | — |

**Contoh Request (JSON):**
```json
{
    "email": "alumni.baru@email.com",
    "password": "password123",
    "password_confirmation": "password123",
    "nama_alumni": "Ahmad Fauzi",
    "jenis_kelamin": "Laki-laki",
    "id_jurusan": 1,
    "tahun_lulus": "2025",
    "no_hp": "081234567890",
    "skills": [1, 3, 5],
    "social_media": [
        { "id_sosmed": 1, "url": "https://instagram.com/ahmadfauzi" }
    ]
}
```

**Response (201 Created):**
```json
{
    "status": "success",
    "message": "Registrasi berhasil",
    "data": {
        "token": "4|aB3cDeF4gH5iJkLmNoPqRsTuVwXyZ..."
    }
}
```

---

#### 4.2.2 Login

| | |
|---|---|
| **URL** | `POST /api/login` |
| **Auth** | Tidak perlu |

**Request Body:**

| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `email` | string | ✅ | email |
| `password` | string | ✅ | string |

**Contoh Request:**
```json
{
    "email": "admin@tracerstudy.com",
    "password": "password"
}
```

**Response (200 OK) — Login sebagai Admin:**
```json
{
    "status": "success",
    "message": "Login berhasil",
    "data": {
        "user": {
            "id": 1,
            "email": "admin@tracerstudy.com",
            "role": "admin",
            "profile": null,
            "admin_profile": {
                "id": 1,
                "nama": "Super Admin"
            },
            "created_at": "2026-02-20T02:56:45.000000Z"
        },
        "token": "1|aB3cDeF4gH5iJkLmNoPqRsTuVwXyZ..."
    }
}
```

**Response (200 OK) — Login sebagai Alumni:**
```json
{
    "status": "success",
    "message": "Login berhasil",
    "data": {
        "user": {
            "id": 2,
            "email": "alumni1@tracerstudy.com",
            "role": "alumni",
            "profile": {
                "id": 1,
                "nama": "Ahmad Fauzi",
                "nis": "12345",
                "nisn": "1234567890",
                "jenis_kelamin": "Laki-laki",
                "tanggal_lahir": "2004-01-08",
                "tempat_lahir": "Surabaya",
                "tahun_masuk": 2019,
                "foto": null,
                "alamat": "Jl. Contoh No. 1",
                "no_hp": "081234567890",
                "tahun_lulus": "2025-05-11",
                "status_create": "ok",
                "jurusan": { "id": 3, "nama": "Multimedia" },
                "created_at": "2026-02-20T02:56:45.000000Z",
                "updated_at": "2026-02-20T02:56:45.000000Z"
            },
            "admin_profile": null,
            "created_at": "2026-02-20T02:56:45.000000Z"
        },
        "token": "3|sykCgkniB2e12pfNbgB1Hx..."
    }
}
```

**Response (422) — Email/Password Salah:**
```json
{
    "status": "error",
    "message": "Email atau password salah.",
    "errors": {
        "email": ["Email atau password salah."]
    }
}
```

---

#### 4.2.3 Get Current User (Me)

| | |
|---|---|
| **URL** | `GET /api/me` |
| **Auth** | ✅ Bearer Token |

**Response (200 OK):**
```json
{
    "status": "success",
    "message": "Berhasil",
    "data": {
        "id": 2,
        "email": "alumni1@tracerstudy.com",
        "role": "alumni",
        "profile": {
            "id": 1,
            "nama": "Ahmad Fauzi",
            "nis": "12345",
            "nisn": "1234567890",
            "jenis_kelamin": "Laki-laki",
            "tanggal_lahir": "2004-01-08",
            "tempat_lahir": "Surabaya",
            "tahun_masuk": 2019,
            "foto": "http://localhost:8000/storage/alumni/foto/abc.jpg",
            "alamat": "Jl. Contoh No. 1",
            "no_hp": "081234567890",
            "tahun_lulus": "2025-01-01",
            "status_create": "ok",
            "jurusan": { "id": 1, "nama": "Rekayasa Perangkat Lunak" },
            "skills": [
                { "id": 1, "nama": "PHP" },
                { "id": 2, "nama": "JavaScript" }
            ],
            "social_media": [
                { "id": 1, "nama": "Instagram", "icon": "instagram", "url": "https://..." }
            ],
            "riwayat_status": [
                {
                    "id": 1,
                    "status": { "id": 1, "nama": "Bekerja" },
                    "tahun_mulai": 2025,
                    "tahun_selesai": null,
                    "pekerjaan": {
                        "id": 1,
                        "posisi": "Backend Developer",
                        "perusahaan": {
                            "id": 1,
                            "nama": "PT Teknologi Indonesia",
                            "jalan": "Jl. Raya No.10",
                            "kota": { "id": 1, "nama": "Surabaya" }
                        }
                    },
                    "universitas": null,
                    "wirausaha": null,
                    "created_at": "2026-02-20T02:56:45.000000Z"
                }
            ],
            "created_at": "2026-02-20T02:56:45.000000Z",
            "updated_at": "2026-02-20T02:56:45.000000Z"
        },
        "admin_profile": null,
        "created_at": "2026-02-20T02:56:45.000000Z"
    }
}
```

---

#### 4.2.4 Logout

| | |
|---|---|
| **URL** | `POST /api/logout` |
| **Auth** | ✅ Bearer Token |

**Response (200 OK):**
```json
{
    "status": "success",
    "message": "Logout berhasil",
    "data": null
}
```

---

### 4.3 Alumni Features

> Semua endpoint di bawah membutuhkan **Bearer Token** + **role: alumni**  
> Prefix: `/api/alumni`

#### 4.3.1 Get Profile

| | |
|---|---|
| **URL** | `GET /api/alumni/profile` |
| **Auth** | ✅ Bearer Token (alumni) |

**Response:** `AlumniResource` lengkap dengan relasi jurusan, skills, social_media, riwayat_status (sama seperti field `profile` di response `/me`).

---

#### 4.3.2 Update Profile

| | |
|---|---|
| **URL** | `PUT /api/alumni/profile` |
| **Auth** | ✅ Bearer Token (alumni) |
| **Content-Type** | `multipart/form-data` (jika upload foto) |

**Request Body:**

| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `nama_alumni` | string | ❌ | max:255 |
| `nis` | string | ❌ | max:20 |
| `nisn` | string | ❌ | max:20 |
| `jenis_kelamin` | string | ❌ | in:Laki-laki,Perempuan |
| `tanggal_lahir` | date | ❌ | format: Y-m-d |
| `tempat_lahir` | string | ❌ | max:255 |
| `tahun_masuk` | integer | ❌ | — |
| `foto` | file | ❌ | image, mimes:jpeg,png,jpg, max:2048 |
| `alamat` | string | ❌ | — |
| `no_hp` | string | ❌ | max:20 |
| `id_jurusan` | integer | ❌ | exists:jurusan,id_jurusan |
| `tahun_lulus` | date | ❌ | format: Y-m-d |
| `skills` | array | ❌ | Array of skill IDs (akan di-sync/replace semua) |
| `social_media` | array | ❌ | Array of `{id_sosmed, url}` (akan di-sync/replace semua) |

> **Catatan:** Field `skills` dan `social_media` melakukan **sync** — menghapus semua data lama lalu insert data baru.

**Response (200 OK):** `AlumniResource` yang sudah diperbarui.

---

#### 4.3.3 Update Career Status

| | |
|---|---|
| **URL** | `POST /api/alumni/career-status` |
| **Auth** | ✅ Bearer Token (alumni) |

**Request Body:**

| Field | Tipe | Wajib | Validasi | Keterangan |
|-------|------|-------|----------|------------|
| `id_status` | integer | ✅ | exists:status,id_status | ID status karir |
| `tahun_mulai` | integer | ❌ | — | Tahun mulai |
| `tahun_selesai` | integer | ❌ | — | Tahun selesai |
| `pekerjaan` | object | ❌ | — | Wajib jika status = Bekerja |
| `pekerjaan.posisi` | string | ⚠️ | required_with:pekerjaan | Posisi di perusahaan |
| `pekerjaan.nama_perusahaan` | string | ⚠️ | required_with:pekerjaan | Nama perusahaan (auto firstOrCreate) |
| `pekerjaan.id_kota` | integer | ⚠️ | exists:kota,id_kota | Lokasi perusahaan |
| `pekerjaan.jalan` | string | ❌ | — | Alamat detail |
| `universitas` | object | ❌ | — | Wajib jika status = Kuliah |
| `universitas.nama_universitas` | string | ⚠️ | required_with:universitas | — |
| `universitas.id_jurusanKuliah` | integer | ⚠️ | exists:jurusan_kuliah,id_jurusanKuliah | — |
| `universitas.jalur_masuk` | string | ⚠️ | in:SNBP,SNBT,Mandiri,Beasiswa,lainnya | — |
| `universitas.jenjang` | string | ⚠️ | in:D3,D4,S1,S2,S3 | — |
| `wirausaha` | object | ❌ | — | Wajib jika status = Wirausaha |
| `wirausaha.id_bidang` | integer | ⚠️ | exists:bidang_usaha,id_bidang | — |
| `wirausaha.nama_usaha` | string | ⚠️ | required_with:wirausaha | — |

> ⚠️ = `required_with` — wajib jika parent object dikirim

**Contoh Request (Bekerja):**
```json
{
    "id_status": 1,
    "tahun_mulai": 2025,
    "pekerjaan": {
        "posisi": "Backend Developer",
        "nama_perusahaan": "PT Teknologi Indonesia",
        "id_kota": 5,
        "jalan": "Jl. Raya No.10"
    }
}
```

**Contoh Request (Kuliah):**
```json
{
    "id_status": 2,
    "tahun_mulai": 2025,
    "universitas": {
        "nama_universitas": "Universitas Airlangga",
        "id_jurusanKuliah": 3,
        "jalur_masuk": "SNBP",
        "jenjang": "S1"
    }
}
```

**Contoh Request (Wirausaha):**
```json
{
    "id_status": 3,
    "tahun_mulai": 2025,
    "wirausaha": {
        "id_bidang": 2,
        "nama_usaha": "Toko IT Solution"
    }
}
```

**Response (201 Created):**
```json
{
    "status": "success",
    "message": "Status karir berhasil disimpan",
    "data": {
        "id": 5,
        "status": { "id": 1, "nama": "Bekerja" },
        "tahun_mulai": 2025,
        "tahun_selesai": null,
        "pekerjaan": {
            "id": 10,
            "posisi": "Backend Developer",
            "perusahaan": {
                "id": 3,
                "nama": "PT Teknologi Indonesia",
                "jalan": "Jl. Raya No.10",
                "kota": { "id": 5, "nama": "Surabaya" }
            }
        },
        "universitas": null,
        "wirausaha": null,
        "created_at": "2026-02-23T..."
    }
}
```

---

#### 4.3.4 Get Saved Lowongan

| | |
|---|---|
| **URL** | `GET /api/alumni/saved-lowongan` |
| **Auth** | ✅ Bearer Token (alumni) |
| **Query Params** | `per_page` (default: 15) |

**Response:** Paginated list of saved lowongan.

---

#### 4.3.5 Toggle Save Lowongan

| | |
|---|---|
| **URL** | `POST /api/alumni/lowongan/{id}/toggle-save` |
| **Auth** | ✅ Bearer Token (alumni) |

**Response (200 OK):**
```json
{
    "status": "success",
    "message": "Lowongan berhasil disimpan",
    "data": { "saved": true }
}
```
> `saved: false` jika lowongan dihapus dari simpanan (toggle behavior).

---

#### 4.3.6 Get Kuesioner dengan Pertanyaan

| | |
|---|---|
| **URL** | `GET /api/alumni/kuesioner/{id}` |
| **Auth** | ✅ Bearer Token (alumni) |

**Response (200 OK):**
```json
{
    "status": "success",
    "message": "Berhasil",
    "data": {
        "id": 1,
        "judul": "Tracer Study 2025",
        "deskripsi": "Kuesioner untuk alumni angkatan 2025",
        "status": "publish",
        "tanggal_publikasi": "2026-01-15",
        "pertanyaan": [
            {
                "id": 1,
                "pertanyaan": "Apakah ilmu SMK berguna di pekerjaan?",
                "opsi": [
                    { "id": 1, "opsi": "Sangat Berguna" },
                    { "id": 2, "opsi": "Cukup Berguna" },
                    { "id": 3, "opsi": "Kurang Berguna" }
                ]
            },
            {
                "id": 2,
                "pertanyaan": "Berikan saran untuk sekolah:",
                "opsi": []
            }
        ]
    }
}
```

---

#### 4.3.7 Submit Jawaban Kuesioner

| | |
|---|---|
| **URL** | `POST /api/alumni/kuesioner/{kuesionerId}/jawaban` |
| **Auth** | ✅ Bearer Token (alumni) |

**Request Body:**

| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `jawaban` | array | ✅ | min:1 |
| `jawaban.*.id_pertanyaan` | integer | ✅ | exists:pertanyaan_kuesioner,id_pertanyaanKuis |
| `jawaban.*.id_opsiJawaban` | integer | ❌ | exists:opsi_jawaban,id_opsi |
| `jawaban.*.jawaban` | string | ❌ | — (untuk jawaban essay/teks bebas) |

**Contoh Request:**
```json
{
    "jawaban": [
        { "id_pertanyaan": 1, "id_opsiJawaban": 3 },
        { "id_pertanyaan": 2, "jawaban": "Sangat membantu dalam karir saya" },
        { "id_pertanyaan": 3, "id_opsiJawaban": 7 }
    ]
}
```

**Response (200 OK):**
```json
{
    "status": "success",
    "message": "Jawaban berhasil disimpan",
    "data": null
}
```

---

### 4.4 Admin Features

> Semua endpoint di bawah membutuhkan **Bearer Token** + **role: admin**  
> Prefix: `/api/admin`

#### 4.4.1 Dashboard Stats

| | |
|---|---|
| **URL** | `GET /api/admin/dashboard-stats` |
| **Auth** | ✅ Bearer Token (admin) |

**Response (200 OK):**
```json
{
    "status": "success",
    "message": "Berhasil",
    "data": {
        "total_users": 20,
        "pending_users": 3,
        "active_kuesioner": 2,
        "pending_lowongan": 5,
        "percent_bekerja": 45
    }
}
```

| Field | Keterangan |
|-------|------------|
| `total_users` | Total user dengan role alumni |
| `pending_users` | Alumni dengan status_create = pending |
| `active_kuesioner` | Kuesioner dengan status = publish |
| `pending_lowongan` | Lowongan dengan approval_status = pending |
| `percent_bekerja` | Persentase alumni yang berstatus "Bekerja" |

---

#### 4.4.2 User Management

| Endpoint | Method | Keterangan |
|----------|--------|------------|
| `/api/admin/pending-users` | GET | Daftar alumni pending (paginated) |
| `/api/admin/approve-user/{id}` | POST | Approve alumni (status_create → ok) |
| `/api/admin/reject-user/{id}` | POST | Reject alumni (status_create → rejected) |
| `/api/admin/alumni` | GET | Semua alumni (filterable, paginated) |
| `/api/admin/alumni/{id}` | GET | Detail satu alumni + semua relasi |
| `/api/admin/users/{id}` | DELETE | Hapus user (cascade: alumni, riwayat, dll.) |

**Query Params untuk `GET /admin/alumni`:**

| Param | Tipe | Keterangan |
|-------|------|------------|
| `status_create` | string | Filter: `pending`, `ok`, `rejected` |
| `id_jurusan` | integer | Filter berdasarkan jurusan |
| `search` | string | Cari berdasarkan nama_alumni, nis, nisn |
| `per_page` | integer | Jumlah per halaman (default: 15) |

**Contoh:** `GET /api/admin/alumni?status_create=ok&id_jurusan=1&search=ahmad&per_page=10`

**Response untuk GET /admin/alumni/{id}:**
Mengembalikan `AlumniResource` lengkap termasuk:
- `user` — data akun login
- `jurusan` — jurusan SMK
- `skills` — keahlian alumni
- `social_media` — link social media
- `riwayat_status` — semua riwayat karir + pekerjaan/universitas/wirausaha

---

#### 4.4.3 Lowongan Management

| Endpoint | Method | Keterangan |
|----------|--------|------------|
| `/api/admin/lowongan` | GET | Semua lowongan (filterable, paginated) |
| `/api/admin/lowongan` | POST | Buat lowongan baru |
| `/api/admin/lowongan/{id}` | PUT | Update lowongan |
| `/api/admin/lowongan/{id}` | DELETE | Hapus lowongan |
| `/api/admin/lowongan/pending` | GET | Lowongan menunggu persetujuan |
| `/api/admin/lowongan/{id}/approve` | POST | Setujui lowongan (approval_status → approved) |
| `/api/admin/lowongan/{id}/reject` | POST | Tolak lowongan (approval_status → rejected) |

**Request Body — Create Lowongan (`POST`):**

| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `judul_lowongan` | string | ✅ | max:255 |
| `deskripsi` | string | ❌ | text |
| `status` | string | ❌ | in:draft,published,closed (default: draft) |
| `lowongan_selesai` | string | ❌ | format: H:i:s |
| `id_pekerjaan` | integer | ❌ | exists:pekerjaan,id_pekerjaan |
| `foto_lowongan` | file | ❌ | image, mimes:jpeg,png,jpg, max:2048 |
| `id_perusahaan` | integer | ✅ | exists:perusahaan,id_perusahaan |

**Request Body — Update Lowongan (`PUT`):** Sama dengan Create, tapi semua field `sometimes` (opsional).

**Query Params untuk `GET /admin/lowongan`:**

| Param | Tipe | Keterangan |
|-------|------|------------|
| `status` | string | Filter: `draft`, `published`, `closed` |
| `approval_status` | string | Filter: `pending`, `approved`, `rejected` |
| `search` | string | Cari berdasarkan judul/deskripsi |
| `per_page` | integer | Default: 15 |

**Response `LowonganResource`:**
```json
{
    "id": 1,
    "judul": "Backend Developer",
    "deskripsi": "Dibutuhkan backend developer...",
    "status": "published",
    "approval_status": "approved",
    "lowongan_selesai": "17:00:00",
    "foto": "http://localhost:8000/storage/lowongan/abc.jpg",
    "perusahaan": {
        "id": 1,
        "nama": "PT Teknologi Indonesia",
        "jalan": "Jl. Raya No.10",
        "kota": { "id": 1, "nama": "Surabaya", "provinsi": { "id": 1, "nama": "Jawa Timur" } }
    },
    "pekerjaan": { "id": 1, "posisi": "Backend Developer" },
    "created_at": "2026-02-20T...",
    "updated_at": "2026-02-20T..."
}
```

---

#### 4.4.4 Kuesioner Management

| Endpoint | Method | Keterangan |
|----------|--------|------------|
| `/api/admin/kuesioner` | GET | Semua kuesioner (filterable, paginated) |
| `/api/admin/kuesioner` | POST | Buat kuesioner baru |
| `/api/admin/kuesioner/{id}` | GET | Detail kuesioner + pertanyaan + opsi |
| `/api/admin/kuesioner/{id}` | PUT | Update kuesioner |
| `/api/admin/kuesioner/{id}` | DELETE | Hapus kuesioner (cascade pertanyaan & opsi) |

**Request Body — Create Kuesioner:**

| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `judul_kuesioner` | string | ✅ | max:255 |
| `deskripsi_kuesioner` | string | ✅ | text |
| `status_kuesioner` | string | ❌ | in:draft,publish,close (default: draft) |
| `tanggal_publikasi` | date | ❌ | format: Y-m-d |

**Request Body — Update Kuesioner:** Sama, semua field `sometimes`.

---

#### 4.4.5 Pertanyaan Kuesioner

| Endpoint | Method | Keterangan |
|----------|--------|------------|
| `/api/admin/kuesioner/{kuesionerId}/pertanyaan` | POST | Tambah pertanyaan + opsi |
| `/api/admin/kuesioner/{kuesionerId}/pertanyaan/{pertanyaanId}` | PUT | Update pertanyaan + opsi (replace) |
| `/api/admin/kuesioner/{kuesionerId}/pertanyaan/{pertanyaanId}` | DELETE | Hapus pertanyaan + opsi terkait |

**Request Body — Create/Update Pertanyaan:**

| Field | Tipe | Wajib | Validasi | Keterangan |
|-------|------|-------|----------|------------|
| `pertanyaan` | string | ✅ | text | Teks pertanyaan |
| `opsi` | array | ❌ | — | Array string opsi jawaban |
| `opsi.*` | string | — | — | Jika ada → pertanyaan pilihan ganda. Jika kosong → essay. |

**Contoh Request (Pilihan Ganda):**
```json
{
    "pertanyaan": "Apakah ilmu yang didapat di SMK berguna di pekerjaan saat ini?",
    "opsi": [
        "Sangat Berguna",
        "Cukup Berguna",
        "Kurang Berguna",
        "Tidak Berguna"
    ]
}
```

**Contoh Request (Essay):**
```json
{
    "pertanyaan": "Berikan saran untuk peningkatan kualitas pendidikan:"
}
```

**Response (201 Created):**
```json
{
    "status": "success",
    "message": "Pertanyaan berhasil ditambahkan",
    "data": {
        "id": 5,
        "pertanyaan": "Apakah ilmu yang didapat di SMK berguna di pekerjaan saat ini?",
        "opsi": [
            { "id": 10, "opsi": "Sangat Berguna" },
            { "id": 11, "opsi": "Cukup Berguna" },
            { "id": 12, "opsi": "Kurang Berguna" },
            { "id": 13, "opsi": "Tidak Berguna" }
        ]
    }
}
```

> **Catatan pada Update:** Ketika mengupdate pertanyaan dengan `opsi`, semua opsi lama **dihapus** dan diganti dengan opsi baru.

---

#### 4.4.6 Master Data Management (Admin CRUD)

Semua master data memiliki pola CRUD yang sama.  
Prefix: `/api/admin/master`

| Resource | POST (Create) | PUT (Update) | DELETE |
|----------|--------------|--------------|--------|
| Provinsi | `/master/provinsi` | `/master/provinsi/{id}` | `/master/provinsi/{id}` |
| Kota | `/master/kota` | `/master/kota/{id}` | `/master/kota/{id}` |
| Jurusan (SMK) | `/master/jurusan` | `/master/jurusan/{id}` | `/master/jurusan/{id}` |
| Jurusan Kuliah | `/master/jurusan-kuliah` | `/master/jurusan-kuliah/{id}` | `/master/jurusan-kuliah/{id}` |
| Skills | `/master/skills` | `/master/skills/{id}` | `/master/skills/{id}` |
| Social Media | `/master/social-media` | `/master/social-media/{id}` | `/master/social-media/{id}` |
| Status | `/master/status` | `/master/status/{id}` | `/master/status/{id}` |
| Bidang Usaha | `/master/bidang-usaha` | `/master/bidang-usaha/{id}` | `/master/bidang-usaha/{id}` |
| Perusahaan | `/master/perusahaan` (GET+POST) | `/master/perusahaan/{id}` | `/master/perusahaan/{id}` |
| Universitas | `/master/universitas` (POST only) | — | — |

**Detail Field per Resource:**

**Provinsi:**
| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `nama_provinsi` | string | ✅ | max:255, unique:provinsi |

**Kota:**
| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `nama_kota` | string | ✅ | max:255 |
| `id_provinsi` | integer | ✅ | exists:provinsi,id_provinsi |

**Jurusan (SMK):**
| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `nama_jurusan` | string | ✅ | max:255, unique:jurusan |

**Jurusan Kuliah:**
| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `nama_jurusan_kuliah` | string | ✅ | max:255, unique:jurusan_kuliah |

**Skills:**
| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `name_skills` | string | ✅ | max:255, unique:skills |

**Social Media:**
| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `nama_sosmed` | string | ✅ | max:255, unique:social_media |
| `icon_sosmed` | string | ❌ | max:255 |

**Status:**
| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `nama_status` | string | ✅ | max:255, unique:status |

**Bidang Usaha:**
| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `nama_bidang` | string | ✅ | max:255, unique:bidang_usaha |

**Perusahaan:**
| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `nama_perusahaan` | string | ✅ | max:255 |
| `id_bidang_usaha` | integer | ✅ | exists:bidang_usaha,id_bidang_usaha |
| `id_kota` | integer | ✅ | exists:kota,id_kota |
| `alamat_perusahaan` | string | ❌ | — |

**Query Params `GET /admin/master/perusahaan`:**
| Param | Tipe | Keterangan |
|-------|------|------------|
| `search` | string | Cari berdasarkan nama_perusahaan |
| `id_kota` | integer | Filter berdasarkan kota |
| `per_page` | integer | Default: 15 |

**Universitas:**
| Field | Tipe | Wajib | Validasi |
|-------|------|-------|----------|
| `nama_universitas` | string | ✅ | max:255 |

---

### 4.5 Public / Master Data

> Endpoint ini **TIDAK MEMBUTUHKAN LOGIN** — digunakan untuk mengisi dropdown form di frontend.

| Endpoint | Method | Response Format | Keterangan |
|----------|--------|-----------------|------------|
| `/api/master/provinsi` | GET | `ProvinsiResource[]` | Semua provinsi (sorted A-Z) |
| `/api/master/kota` | GET | `KotaResource[]` | Semua kota + relasi provinsi |
| `/api/master/kota?id_provinsi=5` | GET | `KotaResource[]` | Kota di provinsi tertentu |
| `/api/master/jurusan` | GET | `JurusanResource[]` | Semua jurusan SMK |
| `/api/master/jurusan-kuliah` | GET | `JurusanKuliahResource[]` | Semua jurusan kuliah |
| `/api/master/skills` | GET | `SkillResource[]` | Semua skill |
| `/api/master/social-media` | GET | `SocialMediaResource[]` | Semua platform sosial media |
| `/api/master/status` | GET | `StatusResource[]` | Semua jenis status karir |
| `/api/master/bidang-usaha` | GET | `BidangUsahaResource[]` | Semua bidang usaha |
| `/api/master/universitas` | GET | Collection | Semua universitas + jurusan kuliah |
| `/api/lowongan/published` | GET | `LowonganResource[]` | Lowongan approved + published (paginated) |
| `/api/lowongan/{id}` | GET | `LowonganResource` | Detail satu lowongan |
| `/api/kuesioner/published` | GET | `KuesionerResource[]` | Kuesioner yang sudah dipublikasi (paginated) |

**Contoh Response — `GET /api/master/jurusan`:**
```json
{
    "status": "success",
    "message": "Berhasil",
    "data": [
        { "id": 1, "nama": "Rekayasa Perangkat Lunak" },
        { "id": 2, "nama": "Teknik Komputer dan Jaringan" },
        { "id": 3, "nama": "Multimedia" },
        { "id": 4, "nama": "Akuntansi" }
    ]
}
```

**Contoh Response — `GET /api/master/kota?id_provinsi=1`:**
```json
{
    "status": "success",
    "message": "Berhasil",
    "data": [
        { "id": 1, "nama": "Surabaya", "provinsi": { "id": 1, "nama": "Jawa Timur" } },
        { "id": 2, "nama": "Malang", "provinsi": { "id": 1, "nama": "Jawa Timur" } }
    ]
}
```

**Contoh Response — `GET /api/master/skills`:**
```json
{
    "status": "success",
    "message": "Berhasil",
    "data": [
        { "id": 1, "nama": "CSS" },
        { "id": 2, "nama": "HTML" },
        { "id": 3, "nama": "JavaScript" },
        { "id": 4, "nama": "PHP" }
    ]
}
```

**Contoh Response — `GET /api/master/status`:**
```json
{
    "status": "success",
    "message": "Berhasil",
    "data": [
        { "id": 1, "nama": "Bekerja" },
        { "id": 2, "nama": "Kuliah" },
        { "id": 3, "nama": "Wirausaha" },
        { "id": 4, "nama": "Belum Bekerja" }
    ]
}
```

---

### 4.6 Daftar Error Codes

| HTTP Code | Status | Keterangan | Kapan Terjadi |
|-----------|--------|------------|---------------|
| `200` | OK | Request berhasil | GET, PUT, POST (login, logout) |
| `201` | Created | Data berhasil dibuat | POST (register, create resource) |
| `401` | Unauthorized | Token tidak valid / belum login | Akses route protected tanpa token |
| `403` | Forbidden | Role tidak diizinkan | Alumni akses admin route, atau sebaliknya |
| `404` | Not Found | Resource tidak ditemukan | ID tidak ada di database |
| `422` | Unprocessable Entity | Validasi gagal | Field kosong, format salah, email duplikat |
| `500` | Internal Server Error | Error server | Bug, missing table, config error |

> **Debugging 500 Error:** Cek file `storage/logs/laravel.log` untuk detail error.

---

## RINGKASAN TOTAL ENDPOINT: 74 Routes

| Kategori | Jumlah | Auth Required | Role |
|----------|--------|---------------|------|
| Autentikasi (register, login, me, logout) | 4 | 2 public, 2 protected | — |
| Alumni (profile, career, save lowongan, kuesioner) | 7 | ✅ | alumni |
| Admin Dashboard & User Management | 7 | ✅ | admin |
| Admin Lowongan | 7 | ✅ | admin |
| Admin Kuesioner + Pertanyaan | 8 | ✅ | admin |
| Admin Master Data CRUD | 28 | ✅ | admin |
| Public Master Data (dropdown) | 9 | ❌ | — |
| Public Lowongan & Kuesioner | 4 | ❌ | — |
| **Total** | **74** | | |
