<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lowongan', function (Blueprint $table) {
            // Change lowongan_selesai from time to date (frontend sends mm/dd/yyyy)
            $table->date('lowongan_selesai')->nullable()->change();

            // Add job category/type for frontend categories
            $table->string('tipe_pekerjaan')->nullable()->after('deskripsi');

            // Add direct location text (frontend sends text, not FK)
            $table->string('lokasi')->nullable()->after('tipe_pekerjaan');

            // Add who posted this lowongan
            $table->unsignedBigInteger('id_users')->nullable()->after('id_perusahaan');
            $table->foreign('id_users')->references('id_users')->on('users')->onDelete('set null');

            // Make id_perusahaan nullable (can create from nama_perusahaan)
            $table->unsignedBigInteger('id_perusahaan')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lowongan', function (Blueprint $table) {
            $table->dropForeign(['id_users']);
            $table->dropColumn(['tipe_pekerjaan', 'lokasi', 'id_users']);
            $table->time('lowongan_selesai')->nullable()->change();
            $table->unsignedBigInteger('id_perusahaan')->change();
        });
    }
};
