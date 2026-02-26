<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Restructure kuesioner tables according to new ERD diagram:
     * - Add section_ques table
     * - Modify kuesioner to use id_status FK
     * - Modify pertanyaan to link to section_ques instead of kuesioner
     * - Rename columns to match ERD naming
     */
    public function up(): void
    {
        // Step 1: Drop existing foreign keys and indexes
        Schema::table('jawaban_kuesioner', function (Blueprint $table) {
            $table->dropForeign(['id_pertanyaan']);
            $table->dropForeign(['id_opsiJawaban']);
        });

        Schema::table('opsi_jawaban', function (Blueprint $table) {
            $table->dropForeign(['id_pertanyaan']);
        });

        Schema::table('pertanyaan_kuesioner', function (Blueprint $table) {
            $table->dropForeign(['id_kuesioner']);
        });

        // Step 2: Create section_ques table
        Schema::create('section_ques', function (Blueprint $table) {
            $table->id('id_sectionques');
            $table->unsignedBigInteger('id_kuesioner');
            $table->string('judul_pertanyaan');
            $table->timestamps();
            
            $table->foreign('id_kuesioner')
                  ->references('id_kuesioner')
                  ->on('kuesioner')
                  ->onDelete('cascade');
        });

        // Step 3: Modify kuesioner table
        Schema::table('kuesioner', function (Blueprint $table) {
            // Add id_status column
            $table->unsignedBigInteger('id_status')->nullable()->after('id_kuesioner');
            
            // Add foreign key to status table
            $table->foreign('id_status')
                  ->references('id_status')
                  ->on('status')
                  ->onDelete('set null');
        });

        // Step 4: Rename pertanyaan_kuesioner table and modify structure
        Schema::rename('pertanyaan_kuesioner', 'pertanyaan');

        Schema::table('pertanyaan', function (Blueprint $table) {
            // Drop extra columns if they exist
            if (Schema::hasColumn('pertanyaan', 'tipe_pertanyaan')) {
                $table->dropColumn('tipe_pertanyaan');
            }
            if (Schema::hasColumn('pertanyaan', 'status_pertanyaan')) {
                $table->dropColumn('status_pertanyaan');
            }
            if (Schema::hasColumn('pertanyaan', 'kategori')) {
                $table->dropColumn('kategori');
            }
            if (Schema::hasColumn('pertanyaan', 'judul_bagian')) {
                $table->dropColumn('judul_bagian');
            }
            if (Schema::hasColumn('pertanyaan', 'urutan')) {
                $table->dropColumn('urutan');
            }
            
            // Rename primary key
            $table->renameColumn('id_pertanyaanKuis', 'id_pertanyaan');
            
            // Rename pertanyaan to isi_pertanyaan
            $table->renameColumn('pertanyaan', 'isi_pertanyaan');
            
            // Drop old id_kuesioner column
            $table->dropColumn('id_kuesioner');
        });

        Schema::table('pertanyaan', function (Blueprint $table) {
            // Add id_sectionques column
            $table->unsignedBigInteger('id_sectionques')->after('id_pertanyaan');
            
            // Add status_pertanyaan column
            $table->enum('status_pertanyaan', ['publish', 'draft', 'hidden'])->default('draft')->after('isi_pertanyaan');
            
            // Add foreign key to section_ques
            $table->foreign('id_sectionques')
                  ->references('id_sectionques')
                  ->on('section_ques')
                  ->onDelete('cascade');
        });

        // Step 5: Rename jawaban_kuesioner table
        Schema::rename('jawaban_kuesioner', 'jawaban');

        Schema::table('jawaban', function (Blueprint $table) {
            // Rename primary key
            $table->renameColumn('id_jawabanKuis', 'id_jawaban');
        });

        // Step 6: Update opsi_jawaban and jawaban foreign keys
        Schema::table('opsi_jawaban', function (Blueprint $table) {
            $table->foreign('id_pertanyaan')
                  ->references('id_pertanyaan')
                  ->on('pertanyaan')
                  ->onDelete('cascade');
        });

        Schema::table('jawaban', function (Blueprint $table) {
            $table->foreign('id_pertanyaan')
                  ->references('id_pertanyaan')
                  ->on('pertanyaan')
                  ->onDelete('cascade');
            
            $table->foreign('id_opsiJawaban')
                  ->references('id_opsi')
                  ->on('opsi_jawaban')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys
        Schema::table('jawaban', function (Blueprint $table) {
            $table->dropForeign(['id_pertanyaan']);
            $table->dropForeign(['id_opsiJawaban']);
        });

        Schema::table('opsi_jawaban', function (Blueprint $table) {
            $table->dropForeign(['id_pertanyaan']);
        });

        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->dropForeign(['id_sectionques']);
        });

        // Restore jawaban_kuesioner table
        Schema::rename('jawaban', 'jawaban_kuesioner');
        Schema::table('jawaban_kuesioner', function (Blueprint $table) {
            $table->renameColumn('id_jawaban', 'id_jawabanKuis');
        });

        // Restore pertanyaan structure
        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->dropColumn(['id_sectionques', 'status_pertanyaan']);
        });

        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kuesioner')->after('id_pertanyaan');
            $table->renameColumn('isi_pertanyaan', 'pertanyaan');
            $table->renameColumn('id_pertanyaan', 'id_pertanyaanKuis');
            
            $table->foreign('id_kuesioner')
                  ->references('id_kuesioner')
                  ->on('kuesioner')
                  ->onDelete('cascade');
        });

        Schema::rename('pertanyaan', 'pertanyaan_kuesioner');

        // Restore kuesioner table
        Schema::table('kuesioner', function (Blueprint $table) {
            $table->dropForeign(['id_status']);
            $table->dropColumn('id_status');
        });

        // Drop section_ques table
        Schema::dropIfExists('section_ques');

        // Restore foreign keys
        Schema::table('opsi_jawaban', function (Blueprint $table) {
            $table->foreign('id_pertanyaan')
                  ->references('id_pertanyaanKuis')
                  ->on('pertanyaan_kuesioner')
                  ->onDelete('cascade');
        });

        Schema::table('jawaban_kuesioner', function (Blueprint $table) {
            $table->foreign('id_pertanyaan')
                  ->references('id_pertanyaanKuis')
                  ->on('pertanyaan_kuesioner')
                  ->onDelete('cascade');
            
            $table->foreign('id_opsiJawaban')
                  ->references('id_opsi')
                  ->on('opsi_jawaban')
                  ->onDelete('set null');
        });
    }
};
