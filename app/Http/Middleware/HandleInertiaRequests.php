<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Support\MenuAkses;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $pegawai = $request->user('pegawai');

        // Untuk pegawai: hitung key menu sidebar yang efektif tampil, lalu
        // timpa atribut akses_menu dengan hasil resolusi (locked + default/pilihan).
        // Layout membaca auth.user.akses_menu ini untuk memfilter tombol sidebar.
        if ($pegawai) {
            $pegawai->setAttribute(
                'akses_menu',
                MenuAkses::effective($pegawai->posisi_pegawai, $pegawai->akses_menu)
            );
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $pegawai ?? $request->user('client') ?? null,
            ],
            'flash' => [
                'success' => session('success'),
                'error'   => session('error'),
                'warning' => session('warning'),
            ],
        ];
    }
}
