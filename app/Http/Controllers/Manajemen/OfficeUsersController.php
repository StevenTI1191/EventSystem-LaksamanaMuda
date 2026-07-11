<?php

namespace App\Http\Controllers\Manajemen;

use App\Http\Controllers\Controller;
use App\Models\OfficeUser;
use App\Traits\ChecksPegawaiRole;
use Illuminate\Http\Request;
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

    public function update(Request $request, string $id)
    {
        $this->checkManajemen();

        $user = OfficeUser::findOrFail($id);

        $data = $request->validate([
            'modules'   => 'present|array',
            'modules.*' => 'string',
        ]);

        $valid   = array_keys(config('office_modules'));
        $modules = $data['modules'];

        $modules = in_array('*', $modules, true)
            ? ['*']
            : array_values(array_intersect($modules, $valid));

        $user->modules = $modules;
        $user->save();

        return back()->with('success', 'Akses modul ' . $user->name . ' diperbarui.');
    }
}
