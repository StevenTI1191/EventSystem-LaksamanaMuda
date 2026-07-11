<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            // Daftar key menu sidebar yang boleh tampil untuk pegawai ini.
            // NULL = pakai default sesuai role (lihat config/aksesmenu.php).
            $table->json('akses_menu')->nullable()->after('note_pegawai');
        });
    }

    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropColumn('akses_menu');
        });
    }
};
