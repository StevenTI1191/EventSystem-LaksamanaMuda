<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Peta role Office -> daftar modul yang boleh dibuka (mirror tab Roles GSheet).
 */
class OfficeRole extends Model
{
    protected $primaryKey = 'role';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['role', 'label', 'modules'];

    protected $casts = [
        'modules' => 'array',
    ];
}
