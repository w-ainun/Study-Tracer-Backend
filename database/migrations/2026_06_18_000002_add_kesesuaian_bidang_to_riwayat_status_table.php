<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_status', function (Blueprint $table) {
            $table->boolean('is_sesuai_bidang')->nullable()->after('approval_status');
            $table->index('is_sesuai_bidang');
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_status', function (Blueprint $table) {
            $table->dropIndex(['is_sesuai_bidang']);
            $table->dropColumn('is_sesuai_bidang');
        });
    }
};
