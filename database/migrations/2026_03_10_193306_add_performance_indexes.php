<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan indexes untuk optimasi query performance berdasarkan analisis slow queries.
     */
    public function up(): void
    {
        // Helper function to check if index exists
        $indexExists = function ($table, $indexName) {
            $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        };

        // Optimize kuesioner queries (filtered by status + tanggal)
        if (Schema::hasTable('kuesioner')) {
            Schema::table('kuesioner', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('kuesioner', 'idx_kuesioner_status_publikasi')) {
                    $table->index(['status', 'tanggal_publikasi'], 'idx_kuesioner_status_publikasi');
                }
                if (!$indexExists('kuesioner', 'idx_kuesioner_status_karir')) {
                    $table->index(['id_status', 'status'], 'idx_kuesioner_status_karir');
                }
            });
        }

        // Optimize pertanyaan queries (filtered by kuesioner)
        if (Schema::hasTable('pertanyaan')) {
            Schema::table('pertanyaan', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('pertanyaan', 'idx_pertanyaan_kuesioner_id')) {
                    $table->index('id_kuesioner', 'idx_pertanyaan_kuesioner_id');
                }
            });
        }

        // Optimize jawaban queries (filtered by user + pertanyaan)
        if (Schema::hasTable('jawaban')) {
            Schema::table('jawaban', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('jawaban', 'idx_jawaban_user_pertanyaan')) {
                    $table->index(['id_user', 'id_pertanyaan'], 'idx_jawaban_user_pertanyaan');
                }
            });
        }

        // Optimize alumni queries (filtered by status + updated_at for recent alumni)
        if (Schema::hasTable('alumni')) {
            Schema::table('alumni', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('alumni', 'idx_alumni_status_updated')) {
                    $table->index(['status_create', 'updated_at'], 'idx_alumni_status_updated');
                }
                if (!$indexExists('alumni', 'idx_alumni_user_id')) {
                    $table->index('id_users', 'idx_alumni_user_id');
                }
            });
        }

        // Optimize riwayat_status queries (filtered by alumni + approval status)
        if (Schema::hasTable('riwayat_status')) {
            Schema::table('riwayat_status', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('riwayat_status', 'idx_riwayat_alumni_approval')) {
                    $table->index(['id_alumni', 'approval_status'], 'idx_riwayat_alumni_approval');
                }
                if (!$indexExists('riwayat_status', 'idx_riwayat_status')) {
                    $table->index('id_status', 'idx_riwayat_status');
                }
            });
        }

        // Optimize lowongan queries (filtered by approval + status + created_at)
        if (Schema::hasTable('lowongan')) {
            Schema::table('lowongan', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('lowongan', 'idx_lowongan_approval_status_created')) {
                    $table->index(['approval_status', 'status', 'created_at'], 'idx_lowongan_approval_status_created');
                }
            });
        }

        // Optimize notifications queries (filtered by user + is_read)
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('notifications', 'idx_notifications_user_read_created')) {
                    $table->index(['id_users', 'is_read', 'created_at'], 'idx_notifications_user_read_created');
                }
            });
        }

        // Optimize pekerjaan queries (join with riwayat_status)
        if (Schema::hasTable('pekerjaan')) {
            Schema::table('pekerjaan', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('pekerjaan', 'idx_pekerjaan_riwayat')) {
                    $table->index('id_riwayat', 'idx_pekerjaan_riwayat');
                }
                if (!$indexExists('pekerjaan', 'idx_pekerjaan_perusahaan')) {
                    $table->index('id_perusahaan', 'idx_pekerjaan_perusahaan');
                }
            });
        }

        // Optimize kuliah queries (join with riwayat_status)
        if (Schema::hasTable('kuliah')) {
            Schema::table('kuliah', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('kuliah', 'idx_kuliah_riwayat')) {
                    $table->index('id_riwayat', 'idx_kuliah_riwayat');
                }
            });
        }

        // Optimize wirausaha queries (join with riwayat_status)
        if (Schema::hasTable('wirausaha')) {
            Schema::table('wirausaha', function (Blueprint $table) use ($indexExists) {
                if (!$indexExists('wirausaha', 'idx_wirausaha_riwayat')) {
                    $table->index('id_riwayat', 'idx_wirausaha_riwayat');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Helper function to check if index exists
        $indexExists = function ($table, $indexName) {
            $indexes = \DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return !empty($indexes);
        };

        if (Schema::hasTable('kuesioner')) {
            Schema::table('kuesioner', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('kuesioner', 'idx_kuesioner_status_publikasi')) {
                    $table->dropIndex('idx_kuesioner_status_publikasi');
                }
                if ($indexExists('kuesioner', 'idx_kuesioner_status_karir')) {
                    $table->dropIndex('idx_kuesioner_status_karir');
                }
            });
        }

        if (Schema::hasTable('pertanyaan')) {
            Schema::table('pertanyaan', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('pertanyaan', 'idx_pertanyaan_kuesioner_id')) {
                    $table->dropIndex('idx_pertanyaan_kuesioner_id');
                }
            });
        }

        if (Schema::hasTable('jawaban')) {
            Schema::table('jawaban', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('jawaban', 'idx_jawaban_user_pertanyaan')) {
                    $table->dropIndex('idx_jawaban_user_pertanyaan');
                }
            });
        }

        if (Schema::hasTable('alumni')) {
            Schema::table('alumni', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('alumni', 'idx_alumni_status_updated')) {
                    $table->dropIndex('idx_alumni_status_updated');
                }
                if ($indexExists('alumni', 'idx_alumni_user_id')) {
                    $table->dropIndex('idx_alumni_user_id');
                }
            });
        }

        if (Schema::hasTable('riwayat_status')) {
            Schema::table('riwayat_status', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('riwayat_status', 'idx_riwayat_alumni_approval')) {
                    $table->dropIndex('idx_riwayat_alumni_approval');
                }
                if ($indexExists('riwayat_status', 'idx_riwayat_status')) {
                    $table->dropIndex('idx_riwayat_status');
                }
            });
        }

        if (Schema::hasTable('lowongan')) {
            Schema::table('lowongan', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('lowongan', 'idx_lowongan_approval_status_created')) {
                    $table->dropIndex('idx_lowongan_approval_status_created');
                }
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('notifications', 'idx_notifications_user_read_created')) {
                    $table->dropIndex('idx_notifications_user_read_created');
                }
            });
        }

        if (Schema::hasTable('pekerjaan')) {
            Schema::table('pekerjaan', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('pekerjaan', 'idx_pekerjaan_riwayat')) {
                    $table->dropIndex('idx_pekerjaan_riwayat');
                }
                if ($indexExists('pekerjaan', 'idx_pekerjaan_perusahaan')) {
                    $table->dropIndex('idx_pekerjaan_perusahaan');
                }
            });
        }

        if (Schema::hasTable('kuliah')) {
            Schema::table('kuliah', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('kuliah', 'idx_kuliah_riwayat')) {
                    $table->dropIndex('idx_kuliah_riwayat');
                }
            });
        }

        if (Schema::hasTable('wirausaha')) {
            Schema::table('wirausaha', function (Blueprint $table) use ($indexExists) {
                if ($indexExists('wirausaha', 'idx_wirausaha_riwayat')) {
                    $table->dropIndex('idx_wirausaha_riwayat');
                }
            });
        }
    }
};
