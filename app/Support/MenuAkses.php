<?php

namespace App\Support;

/**
 * Resolusi akses menu sidebar per pegawai berdasarkan config/aksesmenu.php.
 * Semua "key" mengacu pada config tersebut. Dashboard (locked) selalu tampil.
 */
class MenuAkses
{
    /** Spesifikasi menu untuk sebuah role (array of ['key','label','locked?','default?']). */
    public static function spec(?string $role): array
    {
        return config('aksesmenu.' . $role, []);
    }

    /** Key yang locked (selalu tampil, tak bisa dimatikan). */
    public static function lockedKeys(?string $role): array
    {
        return collect(self::spec($role))
            ->filter(fn ($i) => !empty($i['locked']))
            ->pluck('key')->values()->all();
    }

    /** Key non-locked yang tercentang default. */
    public static function defaultKeys(?string $role): array
    {
        return collect(self::spec($role))
            ->filter(fn ($i) => empty($i['locked']) && !empty($i['default']))
            ->pluck('key')->values()->all();
    }

    /** Semua key non-locked yang valid (untuk sanitasi input). */
    public static function toggleableKeys(?string $role): array
    {
        return collect(self::spec($role))
            ->filter(fn ($i) => empty($i['locked']))
            ->pluck('key')->values()->all();
    }

    /**
     * Daftar key menu yang efektif tampil untuk pegawai.
     * $stored = nilai kolom akses_menu (array key non-locked) atau null.
     * NULL -> pakai default role.
     */
    public static function effective(?string $role, $stored): array
    {
        if (self::spec($role) === []) {
            return []; // role tanpa konfigurasi (mis. Manajemen) -> tak difilter di layout
        }

        $base = is_array($stored) ? $stored : self::defaultKeys($role);
        $valid = self::toggleableKeys($role);

        // hanya key valid + selalu sertakan locked (Dashboard)
        $base = array_values(array_intersect($base, $valid));

        return array_values(array_unique(array_merge(self::lockedKeys($role), $base)));
    }

    /** Sanitasi input dari form (buang key locked & yang tidak dikenal). */
    public static function sanitize(?string $role, $input): array
    {
        $valid = self::toggleableKeys($role);
        $input = is_array($input) ? $input : [];

        return array_values(array_intersect($input, $valid));
    }
}
