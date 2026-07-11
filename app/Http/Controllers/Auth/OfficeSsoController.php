<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * SSO masuk dari Office Portal (office.laksamanamuda.id).
 *
 * Office (via Apps Script) menandatangani token HMAC-SHA256 berisi identitas
 * pengguna, lalu me-redirect ke:
 *   https://backstage.laksamanamuda.id/sso/office?token=<payload>.<sig>
 *
 * Token format: base64url(JSON payload) + "." + base64url(HMAC(payload_b64, SECRET))
 * Payload: { sub: email, name, orole, to, iat, exp }   (iat/exp dalam ms epoch)
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

        // 3. Cek kedaluwarsa (exp dalam ms). Fallback iat + ttl bila exp absen.
        $nowMs = now()->valueOf();
        $exp   = (int) ($payload['exp'] ?? 0);
        if ($exp <= 0 && isset($payload['iat'])) {
            $exp = (int) $payload['iat'] + config('office.sso_ttl') * 1000;
        }
        if ($exp <= 0 || $nowMs > $exp) {
            return $this->deny('Sesi SSO kedaluwarsa. Silakan buka lagi dari Office.');
        }

        // 4. Petakan identitas Office -> pegawai (utamakan email, fallback nama)
        $email = strtolower(trim((string) ($payload['sub'] ?? '')));
        $name  = trim((string) ($payload['name'] ?? ''));

        $pegawai = null;
        if ($email !== '') {
            $pegawai = Pegawai::whereRaw('LOWER(email_pegawai) = ?', [$email])->first();
        }
        if (!$pegawai && $name !== '') {
            $pegawai = Pegawai::whereRaw('LOWER(nama_pegawai) = ?', [strtolower($name)])->first();
        }

        if (!$pegawai) {
            return $this->deny('Akun Office ini belum terhubung ke data pegawai backstage.');
        }

        // 5. Login sebagai pegawai + regenerasi sesi (cegah fixation)
        Auth::guard('pegawai')->login($pegawai);
        $request->session()->regenerate();

        // 6. Arahkan ke dashboard sesuai posisi pegawai (otoritatif)
        return redirect()->to($this->dashboardFor($pegawai->posisi_pegawai));
    }

    /** Tujuan dashboard berdasarkan posisi pegawai. */
    private function dashboardFor(?string $posisi): string
    {
        return match ($posisi) {
            'Manajemen'      => route('manajemen.dashboard'),
            'EventMarketing' => route('event.dashboard'),
            'Finance'        => route('finance.dashboard'),
            default          => route('login'),
        };
    }

    /** Gagal SSO -> kembali ke halaman login backstage dengan pesan. */
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
