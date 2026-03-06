<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // alumni - optimasi search & filter
        Schema::table('alumni', function (Blueprint $table) {
            $table->index('status_create', 'idx_alumni_status');
            $table->index(['status_create', 'updated_at'], 'idx_alumni_status_updated');
            $table->index(['status_create', 'id_jurusan'], 'idx_alumni_status_jurusan');
            $table->index('tahun_lulus', 'idx_alumni_tahun_lulus');
        });

        // riwayat_status - optimasi join & filter
        Schema::table('riwayat_status', function (Blueprint $table) {
            $table->index(['id_alumni', 'id_riwayat'], 'idx_riwayat_alumni_id');
            $table->index('approval_status', 'idx_riwayat_approval');
            $table->index(['id_status', 'approval_status'], 'idx_riwayat_status_approval');
        });

        // lowongan - optimasi filter & approval
        Schema::table('lowongan', function (Blueprint $table) {
            $table->index(['approval_status', 'status'], 'idx_lowongan_approval_status');
            $table->index(['status', 'approval_status', 'created_at'], 'idx_lowongan_published');
        });

        // lowongan_skills - optimasi skill matching join
        if (Schema::hasTable('lowongan_skills')) {
            Schema::table('lowongan_skills', function (Blueprint $table) {
                $table->index('id_skills', 'idx_lowongan_skills_skill');
            });
        }

        // kuesioner - optimasi filter published
        Schema::table('kuesioner', function (Blueprint $table) {
            $table->index(['status', 'tanggal_publikasi'], 'idx_kuesioner_active');
        });

        // pekerjaan - optimasi company joins
        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->index('id_perusahaan', 'idx_pekerjaan_perusahaan');
        });
    }

    public function down(): void
    {
        Schema::table('alumni', function (Blueprint $table) {
            $table->dropIndex('idx_alumni_status');
            $table->dropIndex('idx_alumni_status_updated');
            $table->dropIndex('idx_alumni_status_jurusan');
            $table->dropIndex('idx_alumni_tahun_lulus');
        });

        Schema::table('riwayat_status', function (Blueprint $table) {
            $table->dropIndex('idx_riwayat_alumni_id');
            $table->dropIndex('idx_riwayat_approval');
            $table->dropIndex('idx_riwayat_status_approval');
        });

        Schema::table('lowongan', function (Blueprint $table) {
            $table->dropIndex('idx_lowongan_approval_status');
            $table->dropIndex('idx_lowongan_published');
        });

        if (Schema::hasTable('lowongan_skills')) {
            Schema::table('lowongan_skills', function (Blueprint $table) {
                $table->dropIndex('idx_lowongan_skills_skill');
            });
        }

        Schema::table('kuesioner', function (Blueprint $table) {
            $table->dropIndex('idx_kuesioner_active');
        });

        Schema::table('pekerjaan', function (Blueprint $table) {
            $table->dropIndex('idx_pekerjaan_perusahaan');
        });
    }
};
