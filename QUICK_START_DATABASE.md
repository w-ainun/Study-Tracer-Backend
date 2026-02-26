# Quick Start - Database Restructure Kuesioner

## 🎯 Ringkasan Perubahan

Struktur database kuesioner telah diubah sesuai dengan ERD diagram baru:

### Perubahan Utama:

1. ✅ **Table Baru**: `section_ques` - untuk grouping pertanyaan
2. ✅ **Table Renamed**: `pertanyaan_kuesioner` → `pertanyaan`
3. ✅ **Table Renamed**: `jawaban_kuesioner` → `jawaban`
4. ✅ **Kuesioner**: Ditambahkan relasi ke table `status` via `id_status`
5. ✅ **Pertanyaan**: Sekarang berelasi ke `section_ques` bukan langsung ke `kuesioner`
6. ✅ **Status Kuesioner**: Enum diubah dari `['draft', 'publish', 'close']` → `['hidden', 'aktif', 'draft']`
7. ✅ **Pertanyaan Simplified**: Kolom `tipe_pertanyaan`, `status_pertanyaan`, `urutan`, `kategori`, `judul_bagian` **dihapus**
8. ✅ **Pertanyaan Structure**: Hanya berisi `id_pertanyaan`, `id_sectionques`, `isi_pertanyaan`, `created_at`, `updated_at`
9. ✅ **Kuesioner Simplified**: Kolom `judul_kuesioner` dan `deskripsi_kuesioner` **tidak ada** - gunakan `judul_pertanyaan` di `section_ques` sebagai grouping

## 🚀 Cara Menggunakan

### Step 1: Backup Database (WAJIB!)

```bash
# Di folder backend
cd "b:\Kuliah\Semester 6\study_tracer\Study-Tracer-Backend"

# Backup via mysqldump (Windows)
# Sesuaikan username dan database name
mysqldump -u root -p study_tracer > backup_20260225.sql
```

### Step 2: Jalankan Migration dengan Seed

```bash
# Option A: Fresh migrate dengan seed (HAPUS SEMUA DATA!)
php artisan migrate:fresh --seed

# Option B: Migrate saja (tanpa hapus data lama)
php artisan migrate

# Option C: Seed saja (setelah migrate)
php artisan db:seed --class=KuesionerSeeder
```

**Seeder akan membuat:**

- ✅ 3 Kuesioner (Bekerja, Kuliah, Wirausaha)
- ✅ 7 Section berbeda
- ✅ 13 Pertanyaan dengan berbagai tipe
- ✅ Puluhan opsi jawaban

### Step 3: Verify Data

```bash
# Check database structure
php artisan tinker

# Test query kuesioner dengan relasi
>>> App\Models\Kuesioner::with('status', 'sectionQues.pertanyaan.opsiJawaban')->get()

# Lihat kuesioner pertama
>>> $k = App\Models\Kuesioner::with('sectionQues.pertanyaan')->first()
>>> $k->status->nama_status
>>> $k->status_kuesioner
>>> $k->sectionQues->count()

# Lihat section dan pertanyaan
>>> $section = App\Models\SectionQues::with('pertanyaan.opsiJawaban')->first()
>>> $section->judul_pertanyaan
>>> $section->pertanyaan->count()

# Exit tinker
>>> exit
```

### Step 4: Restart Server

```bash
php artisan serve
```

## 📋 Files yang Berubah

### Backend:

- ✅ **Migration**: `database/migrations/2026_02_25_100000_restructure_kuesioner_tables.php`
- ✅ **Models**: `Kuesioner.php`, `SectionQues.php` (baru), `Pertanyaan.php` (baru), `Jawaban.php` (baru), `OpsiJawaban.php`, `Status.php`
- ✅ **Repository**: `KuesionerRepository.php`, `SectionQuesRepository.php` (baru)
- ✅ **Interface**: `SectionQuesRepositoryInterface.php` (baru)
- ✅ **Provider**: `AppServiceProvider.php`

### Struktur Database Baru:

```
┌─────────────┐
│   status    │
│ id_status   │◄────┐
│ nama_status │     │
└─────────────┘     │
                    │
           ┌────────┴────────┐
           │   kuesioner     │
           │ id_kuesioner    │
           │ id_status (FK)  │◄────┐
           │ status_kuesioner│     │
           │ tanggal_publikasi│    │
           │ timestamps      │     │
           └────────┬────────┘     │
                    │               │
                    │               │
          ┌─────────▼────────┐     │
          │  section_ques    │     │
          │ id_sectionques   │     │
          │ id_kuesioner (FK)│     │
          │ judul_pertanyaan │     │
          └─────────┬────────┘     │
                    │               │
                    │               │
           ┌────────▼────────┐     │
           │   pertanyaan    │     │
           │ id_pertanyaan   │     │
           │ id_sectionques  │     │
           │ isi_pertanyaan  │     │
           └────────┬────────┘     │
                    │               │
         ┌──────────┴──────────┐   │
         │                     │   │
┌────────▼────────┐   ┌────────▼───┴───┐
│  opsi_jawaban   │   │    jawaban     │
│ id_opsi         │   │ id_jawaban     │
│ id_pertanyaan   │   │ id_pertanyaan  │
│ opsi            │   │ id_user        │
└─────────────────┘   │ id_opsiJawaban │
                      │ jawaban        │
                      └────────────────┘
```

## 💡 Contoh Penggunaan

### Membuat Kuesioner dengan Section

```php
// 1. Buat kuesioner (minimal: hanya status)
$kuesioner = Kuesioner::create([
    'id_status' => 1, // ID status "Bekerja"
    'status_kuesioner' => 'draft',
    'tanggal_publikasi' => null,
]);

// 2. Buat section (judul ada di section, bukan kuesioner)
$section = SectionQues::create([
    'id_kuesioner' => $kuesioner->id_kuesioner,
    'judul_pertanyaan' => 'Kepuasan Pendidikan', // Judul ada di section
]);
    'judul_pertanyaan' => 'Status Pekerjaan',
]);

// 3. Buat pertanyaan
$pertanyaan = Pertanyaan::create([
    'id_sectionques' => $section->id_sectionques,
    'isi_pertanyaan' => 'Apa status pekerjaan Anda saat ini?',
]);

// 4. Buat opsi
OpsiJawaban::create([
    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
    'opsi' => 'Bekerja Penuh Waktu',
]);
```

### Query dengan Eager Loading

```php
// Get kuesioner dengan semua relasi
$kuesioner = Kuesioner::with([
    'status',
    'sectionQues.pertanyaan.opsiJawaban'
])->find(1);

// Loop sections
foreach ($kuesioner->sectionQues as $section) {
    echo $section->judul_pertanyaan;

    foreach ($section->pertanyaan as $pertanyaan) {
        echo $pertanyaan->isi_pertanyaan;

        foreach ($pertanyaan->opsiJawaban as $opsi) {
            echo $opsi->opsi;
        }
    }
}
```

## ⚠️ Breaking Changes

### API Response Structure Changed:

**Before:**

```json
{
    "id_kuesioner": 1,
    "pertanyaan": [{ "id_pertanyaanKuis": 1, "pertanyaan": "..." }]
}
```

**After:**

```json
{
    "id_kuesioner": 1,
    "id_status": 2,
    "status": { "id_status": 2, "nama_status": "Bekerja" },
    "section_ques": [
        {
            "id_sectionques": 1,
            "judul_pertanyaan": "Status Pekerjaan",
            "pertanyaan": [{ "id_pertanyaan": 1, "isi_pertanyaan": "..." }]
        }
    ]
}
```

### Form Submission Changed:

**Before:**

```javascript
{
  pertanyaan: "...",
  id_kuesioner: 1
}
```

**After:**

```javascript
{
  isi_pertanyaan: "...",
  judul_bagian: "Status Pekerjaan"
}
```

## 🔧 Troubleshooting

### Error: "Table doesn't exist"

```bash
php artisan migrate:status  # Check migration status
php artisan migrate         # Run migration
```

### Error: "Foreign key constraint"

```bash
# Drop all tables and migrate fresh
php artisan migrate:fresh
```

### Data hilang

```bash
# Restore from backup
mysql -u root -p study_tracer < backup_YYYYMMDD.sql
```

### Cache issues

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## 📚 Dokumentasi Lengkap

Lihat file [DATABASE_RESTRUCTURE.md](DATABASE_RESTRUCTURE.md) untuk dokumentasi lengkap.

## ✅ Checklist Setelah Migration

- [ ] Migration berhasil dijalankan
- [ ] Semua table baru terbuat (section_ques, pertanyaan, jawaban)
- [ ] Seeder berhasil dijalankan (3 kuesioner, 7 sections, 13 pertanyaan terbuat)
- [ ] Test query dengan tinker
- [ ] Verify relasi: kuesioner → status, kuesioner → section_ques → pertanyaan → opsi_jawaban
- [ ] Test API endpoints
- [ ] Test form tambah pertanyaan
- [ ] Verify data integrity
- [ ] Test pada frontend

## 📊 Data yang Dibuat oleh Seeder

Setelah menjalankan `php artisan migrate:fresh --seed`, seeder akan membuat:

### Kuesioner 1: Alumni Bekerja (id_status = 1)

**Status**: Aktif

- **Section 1**: Kepuasan Pendidikan (2 pertanyaan)
- **Section 2**: Informasi Karier (2 pertanyaan)
- **Section 3**: Penilaian Umum (2 pertanyaan)

### Kuesioner 2: Alumni Kuliah (id_status = 2)

**Status**: Draft

- **Section 1**: Informasi Studi Lanjut (2 pertanyaan)
- **Section 2**: Pengembangan Diri (2 pertanyaan)

### Kuesioner 3: Alumni Wirausaha (id_status = 3)

**Status**: Aktif

- **Section 1**: Informasi Usaha (3 pertanyaan)

**Total**: 3 Kuesioner, 7 Sections, 13 Pertanyaan, ~45 Opsi Jawaban

## 💬 Support

Jika ada masalah atau pertanyaan, silakan buat issue di repository atau hubungi tim development.

---

**Last Updated**: February 25, 2026
