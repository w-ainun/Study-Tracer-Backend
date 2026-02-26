# Database Restructure - Kuesioner System

## Overview

Perubahan struktur database kuesioner sesuai dengan ERD diagram baru. Struktur baru menggunakan table intermediary `section_ques` untuk mengelompokkan pertanyaan berdasarkan section/bagian.

## Perubahan Struktur Database

### Schema Lama

```
kuesioner (1) ─── (N) pertanyaan_kuesioner (1) ─── (N) opsi_jawaban
                                                 │
                                                 └── (N) jawaban_kuesioner
```

### Schema Baru

```
status (1) ─── (N) kuesioner (1) ─── (N) section_ques (1) ─── (N) pertanyaan (1) ─── (N) opsi_jawaban
                                                                                    │
                                                                                    └── (N) jawaban
```

## Detail Perubahan

### 1. Table Baru: `section_ques`

```sql
CREATE TABLE section_ques (
    id_sectionques BIGINT PRIMARY KEY AUTO_INCREMENT,
    id_kuesioner BIGINT (FK to kuesioner),
    judul_pertanyaan VARCHAR,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Purpose**: Mengelompokkan pertanyaan berdasarkan section/bagian (misal: Status Pekerjaan, Riwayat Pendidikan, dll)

### 2. Table `kuesioner` - Modified

**Perubahan:**

- ✅ **Added**: `id_status` (FK to status table)
- ✅ **Modified**: `status_kuesioner` enum dari `['draft', 'publish', 'close']` menjadi `['hidden', 'aktif', 'draft']`

```sql
ALTER TABLE kuesioner:
- ADD id_status BIGINT NULL
- CHANGE status_kuesioner ENUM('hidden', 'aktif', 'draft') DEFAULT 'draft'
- ADD FOREIGN KEY (id_status) REFERENCES status(id_status)
```

### 3. Table `pertanyaan_kuesioner` → `pertanyaan` - Renamed & Modified

**Perubahan:**

- ✅ **Renamed**: Table `pertanyaan_kuesioner` → `pertanyaan`
- ✅ **Renamed**: Column `id_pertanyaanKuis` → `id_pertanyaan`
- ✅ **Renamed**: Column `pertanyaan` → `isi_pertanyaan`
- ✅ **Removed**: Column `id_kuesioner` (direct relation)
- ✅ **Added**: Column `id_sectionques` (FK to section_ques)
- ✅ **Removed**: Columns `tipe_pertanyaan`, `status_pertanyaan`, `kategori`, `judul_bagian`, `urutan` (dari enhancement migration)

### 4. Table `jawaban_kuesioner` → `jawaban` - Renamed

**Perubahan:**

- ✅ **Renamed**: Table `jawaban_kuesioner` → `jawaban`
- ✅ **Renamed**: Column `id_jawabanKuis` → `id_jawaban`
- FK references updated ke table `pertanyaan`

### 5. Table `opsi_jawaban` - Modified

**Perubahan:**

- ✅ **Updated**: Foreign key `id_pertanyaan` sekarang references `pertanyaan(id_pertanyaan)` bukan `pertanyaan_kuesioner(id_pertanyaanKuis)`

## Migration File

File migration: `2026_02_25_100000_restructure_kuesioner_tables.php`

**Location**: `database/migrations/2026_02_25_100000_restructure_kuesioner_tables.php`

## Models Baru/Updated

### New Models:

1. **SectionQues.php** - Model untuk table `section_ques`
2. **Pertanyaan.php** - Model untuk table `pertanyaan` (renamed from PertanyaanKuesioner)
3. **Jawaban.php** - Model untuk table `jawaban` (renamed from JawabanKuesioner)

### Updated Models:

1. **Kuesioner.php** - Added relation to Status and SectionQues
2. **OpsiJawaban.php** - Updated relation to Pertanyaan
3. **Status.php** - Added relation to Kuesioner

## Repositories & Services

### New Repository:

- **SectionQuesRepository.php** - Repository untuk manage section_ques

### Updated Repository:

- **KuesionerRepository.php** - Updated untuk menggunakan model baru (Pertanyaan, Jawaban, SectionQues)

### Provider:

- **AppServiceProvider.php** - Added binding untuk SectionQuesRepository

## Cara Menjalankan Migration

### Step 1: Backup Database

**PENTING**: Backup database Anda terlebih dahulu!

```bash
# Untuk MySQL
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# Atau via PHP artisan
php artisan db:backup
```

### Step 2: Jalankan Migration

```bash
# Di folder Study-Tracer-Backend
cd "b:\Kuliah\Semester 6\study_tracer\Study-Tracer-Backend"

# Roll fresh (WARNING: This will delete all data!)
php artisan migrate:fresh

# Atau roll back dan migrate ulang (WARNING: This will delete data!)
php artisan migrate:rollback
php artisan migrate

# Atau hanya migrate yang baru (SAFEST if you have existing data)
php artisan migrate
```

### Step 3: Seed Data (Optional)

Jika Anda punya seeder untuk data awal:

```bash
php artisan db:seed
```

## Cara Migrate Data Existing (Manual)

Jika Anda memiliki data existing dan ingin preserve, lakukan langkah berikut **SEBELUM** menjalankan migration:

### 1. Export Data Existing

```sql
-- Export pertanyaan_kuesioner
SELECT * FROM pertanyaan_kuesioner INTO OUTFILE '/tmp/pertanyaan_backup.csv'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Export jawaban_kuesioner
SELECT * FROM jawaban_kuesioner INTO OUTFILE '/tmp/jawaban_backup.csv'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Export opsi_jawaban
SELECT * FROM opsi_jawaban INTO OUTFILE '/tmp/opsi_backup.csv'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

### 2. Jalankan Migration

```bash
php artisan migrate
```

### 3. Migrate Data Script

Buat seeder atau script manual untuk memindahkan data:

```php
// database/seeders/MigrateKuesionerDataSeeder.php
public function run()
{
    // 1. Buat section_ques default untuk setiap kuesioner
    $kuesioners = DB::table('kuesioner')->get();

    foreach ($kuesioners as $kuesioner) {
        $sectionId = DB::table('section_ques')->insertGetId([
            'id_kuesioner' => $kuesioner->id_kuesioner,
            'judul_pertanyaan' => 'Umum', // Default section name
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Update pertanyaan yang ada untuk point ke section baru
        DB::table('pertanyaan')
            ->where('id_kuesioner', $kuesioner->id_kuesioner)
            ->update(['id_sectionques' => $sectionId]);
    }
}
```

## Testing Migration

### 1. Test Database Structure

```bash
# Check tables exist
php artisan tinker

>>> \Schema::hasTable('section_ques')
=> true

>>> \Schema::hasTable('pertanyaan')
=> true

>>> \Schema::hasTable('jawaban')
=> true
```

### 2. Test Models

```bash
php artisan tinker

# Test Kuesioner with relations
>>> $k = App\Models\Kuesioner::with('sectionQues.pertanyaan.opsiJawaban')->first()
>>> $k->sectionQues
>>> $k->pertanyaan

# Test SectionQues
>>> $s = App\Models\SectionQues::with('pertanyaan')->first()
>>> $s->pertanyaan

# Test Pertanyaan
>>> $p = App\Models\Pertanyaan::with('opsiJawaban')->first()
>>> $p->opsiJawaban
```

### 3. Test Application

1. Start backend server:

```bash
php artisan serve
```

2. Test API endpoints:

```bash
# Get kuesioner
curl -X GET "http://localhost:8000/api/admin/kuesioner" \
  -H "Authorization: Bearer YOUR_TOKEN"

# Get kuesioner detail with sections
curl -X GET "http://localhost:8000/api/admin/kuesioner/1" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Rollback Instructions

Jika terjadi masalah, Anda bisa rollback:

```bash
# Rollback migration terakhir
php artisan migrate:rollback

# Atau rollback step tertentu
php artisan migrate:rollback --step=1

# Restore dari backup
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql
```

## API Changes

### Endpoint yang Terpengaruh:

#### 1. GET /api/admin/kuesioner

**Before:**

```json
{
  "id_kuesioner": 1,
  "status_kuesioner": "publish",
  "pertanyaan": [...]
}
```

**After:**

```json
{
  "id_kuesioner": 1,
  "id_status": 2,
  "status_kuesioner": "aktif",
  "status": {
    "id_status": 2,
    "nama_status": "Bekerja"
  },
  "section_ques": [
    {
      "id_sectionques": 1,
      "judul_pertanyaan": "Status Pekerjaan",
      "pertanyaan": [...]
    }
  ]
}
```

#### 2. POST /api/admin/kuesioner/{id}/pertanyaan

**Before:**

```json
{
    "pertanyaan": "...",
    "id_kuesioner": 1
}
```

**After:**

```json
{
    "isi_pertanyaan": "...",
    "judul_bagian": "Status Pekerjaan"
}
```

_Note: System akan otomatis create atau find existing section_ques berdasarkan judul_bagian_

## Frontend Changes Required

### 1. Form Tambah Pertanyaan

- Field `pertanyaan` → `isi_pertanyaan`
- Auto-create `section_ques` berdasarkan `judul_bagian`

### 2. Display Kuesioner

- Loop through `section_ques` → `pertanyaan` (nested)
- Grouping by section

### 3. Filter/Search

- Filter berdasarkan `id_status` (referensi ke status table)
- Status dropdown dari table `status`

## Troubleshooting

### Error: "Table doesn't exist"

**Solution**: Pastikan migration sudah dijalankan

```bash
php artisan migrate:status
php artisan migrate
```

### Error: "Foreign key constraint fails"

**Solution**: Pastikan urutan migration benar. Table `status` harus sudah exist sebelum table `kuesioner`.

### Error: "Column not found"

**Solution**: Clear cache dan config:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Data hilang setelah migration

**Solution**: Restore dari backup:

```bash
mysql -u username -p database_name < backup_file.sql
```

## Notes

⚠️ **PENTING**:

- Migration ini akan **MENGUBAH STRUKTUR TABLE** yang ada
- **DROP COLUMNS** yang tidak diperlukan dari enhancement migration
- **RENAME TABLES** dan **COLUMNS**
- Pastikan **BACKUP DATABASE** sebelum menjalankan migration
- Test di environment development terlebih dahulu
- Existing data might need manual migration script

📝 **Recommendation**:

- Jalankan migration di environment staging terlebih dahulu
- Test semua fitur CRUD kuesioner
- Test form submission dari frontend
- Validate data integrity
- Baru deploy ke production

## Contact & Support

Jika ada pertanyaan atau issue terkait migration, hubungi tim development atau buat issue di repository.
