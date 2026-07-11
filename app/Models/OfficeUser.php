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

    protected $fillable = ['id', 'name', 'pin', 'role', 'active', 'keterangan'];

    protected $casts = [
        'pin'    => 'encrypted',
        'active' => 'boolean',
    ];

    /** Daftar key modul yang boleh dibuka user ini (dari role). */
    public function modules(): array
    {
        $role = OfficeRole::find($this->role);
        return $role?->modules ?? [];
    }
}
