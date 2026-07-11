<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akses modul PER USER (override role). NULL = pakai modul dari role.
 * Diisi lewat halaman "Akses Modul" di modul Manajemen (checkbox).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_users', function (Blueprint $table) {
            $table->json('modules')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('office_users', function (Blueprint $table) {
            $table->dropColumn('modules');
        });
    }
};
