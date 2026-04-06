<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add logo & alamat columns to universitas and perusahaan tables
     * for the Kemitraan (Partnership) admin module.
     */
    public function up(): void
    {
        // ── Universitas: add alamat + logo ──────────────────────
        Schema::table('universitas', function (Blueprint $table) {
            $table->string('alamat', 500)->nullable()->after('nama_universitas');
            $table->string('logo')->nullable()->after('alamat');
        });

        // ── Perusahaan: add logo, make id_kota & jalan nullable ─
        Schema::table('perusahaan', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('jalan');
        });

        // Make id_kota nullable so Kemitraan can create without city FK
        if (Schema::hasColumn('perusahaan', 'id_kota')) {
            Schema::table('perusahaan', function (Blueprint $table) {
                $table->unsignedBigInteger('id_kota')->nullable()->change();
            });
        }

        // Make jalan nullable
        if (Schema::hasColumn('perusahaan', 'jalan')) {
            Schema::table('perusahaan', function (Blueprint $table) {
                $table->string('jalan')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('universitas', function (Blueprint $table) {
            $table->dropColumn(['alamat', 'logo']);
        });

        Schema::table('perusahaan', function (Blueprint $table) {
            $table->dropColumn('logo');
        });

        // Revert nullable changes
        if (Schema::hasColumn('perusahaan', 'id_kota')) {
            Schema::table('perusahaan', function (Blueprint $table) {
                $table->unsignedBigInteger('id_kota')->nullable(false)->change();
            });
        }

        if (Schema::hasColumn('perusahaan', 'jalan')) {
            Schema::table('perusahaan', function (Blueprint $table) {
                $table->string('jalan')->nullable(false)->change();
            });
        }
    }
};
