<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        // ─── Create posisi table ──────────────────────────────
        Schema::create('posisi', function (Blueprint $table) {
            $table->id('id_posisi');
            $table->string('nama_posisi');
            $table->timestamps();
        });

        // ─── Create referensi_universitas table ───────────────
        Schema::create('referensi_universitas', function (Blueprint $table) {
            $table->id('id_ref_univ');
            $table->string('nama_universitas');
            $table->json('jurusan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referensi_universitas');
        Schema::dropIfExists('posisi');

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
