<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add landing page hero content fields to pengaturan_tampilan.
     */
    public function up(): void
    {
        Schema::table('pengaturan_tampilan', function (Blueprint $table) {
            $table->string('landing_title', 500)->nullable()->after('landing_bg');
            $table->text('landing_description')->nullable()->after('landing_title');
        });
    }

    public function down(): void
    {
        Schema::table('pengaturan_tampilan', function (Blueprint $table) {
            $table->dropColumn(['landing_title', 'landing_description']);
        });
    }
};
