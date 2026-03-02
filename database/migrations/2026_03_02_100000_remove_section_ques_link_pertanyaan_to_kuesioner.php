<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove section_ques table.
     * Link pertanyaan directly to kuesioner (one-to-many).
     */
    public function up(): void
    {
        // Step 1: Add id_kuesioner to pertanyaan
        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_kuesioner')->nullable()->after('id_pertanyaan');
        });

        // Step 2: Migrate data — copy id_kuesioner from section_ques to pertanyaan
        DB::statement('
            UPDATE pertanyaan p
            INNER JOIN section_ques sq ON p.id_sectionques = sq.id_sectionques
            SET p.id_kuesioner = sq.id_kuesioner
        ');

        // Step 3: Drop foreign key & column id_sectionques from pertanyaan
        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->dropForeign(['id_sectionques']);
            $table->dropColumn('id_sectionques');
        });

        // Step 4: Add foreign key for id_kuesioner on pertanyaan
        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->foreign('id_kuesioner')
                  ->references('id_kuesioner')
                  ->on('kuesioner')
                  ->onDelete('cascade');

            $table->index('id_kuesioner');
        });

        // Step 5: Drop section_ques table
        Schema::dropIfExists('section_ques');

        // Step 6: Drop performance indexes on section_ques if they exist
        // (already dropped with the table)
    }

    /**
     * Reverse: recreate section_ques and relink pertanyaan.
     */
    public function down(): void
    {
        // Recreate section_ques table
        Schema::create('section_ques', function (Blueprint $table) {
            $table->id('id_sectionques');
            $table->unsignedBigInteger('id_kuesioner');
            $table->string('judul_pertanyaan');
            $table->timestamps();

            $table->foreign('id_kuesioner')
                  ->references('id_kuesioner')
                  ->on('kuesioner')
                  ->onDelete('cascade');

            $table->index('id_kuesioner');
        });

        // Add id_sectionques back to pertanyaan
        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sectionques')->nullable()->after('id_pertanyaan');
        });

        // Create a default section for each kuesioner and link pertanyaan back
        $kuesionerIds = DB::table('pertanyaan')->distinct()->pluck('id_kuesioner');
        foreach ($kuesionerIds as $kuesionerId) {
            $sectionId = DB::table('section_ques')->insertGetId([
                'id_kuesioner' => $kuesionerId,
                'judul_pertanyaan' => 'Umum',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('pertanyaan')
                ->where('id_kuesioner', $kuesionerId)
                ->update(['id_sectionques' => $sectionId]);
        }

        // Add FK for id_sectionques
        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->foreign('id_sectionques')
                  ->references('id_sectionques')
                  ->on('section_ques')
                  ->onDelete('cascade');
        });

        // Remove id_kuesioner from pertanyaan
        Schema::table('pertanyaan', function (Blueprint $table) {
            $table->dropForeign(['id_kuesioner']);
            $table->dropIndex(['id_kuesioner']);
            $table->dropColumn('id_kuesioner');
        });
    }
};
