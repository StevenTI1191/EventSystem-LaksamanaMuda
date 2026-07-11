<?php

namespace App\Console\Commands;

use App\Models\OfficeUser;
use App\Models\OfficeRole;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Import user & role Office dari CSV export GSheet ke MySQL (Level 2).
 *   php artisan office:import-users storage/app/users.csv storage/app/roles.csv
 *
 * Header CSV yang diharapkan (huruf boleh beda kapital):
 *   users.csv : id,name,pin,role,active,keterangan   (id & active/keterangan opsional)
 *   roles.csv : role,label,modules                    (modules: "*" atau "ordering,akademi")
 */
class ImportOfficeUsers extends Command
{
    protected $signature = 'office:import-users {users : path CSV Users} {roles? : path CSV Roles}';
    protected $description = 'Import user & role Office dari CSV GSheet ke MySQL (aman diulang / upsert)';

    public function handle(): int
    {
        $usersPath = $this->argument('users');
        $rolesPath = $this->argument('roles');

        if ($rolesPath) {
            $roles = $this->readCsv($rolesPath);
            if ($roles === null) return self::FAILURE;
            $nr = 0;
            foreach ($roles as $r) {
                $role = trim((string) ($r['role'] ?? ''));
                if ($role === '') continue;
                $modRaw = trim((string) ($r['modules'] ?? ''));
                $modules = $modRaw === '*'
                    ? ['*']
                    : array_values(array_filter(array_map('trim', explode(',', $modRaw))));
                OfficeRole::updateOrCreate(['role' => $role], [
                    'label'   => trim((string) ($r['label'] ?? $role)),
                    'modules' => $modules,
                ]);
                $nr++;
            }
            $this->info("Roles diimport: {$nr}");
        }

        $users = $this->readCsv($usersPath);
        if ($users === null) return self::FAILURE;

        $nu = 0;
        foreach ($users as $u) {
            $name = trim((string) ($u['name'] ?? ''));
            $pin  = trim((string) ($u['pin'] ?? ''));
            if ($name === '' || $pin === '') continue;

            $id = trim((string) ($u['id'] ?? '')) ?: 'u-' . Str::slug($name, '');
            $activeRaw = strtoupper(trim((string) ($u['active'] ?? 'TRUE')));

            OfficeUser::updateOrCreate(['id' => $id], [
                'name'       => $name,
                'pin'        => $pin, // otomatis terenkripsi oleh cast
                'role'       => trim((string) ($u['role'] ?? '')),
                'active'     => $activeRaw !== 'FALSE' && $activeRaw !== '0',
                'keterangan' => trim((string) ($u['keterangan'] ?? '')),
            ]);
            $nu++;
        }
        $this->info("Users diimport: {$nu}");

        return self::SUCCESS;
    }

    /** Baca CSV -> array assoc (header baris pertama, lowercase). */
    private function readCsv(string $path): ?array
    {
        if (!is_file($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return null;
        }
        $rows = [];
        $fh = fopen($path, 'r');
        $header = null;
        while (($data = fgetcsv($fh)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) $h)), $data);
                continue;
            }
            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = $data[$i] ?? '';
            }
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }
}
