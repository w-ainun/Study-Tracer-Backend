<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =============================================
        // Tabel staging: calon_lulusan
        // Data siswa yang diimpor admin (via Excel/manual)
        // sebelum dikonfirmasi lulus dan masuk tabel alumni.
        // =============================================
        Schema::create('calon_lulusan', function (Blueprint $table) {
            $table->id('id_calon');
            $table->string('nisn', 20)->index();
            $table->string('nama', 150);
            $table->unsignedBigInteger('id_jurusan');
            $table->unsignedBigInteger('imported_by')->nullable()->comment('Admin who imported');
            $table->string('batch_id', 36)->nullable()->index()->comment('UUID grouping per import session');
            $table->timestamps();

            $table->foreign('id_jurusan')->references('id_jurusan')->on('jurusan')->onDelete('cascade');
            $table->foreign('imported_by')->references('id_users')->on('users')->onDelete('set null');
        });

        // =============================================
        // Tabel riwayat: riwayat_kelulusan
        // Data lulusan yang sudah resmi dikonfirmasi admin.
        // Terpisah dari tabel alumni agar tidak menggangu
        // flow registrasi alumni yang sudah ada.
        // =============================================
        Schema::create('riwayat_kelulusan', function (Blueprint $table) {
            $table->id('id_kelulusan');
            $table->string('nisn', 20)->index();
            $table->string('nama', 150);
            $table->unsignedBigInteger('id_jurusan');
            $table->year('tahun_lulus');
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('Admin who confirmed');
            $table->string('batch_id', 36)->nullable()->index()->comment('UUID grouping per graduation batch');
            $table->timestamps();

            $table->foreign('id_jurusan')->references('id_jurusan')->on('jurusan')->onDelete('cascade');
            $table->foreign('confirmed_by')->references('id_users')->on('users')->onDelete('set null');

            // Composite index for fast filtering
            $table->index(['tahun_lulus', 'id_jurusan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_kelulusan');
        Schema::dropIfExists('calon_lulusan');
    }
};
