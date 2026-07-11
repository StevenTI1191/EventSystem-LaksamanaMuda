<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            // ID user Office (mis. "u-wandi") sebagai jembatan stabil SSO.
            // Dipakai untuk auto-sync: 1 user Office <-> 1 pegawai.
            $table->string('office_uid')->nullable()->unique()->after('akses_menu');
        });
    }

    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropUnique(['office_uid']);
            $table->dropColumn('office_uid');
        });
    }
};
