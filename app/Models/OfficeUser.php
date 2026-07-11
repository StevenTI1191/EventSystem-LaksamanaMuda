<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * User Office (identity layer Level 2). Login pakai nama + PIN.
 * PIN disimpan TERENKRIPSI (bisa didekripsi untuk ditampilkan ke admin).
 */
class OfficeUser extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'pin', 'role', 'modules', 'active', 'keterangan'];

    protected $casts = [
        'pin'     => 'encrypted',
        'modules' => 'array',
        'active'  => 'boolean',
    ];

    /**
     * Daftar key modul efektif yang boleh dibuka user ini.
     * Utamakan modul per-user (kolom modules); kalau kosong -> dari role.
     */
    public function resolvedModules(): array
    {
        if (is_array($this->modules) && count($this->modules) > 0) {
            return $this->modules;
        }
        $role = OfficeRole::find($this->role);
        return $role?->modules ?? [];
    }
}
