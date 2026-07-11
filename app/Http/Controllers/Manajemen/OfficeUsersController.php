<?php

namespace App\Http\Controllers\Manajemen;

use App\Http\Controllers\Controller;
use App\Models\OfficeUser;
use App\Traits\ChecksPegawaiRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Roster seluruh user Office + atur akses modul (checkbox) dari modul Manajemen.
 * Menulis ke office_users.modules (override role). '*' = akses penuh.
 */
class OfficeUsersController extends Controller
{
    use ChecksPegawaiRole;

    public function index()
    {
        $this->checkManajemen();

        $users = OfficeUser::orderBy('name')->get()->map(fn ($u) => [
            'id'      => $u->id,
            'name'    => $u->name,
            'role'    => $u->role,
            'active'  => $u->active,
            'modules' => $u->resolvedModules(),                 // untuk centang awal
            'custom'  => is_array($u->modules) && count($u->modules) > 0,
        ]);

        return Inertia::render('Manajemen/OfficeUsers/Index', [
            'users'      => $users,
            'moduleList' => config('office_modules'),
        ]);
    }

    /** Tambah user Office baru (akun login) langsung dari Manajemen. */
    public function store(Request $request)
    {
        $this->checkManajemen();

        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'pin'        => ['required', 'regex:/^\d{3,8}$/'],
            'modules'    => 'present|array',
            'modules.*'  => 'string',
            'active'     => 'boolean',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $name = trim($data['name']);
        $pin  = trim($data['pin']);

        if (OfficeUser::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            return back()->with('error', 'Nama "' . $name . '" sudah dipakai user lain.');
        }
        foreach (OfficeUser::all() as $u) {
            if (hash_equals((string) $u->pin, $pin)) {
                return back()->with('error', 'PIN itu sudah dipakai user lain.');
            }
        }

        // id unik: u-<slug nama>
        $base = 'u-' . Str::slug($name, '');
        $id = $base;
        $n = 2;
        while (OfficeUser::whereKey($id)->exists()) {
            $id = $base . $n++;
        }

        OfficeUser::create([
            'id'         => $id,
            'name'       => $name,
            'pin'        => $pin,
            'role'       => null,
            'modules'    => $this->sanitizeModules($data['modules']),
            'active'     => $data['active'] ?? true,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        return back()->with('success', 'User ' . $name . ' ditambahkan.');
    }

    public function destroy(string $id)
    {
        $this->checkManajemen();

        $user = OfficeUser::findOrFail($id);
        $name = $user->name;
        $user->delete();

        return back()->with('success', 'User ' . $name . ' dihapus.');
    }

    public function update(Request $request, string $id)
    {
        $this->checkManajemen();

        $user = OfficeUser::findOrFail($id);

        $data = $request->validate([
            'modules'   => 'present|array',
            'modules.*' => 'string',
        ]);

        $user->modules = $this->sanitizeModules($data['modules']);
        $user->save();

        return back()->with('success', 'Akses modul ' . $user->name . ' diperbarui.');
    }

    /** '*' = akses penuh; selain itu hanya key modul valid. */
    private function sanitizeModules(array $modules): array
    {
        if (in_array('*', $modules, true)) {
            return ['*'];
        }
        return array_values(array_intersect($modules, array_keys(config('office_modules'))));
    }
}
