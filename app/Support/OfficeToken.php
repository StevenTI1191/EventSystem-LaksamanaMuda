<?php

namespace App\Support;

/**
 * Pembuat token SSO Office (HMAC-SHA256), format identik dengan yang
 * diverifikasi OfficeSsoController. Dipakai saat login via Laravel (Level 2)
 * agar loop SSO ke 3 modul backstage tetap jalan.
 *
 * Token: base64url(JSON payload) + "." + base64url(HMAC(payload_b64, SECRET))
 * Payload: { sub, name, orole, iat, exp }  (ms epoch)
 */
class OfficeToken
{
    public static function make(string $sub, string $name, string $orole, int $ttlSeconds = 86400): string
    {
        $secret = (string) config('office.sso_secret');
        if ($secret === '') {
            return '';
        }

        $now = (int) round(microtime(true) * 1000);
        $payload = [
            'sub'   => $sub,
            'name'  => $name,
            'orole' => $orole,
            'iat'   => $now,
            'exp'   => $now + $ttlSeconds * 1000,
        ];

        $b64 = self::b64url(json_encode($payload));
        $sig = self::b64url(hash_hmac('sha256', $b64, $secret, true));

        return $b64 . '.' . $sig;
    }

    private static function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }
}
