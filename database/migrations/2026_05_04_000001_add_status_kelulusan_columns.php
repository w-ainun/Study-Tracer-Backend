<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calon_lulusan', function (Blueprint $table) {
            $table->enum('status_kelulusan', ['lulus', 'tidak_lulus'])->default('lulus')->after('id_jurusan');
        });

        Schema::table('riwayat_kelulusan', function (Blueprint $table) {
            $table->enum('status_kelulusan', ['lulus', 'tidak_lulus'])->default('lulus')->after('id_jurusan');
        });
    }

    public function down(): void
    {
        Schema::table('calon_lulusan', function (Blueprint $table) {
            $table->dropColumn('status_kelulusan');
        });

        Schema::table('riwayat_kelulusan', function (Blueprint $table) {
            $table->dropColumn('status_kelulusan');
        });
    }
};
