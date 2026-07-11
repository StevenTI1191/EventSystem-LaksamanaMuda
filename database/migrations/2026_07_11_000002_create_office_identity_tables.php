<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Level 2 — Identity Layer di MySQL (pengganti GSheet Users/Roles).
 * Dibangun paralel; tidak mengganggu sistem yang berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Daftar user Office (semua modul: ordering, konten, backstage, dll)
        Schema::create('office_users', function (Blueprint $table) {
            $table->string('id')->primary();          // mis. "u-hengky"
            $table->string('name')->unique();         // login pakai nama + PIN
            $table->text('pin');                      // TERENKRIPSI (cast encrypted)
            $table->string('role')->nullable();       // mis. superadmin, ordering, eventmarketing
            $table->boolean('active')->default(true);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        // Peta role -> modul yang boleh dibuka (mirror tab Roles GSheet)
        Schema::create('office_roles', function (Blueprint $table) {
            $table->string('role')->primary();        // mis. ordering, marketing
            $table->string('label')->nullable();
            $table->json('modules')->nullable();      // ["ordering","akademi"] atau ["*"]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_users');
        Schema::dropIfExists('office_roles');
    }
};
