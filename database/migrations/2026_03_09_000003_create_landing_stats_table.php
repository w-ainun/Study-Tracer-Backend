<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_stats', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., 'total_alumni', 'active_workers_percentage', 'partner_companies'
            $table->string('value');
            $table->string('label')->nullable();
            $table->timestamps();
        });

        // Seed default values
        DB::table('landing_stats')->insert([
            ['key' => 'total_alumni', 'value' => '15000', 'label' => 'Alumni Aktif', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'employment_rate', 'value' => '92', 'label' => 'Tingkat Pekerjaan (%)', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'partner_companies', 'value' => '100', 'label' => 'Perusahaan Mitra', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'active_workers_percentage', 'value' => '85', 'label' => 'Alumni Aktif Bekerja (%)', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_stats');
    }
};
