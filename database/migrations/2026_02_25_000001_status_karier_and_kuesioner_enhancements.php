<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Add code column to provinsi & kota ───────────────
        if (!Schema::hasColumn('provinsi', 'code')) {
            Schema::table('provinsi', function (Blueprint $table) {
                $table->string('code')->unique()->after('nama_provinsi');
            });
        }

        if (!Schema::hasColumn('kota', 'code')) {
            Schema::table('kota', function (Blueprint $table) {
                $table->string('code')->unique()->after('id_provinsi');
            });
        }

        // ─── Enhance pertanyaan_kuesioner ─────────────────────
        Schema::table('pertanyaan_kuesioner', function (Blueprint $table) {
            $table->string('tipe_pertanyaan')->default('pilihan_tunggal')->after('pertanyaan');
            $table->string('status_pertanyaan')->default('DRAF')->after('tipe_pertanyaan');
            $table->string('kategori')->nullable()->after('status_pertanyaan');
            $table->string('judul_bagian')->nullable()->after('kategori');
            $table->integer('urutan')->default(0)->after('judul_bagian');
        });

        // ─── Make universitas FK columns nullable (admin can create name-only entries) ───
        DB::statement("ALTER TABLE universitas MODIFY id_jurusanKuliah BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE universitas MODIFY jalur_masuk ENUM('SNBP','SNBT','Mandiri','Beasiswa','lainnya') NULL");
        DB::statement("ALTER TABLE universitas MODIFY id_riwayat BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE universitas MODIFY jenjang ENUM('D3','D4','S1','S2','S3') NULL");
    }

    public function down(): void
    {
        // Restore universitas columns to NOT NULL
        DB::statement("ALTER TABLE universitas MODIFY jenjang ENUM('D3','D4','S1','S2','S3') NOT NULL");
        DB::statement("ALTER TABLE universitas MODIFY id_riwayat BIGINT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE universitas MODIFY jalur_masuk ENUM('SNBP','SNBT','Mandiri','Beasiswa','lainnya') NOT NULL");
        DB::statement("ALTER TABLE universitas MODIFY id_jurusanKuliah BIGINT UNSIGNED NOT NULL");

        Schema::table('pertanyaan_kuesioner', function (Blueprint $table) {
            $table->dropColumn(['tipe_pertanyaan', 'status_pertanyaan', 'kategori', 'judul_bagian', 'urutan']);
        });

        if (Schema::hasColumn('kota', 'code')) {
            Schema::table('kota', function (Blueprint $table) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            });
        }

        if (Schema::hasColumn('provinsi', 'code')) {
            Schema::table('provinsi', function (Blueprint $table) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            });
        }
    }
};
