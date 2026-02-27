<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Performance indexes for frequently queried columns.
     */
    public function up(): void
    {
        // Alumni: status_create filter + search
        Schema::table('alumni', function (Blueprint $table) {
            $table->index('status_create');
            $table->index('id_jurusan');
            $table->index('id_users');
            $table->index('tahun_lulus');
            $table->index(['status_create', 'created_at']);
        });

        // Jawaban: aggregation by user + pertanyaan
        Schema::table('jawaban', function (Blueprint $table) {
            $table->index('id_user');
            $table->index('id_pertanyaan');
            $table->index(['id_user', 'id_pertanyaan']);
        });

        // Riwayat status: distribution queries
        Schema::table('riwayat_status', function (Blueprint $table) {
            $table->index('id_alumni');
            $table->index('id_status');
            $table->index(['id_status', 'tahun_selesai']);
        });

        // Lowongan: filter by status + approval
        Schema::table('lowongan', function (Blueprint $table) {
            $table->index('approval_status');
            $table->index('status');
            $table->index(['status', 'approval_status']);
        });

        // Pertanyaan: section lookup
        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->index('id_sectionques');
        });

        // Section ques: kuesioner lookup
        Schema::table('section_ques', function (Blueprint $table) {
            $table->index('id_kuesioner');
        });

        // Opsi jawaban: pertanyaan lookup
        Schema::table('opsi_jawaban', function (Blueprint $table) {
            $table->index('id_pertanyaan');
        });

        // Pekerjaan: joins
        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->index('id_perusahaan');
            $table->index('id_riwayat');
        });

        // Perusahaan: kota lookup
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->index('id_kota');
        });

        // Kota: provinsi lookup
        Schema::table('kota', function (Blueprint $table) {
            $table->index('id_provinsi');
        });

        // Simpan lowongan: user filter
        Schema::table('simpan_lowongan', function (Blueprint $table) {
            $table->index(['id_user', 'id_lowongan']);
        });
    }

    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropIndex(['status_create']);
            $table->dropIndex(['id_jurusan']);
            $table->dropIndex(['id_users']);
            $table->dropIndex(['tahun_lulus']);
            $table->dropIndex(['status_create', 'created_at']);
        });

        Schema::table('jawaban', function (Blueprint $table) {
            $table->dropIndex(['id_user']);
            $table->dropIndex(['id_pertanyaan']);
            $table->dropIndex(['id_user', 'id_pertanyaan']);
        });

        Schema::table('riwayat_status', function (Blueprint $table) {
            $table->dropIndex(['id_alumni']);
            $table->dropIndex(['id_status']);
            $table->dropIndex(['id_status', 'tahun_selesai']);
        });

        Schema::table('lowongan', function (Blueprint $table) {
            $table->dropIndex(['approval_status']);
            $table->dropIndex(['status']);
            $table->dropIndex(['status', 'approval_status']);
        });

        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->dropIndex(['id_sectionques']);
        });

        Schema::table('section_ques', function (Blueprint $table) {
            $table->dropIndex(['id_kuesioner']);
        });

        Schema::table('opsi_jawaban', function (Blueprint $table) {
            $table->dropIndex(['id_pertanyaan']);
        });

        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->dropIndex(['id_perusahaan']);
            $table->dropIndex(['id_riwayat']);
        });

        Schema::table('perusahaan', function (Blueprint $table) {
            $table->dropIndex(['id_kota']);
        });

        Schema::table('kota', function (Blueprint $table) {
            $table->dropIndex(['id_provinsi']);
        });

        Schema::table('simpan_lowongan', function (Blueprint $table) {
            $table->dropIndex(['id_user', 'id_lowongan']);
        });
    }
};
