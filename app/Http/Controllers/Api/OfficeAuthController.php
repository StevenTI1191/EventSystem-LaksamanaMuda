<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfficeUser;
use App\Models\OfficeRole;
use App\Support\OfficeToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Identity API Office (Level 2) — pengganti Apps Script.
 * Satu endpoint POST /api/office dengan dispatch `action`, MENIRU kontrak
 * Apps Script (login/listUsers/saveUser/deleteUser) agar index.html cukup
 * ganti URL. Respons `login` menyertakan ssoToken (loop SSO tetap jalan).
 */
class OfficeAuthController extends Controller
{
    public function handle(Request $request)
    {
        return match ((string) $request->input('action')) {
            'login'      => $this->login($request),
            'listUsers'  => $this->listUsers($request),
            'saveUser'   => $this->saveUser($request),
            'deleteUser' => $this->deleteUser($request),
            default      => response()->json(['ok' => false, 'error' => 'unknown_action']),
        };
    }

    /* ---------------- Auth ---------------- */

    private function login(Request $request)
    {
        $name = trim((string) $request->input('name'));
        $pin  = trim((string) $request->input('pin'));
        if ($name === '' || $pin === '') {
            return response()->json(['ok' => false, 'error' => 'missing']);
        }

        $user = $this->findByCreds($name, $pin);
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'invalid']);
        }

        $role    = (string) $user->role;
        $modules = $user->modules();

        return response()->json(['ok' => true, 'user' => [
            'id'       => $user->id,
            'name'     => $user->name,
            'role'     => $role,
            'modules'  => $modules,
            'ssoToken' => OfficeToken::make($user->id, $user->name, $role),
        ]]);
    }

    /* ---------------- Admin CRUD ---------------- */

    private function listUsers(Request $request)
    {
        if (!$this->caller($request)) {
            return response()->json(['ok' => false, 'error' => 'forbidden']);
        }

        $users = OfficeUser::orderBy('name')->get()->map(fn ($u) => [
            'id' => $u->id, 'name' => $u->name, 'pin' => $u->pin, // pin terdekripsi (admin boleh lihat)
            'role' => $u->role, 'active' => $u->active, 'keterangan' => $u->keterangan,
        ]);
        $roles = OfficeRole::orderBy('role')->pluck('role');

        return response()->json(['ok' => true, 'users' => $users, 'roles' => $roles]);
    }

    private function saveUser(Request $request)
    {
        if (!$this->caller($request)) {
            return response()->json(['ok' => false, 'error' => 'forbidden']);
        }

        $name = trim((string) $request->input('name'));
        $pin  = trim((string) $request->input('pin'));
        $role = trim((string) $request->input('role'));
        if ($name === '' || $pin === '' || $role === '') {
            return response()->json(['ok' => false, 'error' => 'missing_fields']);
        }

        $editId = trim((string) $request->input('id'));

        // Unik: nama & PIN tidak boleh sama dengan user lain
        foreach (OfficeUser::all() as $u) {
            if ($u->id === $editId) continue;
            if (mb_strtolower($u->name) === mb_strtolower($name)) {
                return response()->json(['ok' => false, 'error' => 'name_taken']);
            }
            if (hash_equals((string) $u->pin, $pin)) {
                return response()->json(['ok' => false, 'error' => 'pin_taken']);
            }
        }

        $data = [
            'name'       => $name,
            'pin'        => $pin,
            'role'       => $role,
            'active'     => $request->boolean('active', true),
            'keterangan' => trim((string) $request->input('keterangan')),
        ];

        if ($editId !== '' && ($existing = OfficeUser::find($editId))) {
            $existing->update($data);
            return response()->json(['ok' => true, 'id' => $existing->id]);
        }

        // Buat baru: id = u-<slug nama>, dedup
        $base = 'u-' . Str::slug($name, '');
        $id = $base;
        $n = 2;
        while (OfficeUser::whereKey($id)->exists()) {
            $id = $base . $n++;
        }
        OfficeUser::create(['id' => $id] + $data);

        return response()->json(['ok' => true, 'id' => $id]);
    }

    private function deleteUser(Request $request)
    {
        $caller = $this->caller($request);
        if (!$caller) {
            return response()->json(['ok' => false, 'error' => 'forbidden']);
        }

        $id = trim((string) $request->input('id'));
        if ($id !== '' && $id === $caller->id) {
            return response()->json(['ok' => false, 'error' => 'cannot_delete_self']);
        }

        $user = OfficeUser::find($id);
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'not_found']);
        }
        $user->delete();

        return response()->json(['ok' => true]);
    }

    /* ---------------- Helpers ---------------- */

    /** Cari user aktif yang cocok nama + PIN (PIN terdekripsi, timing-safe). */
    private function findByCreds(string $name, string $pin): ?OfficeUser
    {
        $user = OfficeUser::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where('active', true)->first();

        if ($user && hash_equals((string) $user->pin, $pin)) {
            return $user;
        }
        return null;
    }

    /** Verifikasi pemanggil aksi admin: cocok kredensial + role ber-modul '*'. */
    private function caller(Request $request): ?OfficeUser
    {
        $caller = $this->findByCreds(
            trim((string) $request->input('callerName')),
            trim((string) $request->input('callerPin'))
        );
        if (!$caller) return null;

        return in_array('*', $caller->modules(), true) ? $caller : null;
    }
}
