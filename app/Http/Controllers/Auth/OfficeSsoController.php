<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * SSO masuk dari Office Portal (office.laksamanamuda.id) + AUTO-SYNC pegawai.
 *
 * Office (Apps Script) menandatangani token HMAC-SHA256 berisi identitas user,
 * lalu me-redirect ke: <domain>/sso/office?token=<payload_b64>.<sig_b64>
 * Payload: { sub: <id user office>, name, orole, iat, exp }  (ms epoch)
 *
 * Auto-sync (Level 1): GSheet = master, tabel `pegawais` = cermin otomatis.
 * Pegawai dikunci ke user Office lewat kolom `office_uid`. Bila belum ada,
 * pegawai dibuat otomatis; bila ada, nama & posisi diselaraskan dari token.
 * Tidak perlu lagi mencocokkan nama secara manual.
 */
class OfficeSsoController extends Controller
{
    public function login(Request $request)
    {
        $secret = (string) config('office.sso_secret');
        $token  = (string) $request->query('token', '');

        if ($secret === '' || $token === '' || !str_contains($token, '.')) {
            return $this->deny('Token SSO tidak valid.');
        }

        [$payloadB64, $sig] = explode('.', $token, 2);

        // 1. Verifikasi tanda tangan (timing-safe)
        $expected = $this->b64url(hash_hmac('sha256', $payloadB64, $secret, true));
        if (!hash_equals($expected, $sig)) {
            return $this->deny('Tanda tangan SSO tidak cocok.');
        }

        // 2. Decode payload
        $payload = json_decode($this->b64urlDecode($payloadB64), true);
        if (!is_array($payload)) {
            return $this->deny('Payload SSO rusak.');
        }

        // 3. Cek kedaluwarsa (exp dalam ms; fallback iat + ttl)
        $nowMs = now()->valueOf();
        $exp   = (int) ($payload['exp'] ?? 0);
        if ($exp <= 0 && isset($payload['iat'])) {
            $exp = (int) $payload['iat'] + config('office.sso_ttl') * 1000;
        }
        if ($exp <= 0 || $nowMs > $exp) {
            return $this->deny('Sesi SSO kedaluwarsa. Silakan buka lagi dari Office.');
        }

        // 4. Petakan role Office -> posisi pegawai
        $uid    = trim((string) ($payload['sub'] ?? ''));
        $name   = trim((string) ($payload['name'] ?? ''));
        $posisi = $this->posisiFromOrole((string) ($payload['orole'] ?? ''));

        if (!$posisi) {
            return $this->deny('Role Office ini tidak punya modul backstage.');
        }

        // 5. Cari pegawai (office_uid -> email bila sub email -> nama), lalu SYNC
        $pegawai = $this->findPegawai($uid, $name);

        if ($pegawai) {
            $pegawai->office_uid     = $uid !== '' ? $uid : $pegawai->office_uid;
            if ($name !== '') $pegawai->nama_pegawai = $name;
            $pegawai->posisi_pegawai = $posisi;
            $pegawai->save();
        } else {
            // 6. Auto-provision pegawai baru dari identitas Office
            $pegawai = Pegawai::create([
                'nama_pegawai'     => $name !== '' ? $name : 'User Office',
                'jenis_pegawai'    => 'Internal',
                'posisi_pegawai'   => $posisi,
                'no_hp_pegawai'    => '-',
                'email_pegawai'    => $this->syntheticEmail($uid, $name),
                'password_pegawai' => Hash::make(Str::random(40)), // login hanya via SSO
                'office_uid'       => $uid !== '' ? $uid : null,
            ]);
        }

        // 7. Login sebagai pegawai + regenerasi sesi (cegah fixation)
        Auth::guard('pegawai')->login($pegawai);
        $request->session()->regenerate();

        return redirect()->to($this->dashboardFor($pegawai->posisi_pegawai));
    }

    /** Cari pegawai berdasarkan office_uid, lalu email (bila sub email), lalu nama. */
    private function findPegawai(string $uid, string $name): ?Pegawai
    {
        if ($uid !== '') {
            $p = Pegawai::where('office_uid', $uid)->first();
            if ($p) return $p;

            if (str_contains($uid, '@')) {
                $p = Pegawai::whereRaw('LOWER(email_pegawai) = ?', [strtolower($uid)])->first();
                if ($p) return $p;
            }
        }

        if ($name !== '') {
            return Pegawai::whereRaw('LOWER(nama_pegawai) = ?', [mb_strtolower($name)])->first();
        }

        return null;
    }

    /** Role Office -> posisi pegawai Laravel (pemetaan standar). */
    private function posisiFromOrole(?string $orole): ?string
    {
        return match (strtolower(trim((string) $orole))) {
            'manajemen', 'management', 'owner', 'admin' => 'Manajemen',
            'finance', 'keuangan'                       => 'Finance',
            'eventmarketing', 'event_marketing', 'event-marketing', 'em', 'marketing' => 'EventMarketing',
            default => null,
        };
    }

    /** Email sintetis unik untuk pegawai hasil auto-provision (login hanya via SSO). */
    private function syntheticEmail(string $uid, string $name): string
    {
        $base = $uid !== '' ? $uid : Str::slug($name !== '' ? $name : 'office-user');
        $base = preg_replace('/[^a-zA-Z0-9._-]/', '', $base) ?: 'user';
        $email = strtolower($base) . '@office.local';

        $i = 1;
        while (Pegawai::where('email_pegawai', $email)->exists()) {
            $email = strtolower($base) . '-' . (++$i) . '@office.local';
        }
        return $email;
    }

    private function dashboardFor(?string $posisi): string
    {
        return match ($posisi) {
            'Manajemen'      => route('manajemen.dashboard'),
            'EventMarketing' => route('event.dashboard'),
            'Finance'        => route('finance.dashboard'),
            default          => route('login'),
        };
    }

    private function deny(string $message)
    {
        return redirect()->route('login')->with('error', $message);
    }

    private function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function b64urlDecode(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/')) ?: '';
    }
}
