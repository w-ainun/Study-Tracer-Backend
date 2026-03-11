<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('verification','lowongan','career_status','kuesioner','system','profile_update') NOT NULL DEFAULT 'system'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN type ENUM('verification','lowongan','career_status','kuesioner','system') NOT NULL DEFAULT 'system'");
    }
};
