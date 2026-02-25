<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add code column to provinsi if not exists
        if (!Schema::hasColumn('provinsi', 'code')) {
            Schema::table('provinsi', function (Blueprint $table) {
                $table->string('code')->unique()->after('nama_provinsi');
            });
        }

        // Add code column to kota if not exists
        if (!Schema::hasColumn('kota', 'code')) {
            Schema::table('kota', function (Blueprint $table) {
                $table->string('code')->unique()->after('id_provinsi');
            });
        }
    }

    public function down(): void
    {
        Schema::table('provinsi', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });

        Schema::table('kota', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
