<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    /**
     * Helper: check if an index already exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'idx_users_role')) {
                // Alasan: Dashboard stats filter by role, middleware check role setiap request
                $table->index('role', 'idx_users_role');
            }
        });

        Schema::table('alumni', function (Blueprint $table) {
            // Composite: filter status + sort by created_at (pending users listing)
            if (!$this->indexExists('alumni', 'idx_alumni_status_create_created')) {
                $table->index(['status_create', 'created_at'], 'idx_alumni_status_create_created');
            }

            // Composite: filter by status + jurusan (dashboard alumni per jurusan)
            if (!$this->indexExists('alumni', 'idx_alumni_status_jurusan_v2')) {
                $table->index(['status_create', 'id_jurusan'], 'idx_alumni_status_jurusan_v2');
            }

            // Nama search — prefix index untuk LIKE 'search%' (B-Tree left-prefix)
            // Juga membantu ORDER BY nama_alumni
            if (!$this->indexExists('alumni', 'idx_alumni_nama')) {
                $table->index('nama_alumni', 'idx_alumni_nama');
            }

            // NIS search — sering dicari di admin search
            if (!$this->indexExists('alumni', 'idx_alumni_nis')) {
                $table->index('nis', 'idx_alumni_nis');
            }

            // NISN search — sering dicari di admin search
            if (!$this->indexExists('alumni', 'idx_alumni_nisn')) {
                $table->index('nisn', 'idx_alumni_nisn');
            }

            // tahun_masuk — dipakai di dashboard angkatan grouping
            if (!$this->indexExists('alumni', 'idx_alumni_tahun_masuk')) {
                $table->index('tahun_masuk', 'idx_alumni_tahun_masuk');
            }
        });

        Schema::table('riwayat_status', function (Blueprint $table) {
            // Composite: approval_status + created_at untuk pending career updates listing
            if (!$this->indexExists('riwayat_status', 'idx_riwayat_approval_created')) {
                $table->index(['approval_status', 'created_at'], 'idx_riwayat_approval_created');
            }

            // Composite: id_alumni + approval_status + id_riwayat
            // Dipakai di getPendingCareerUpdates() → cari previous approved riwayat
            if (!$this->indexExists('riwayat_status', 'idx_riwayat_alumni_approval_id')) {
                $table->index(['id_alumni', 'approval_status', 'id_riwayat'], 'idx_riwayat_alumni_approval_id');
            }

            // Composite: id_status + tahun_selesai untuk distribution queries
            // (sudah ada di migration sebelumnya, tapi cek untuk safety)
            if (!$this->indexExists('riwayat_status', 'idx_riwayat_status_selesai')) {
                $table->index(['id_status', 'tahun_selesai'], 'idx_riwayat_status_selesai');
            }

            // created_at DESC — untuk sorting pending updates
            if (!$this->indexExists('riwayat_status', 'idx_riwayat_created')) {
                $table->index('created_at', 'idx_riwayat_created');
            }
        });

        Schema::table('lowongan', function (Blueprint $table) {
            // FK: id_perusahaan (JOIN perusahaan)
            if (!$this->indexExists('lowongan', 'idx_lowongan_perusahaan')) {
                $table->index('id_perusahaan', 'idx_lowongan_perusahaan');
            }

            // FK: id_pekerjaan (JOIN pekerjaan)
            if (!$this->indexExists('lowongan', 'idx_lowongan_pekerjaan')) {
                $table->index('id_pekerjaan', 'idx_lowongan_pekerjaan');
            }

            // FK: id_users (JOIN users — who posted)
            if (!$this->indexExists('lowongan', 'idx_lowongan_users')) {
                $table->index('id_users', 'idx_lowongan_users');
            }

            // tipe_pekerjaan — dipakai di getLowonganStats() GROUP BY
            if (!$this->indexExists('lowongan', 'idx_lowongan_tipe')) {
                $table->index('tipe_pekerjaan', 'idx_lowongan_tipe');
            }

            // lowongan_selesai — closeExpiredLowongan() WHERE < today
            if (!$this->indexExists('lowongan', 'idx_lowongan_selesai')) {
                $table->index('lowongan_selesai', 'idx_lowongan_selesai');
            }

            // Composite: lowongan_selesai + status untuk auto-close expired
            if (!$this->indexExists('lowongan', 'idx_lowongan_selesai_status')) {
                $table->index(['lowongan_selesai', 'status'], 'idx_lowongan_selesai_status');
            }

            // created_at untuk ORDER BY sorting
            if (!$this->indexExists('lowongan', 'idx_lowongan_created')) {
                $table->index('created_at', 'idx_lowongan_created');
            }
        });

        if (Schema::hasTable('lowongan_skills')) {
            Schema::table('lowongan_skills', function (Blueprint $table) {
                // FK reverse: lookup by id_lowongan (covered by UNIQUE)
                // FK reverse: lookup by id_skills (untuk skill matching query)
                if (!$this->indexExists('lowongan_skills', 'idx_lowskilss_lowongan')) {
                    $table->index('id_lowongan', 'idx_lowskilss_lowongan');
                }
            });
        }

        Schema::table('pekerjaan', function (Blueprint $table) {
            // Composite: id_riwayat + id_perusahaan — covering index for geo distribution
            if (!$this->indexExists('pekerjaan', 'idx_pekerjaan_riwayat_perusahaan')) {
                $table->index(['id_riwayat', 'id_perusahaan'], 'idx_pekerjaan_riwayat_perusahaan');
            }
        });

       
        Schema::table('perusahaan', function (Blueprint $table) {
            // nama_perusahaan search + ORDER BY
            if (!$this->indexExists('perusahaan', 'idx_perusahaan_nama')) {
                $table->index('nama_perusahaan', 'idx_perusahaan_nama');
            }
        });

        Schema::table('kuesioner', function (Blueprint $table) {
            // FK: id_status
            if (!$this->indexExists('kuesioner', 'idx_kuesioner_status_fk')) {
                $table->index('id_status', 'idx_kuesioner_status_fk');
            }

            // status enum — sering dipakai di WHERE filter
            if (!$this->indexExists('kuesioner', 'idx_kuesioner_status_enum')) {
                $table->index('status', 'idx_kuesioner_status_enum');
            }

            // created_at — ORDER BY di admin list
            if (!$this->indexExists('kuesioner', 'idx_kuesioner_created')) {
                $table->index('created_at', 'idx_kuesioner_created');
            }

            // tanggal_publikasi — ORDER BY di published list + WHERE NOT NULL
            if (!$this->indexExists('kuesioner', 'idx_kuesioner_publikasi')) {
                $table->index('tanggal_publikasi', 'idx_kuesioner_publikasi');
            }

            // Composite: status + id_status + tanggal_publikasi
            // Covering index untuk getPublishedByStatus()
            if (!$this->indexExists('kuesioner', 'idx_kuesioner_status_id_pub')) {
                $table->index(['status', 'id_status', 'tanggal_publikasi'], 'idx_kuesioner_status_id_pub');
            }
        });

        Schema::table('pertanyaan', function (Blueprint $table) {
            // created_at — ORDER BY di pertanyaan list
            if (!$this->indexExists('pertanyaan', 'idx_pertanyaan_created')) {
                $table->index('created_at', 'idx_pertanyaan_created');
            }
        });

    
        Schema::table('jawaban', function (Blueprint $table) {
            // FK: id_opsiJawaban — JOIN opsi_jawaban untuk statistics
            if (!$this->indexExists('jawaban', 'idx_jawaban_opsi')) {
                $table->index('id_opsiJawaban', 'idx_jawaban_opsi');
            }

            // Composite: id_pertanyaan + id_opsiJawaban — statistics count per opsi
            if (!$this->indexExists('jawaban', 'idx_jawaban_pertanyaan_opsi')) {
                $table->index(['id_pertanyaan', 'id_opsiJawaban'], 'idx_jawaban_pertanyaan_opsi');
            }

            // Composite: id_pertanyaan + id_user — covering index for aggregation
            // (mungkin sudah ada, cek dulu)
            if (!$this->indexExists('jawaban', 'idx_jawaban_pertanyaan_user')) {
                $table->index(['id_pertanyaan', 'id_user'], 'idx_jawaban_pertanyaan_user');
            }

            // status — filter by selesai/belum
            if (!$this->indexExists('jawaban', 'idx_jawaban_status')) {
                $table->index('status', 'idx_jawaban_status');
            }

            // created_at — MAX(created_at) in statistics
            if (!$this->indexExists('jawaban', 'idx_jawaban_created')) {
                $table->index('created_at', 'idx_jawaban_created');
            }
        });

       
        if (Schema::hasTable('kuliah')) {
            Schema::table('kuliah', function (Blueprint $table) {
                // FK: id_universitas
                if (!$this->indexExists('kuliah', 'idx_kuliah_universitas')) {
                    $table->index('id_universitas', 'idx_kuliah_universitas');
                }

                // FK: id_jurusanKuliah
                if (!$this->indexExists('kuliah', 'idx_kuliah_jurusan_kuliah')) {
                    $table->index('id_jurusanKuliah', 'idx_kuliah_jurusan_kuliah');
                }
            });
        }

       
        Schema::table('wirausaha', function (Blueprint $table) {
            // FK: id_bidang — JOIN bidang_usaha
            if (!$this->indexExists('wirausaha', 'idx_wirausaha_bidang')) {
                $table->index('id_bidang', 'idx_wirausaha_bidang');
            }
        });

        Schema::table('jurusan_kuliah', function (Blueprint $table) {
            // FK: id_universitas (mungkin sudah ada dari constraint, tapi buat explicit)
            if (!$this->indexExists('jurusan_kuliah', 'idx_jurusan_kuliah_univ')) {
                $table->index('id_universitas', 'idx_jurusan_kuliah_univ');
            }

            // nama_jurusan — ORDER BY
            if (!$this->indexExists('jurusan_kuliah', 'idx_jurusan_kuliah_nama')) {
                $table->index('nama_jurusan', 'idx_jurusan_kuliah_nama');
            }
        });

        
        Schema::table('simpan_lowongan', function (Blueprint $table) {
            // FK: id_user saja (composite sudah ada)
            if (!$this->indexExists('simpan_lowongan', 'idx_simpan_user')) {
                $table->index('id_user', 'idx_simpan_user');
            }

            // FK: id_lowongan saja
            if (!$this->indexExists('simpan_lowongan', 'idx_simpan_lowongan')) {
                $table->index('id_lowongan', 'idx_simpan_lowongan');
            }
        });

      
        Schema::table('alumni_skills', function (Blueprint $table) {
            // FK: id_alumni
            if (!$this->indexExists('alumni_skills', 'idx_alumni_skills_alumni')) {
                $table->index('id_alumni', 'idx_alumni_skills_alumni');
            }

            // FK: id_skills
            if (!$this->indexExists('alumni_skills', 'idx_alumni_skills_skill')) {
                $table->index('id_skills', 'idx_alumni_skills_skill');
            }

            // Composite unique: prevent duplicates + covering index
            if (!$this->indexExists('alumni_skills', 'idx_alumni_skills_composite')) {
                $table->unique(['id_alumni', 'id_skills'], 'idx_alumni_skills_composite');
            }
        });

        
        Schema::table('alumni_social_media', function (Blueprint $table) {
            // FK: id_alumni
            if (!$this->indexExists('alumni_social_media', 'idx_alumni_sosmed_alumni')) {
                $table->index('id_alumni', 'idx_alumni_sosmed_alumni');
            }

            // FK: id_sosmed
            if (!$this->indexExists('alumni_social_media', 'idx_alumni_sosmed_sosmed')) {
                $table->index('id_sosmed', 'idx_alumni_sosmed_sosmed');
            }
        });

    
        if (Schema::hasTable('pending_profile_updates')) {
            Schema::table('pending_profile_updates', function (Blueprint $table) {
                // section — filter by section type
                if (!$this->indexExists('pending_profile_updates', 'idx_ppu_section')) {
                    $table->index('section', 'idx_ppu_section');
                }

                // Composite: status + section + created_at (filter by pending + type)
                if (!$this->indexExists('pending_profile_updates', 'idx_ppu_status_section_created')) {
                    $table->index(['status', 'section', 'created_at'], 'idx_ppu_status_section_created');
                }

                // FK: reviewed_by
                if (!$this->indexExists('pending_profile_updates', 'idx_ppu_reviewed_by')) {
                    $table->index('reviewed_by', 'idx_ppu_reviewed_by');
                }
            });
        }

    
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                // type — filter notifications by category
                if (!$this->indexExists('notifications', 'idx_notif_type')) {
                    $table->index('type', 'idx_notif_type');
                }

                // Composite: id_users + type — user notifications by type
                if (!$this->indexExists('notifications', 'idx_notif_user_type')) {
                    $table->index(['id_users', 'type'], 'idx_notif_user_type');
                }
            });
        }

        Schema::table('kota', function (Blueprint $table) {
            // nama_kota — ORDER BY
            if (!$this->indexExists('kota', 'idx_kota_nama')) {
                $table->index('nama_kota', 'idx_kota_nama');
            }
        });
    }

    public function down(): void
    {
        $dropIndex = function (string $table, string $indexName) {
            if (Schema::hasTable($table) && $this->indexExists($table, $indexName)) {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        };

        // 1. Users
        $dropIndex('users', 'idx_users_role');

        // 2. Alumni
        $dropIndex('alumni', 'idx_alumni_status_create_created');
        $dropIndex('alumni', 'idx_alumni_status_jurusan_v2');
        $dropIndex('alumni', 'idx_alumni_nama');
        $dropIndex('alumni', 'idx_alumni_nis');
        $dropIndex('alumni', 'idx_alumni_nisn');
        $dropIndex('alumni', 'idx_alumni_tahun_masuk');

        // 3. Riwayat Status
        $dropIndex('riwayat_status', 'idx_riwayat_approval_created');
        $dropIndex('riwayat_status', 'idx_riwayat_alumni_approval_id');
        $dropIndex('riwayat_status', 'idx_riwayat_status_selesai');
        $dropIndex('riwayat_status', 'idx_riwayat_created');

        // 4. Lowongan
        $dropIndex('lowongan', 'idx_lowongan_perusahaan');
        $dropIndex('lowongan', 'idx_lowongan_pekerjaan');
        $dropIndex('lowongan', 'idx_lowongan_users');
        $dropIndex('lowongan', 'idx_lowongan_tipe');
        $dropIndex('lowongan', 'idx_lowongan_selesai');
        $dropIndex('lowongan', 'idx_lowongan_selesai_status');
        $dropIndex('lowongan', 'idx_lowongan_created');

        // 5. Lowongan Skills
        $dropIndex('lowongan_skills', 'idx_lowskilss_lowongan');

        // 6. Pekerjaan
        $dropIndex('pekerjaan', 'idx_pekerjaan_riwayat_perusahaan');

        // 7. Perusahaan
        $dropIndex('perusahaan', 'idx_perusahaan_nama');

        // 8. Kuesioner
        $dropIndex('kuesioner', 'idx_kuesioner_status_fk');
        $dropIndex('kuesioner', 'idx_kuesioner_status_enum');
        $dropIndex('kuesioner', 'idx_kuesioner_created');
        $dropIndex('kuesioner', 'idx_kuesioner_publikasi');
        $dropIndex('kuesioner', 'idx_kuesioner_status_id_pub');

        // 9. Pertanyaan
        $dropIndex('pertanyaan', 'idx_pertanyaan_created');

        // 11. Jawaban
        $dropIndex('jawaban', 'idx_jawaban_opsi');
        $dropIndex('jawaban', 'idx_jawaban_pertanyaan_opsi');
        $dropIndex('jawaban', 'idx_jawaban_pertanyaan_user');
        $dropIndex('jawaban', 'idx_jawaban_status');
        $dropIndex('jawaban', 'idx_jawaban_created');

        // 12. Kuliah
        $dropIndex('kuliah', 'idx_kuliah_universitas');
        $dropIndex('kuliah', 'idx_kuliah_jurusan_kuliah');

        // 13. Wirausaha
        $dropIndex('wirausaha', 'idx_wirausaha_bidang');

        // 14. Jurusan Kuliah
        $dropIndex('jurusan_kuliah', 'idx_jurusan_kuliah_univ');
        $dropIndex('jurusan_kuliah', 'idx_jurusan_kuliah_nama');

        // 15. Simpan Lowongan
        $dropIndex('simpan_lowongan', 'idx_simpan_user');
        $dropIndex('simpan_lowongan', 'idx_simpan_lowongan');

        // 16. Alumni Skills
        $dropIndex('alumni_skills', 'idx_alumni_skills_alumni');
        $dropIndex('alumni_skills', 'idx_alumni_skills_skill');
        if (Schema::hasTable('alumni_skills') && $this->indexExists('alumni_skills', 'idx_alumni_skills_composite')) {
            Schema::table('alumni_skills', function (Blueprint $table) {
                $table->dropUnique('idx_alumni_skills_composite');
            });
        }

        // 17. Alumni Social Media
        $dropIndex('alumni_social_media', 'idx_alumni_sosmed_alumni');
        $dropIndex('alumni_social_media', 'idx_alumni_sosmed_sosmed');

        // 18. Pending Profile Updates
        $dropIndex('pending_profile_updates', 'idx_ppu_section');
        $dropIndex('pending_profile_updates', 'idx_ppu_status_section_created');
        $dropIndex('pending_profile_updates', 'idx_ppu_reviewed_by');

        // 19. Notifications
        $dropIndex('notifications', 'idx_notif_type');
        $dropIndex('notifications', 'idx_notif_user_type');

        // 22. Kota
        $dropIndex('kota', 'idx_kota_nama');
    }
};
