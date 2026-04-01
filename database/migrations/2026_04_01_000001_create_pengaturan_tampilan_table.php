<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengaturan_tampilan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_sekolah', 255)->default('SMK Negeri 1 Gondang');
            $table->string('logo', 500)->nullable();
            $table->string('login_bg', 500)->nullable();
            $table->string('primary_color', 7)->default('#3C5759');
            $table->string('secondary_color', 7)->default('#F3F4F4');
            $table->string('third_color', 7)->default('#9CA3AF');
            $table->timestamps();
        });

        // Seed default row (singleton pattern)
        DB::table('pengaturan_tampilan')->insert([
            'nama_sekolah'    => 'SMK Negeri 1 Gondang',
            'primary_color'   => '#3C5759',
            'secondary_color' => '#F3F4F4',
            'third_color'     => '#9CA3AF',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengaturan_tampilan');
    }
};
