<?php

/*
|--------------------------------------------------------------------------
| Integrasi Office Portal (SSO)
|--------------------------------------------------------------------------
| Jembatan single sign-on dari office.laksamanamuda.id (portal statis +
| Google Apps Script) ke aplikasi backstage (Laravel).
|
| SECRET harus SAMA PERSIS dengan Script Property `SSO_SECRET` di Apps Script
| office. Token ditandatangani HMAC-SHA256 dan berlaku singkat (sso_ttl detik).
*/

return [
    'sso_secret' => env('OFFICE_SSO_SECRET', ''),

    // Toleransi umur token (detik). Token dari office mengirim exp sendiri,
    // ini batas maksimum aman kalau exp tidak ada / terlalu jauh.
    'sso_ttl' => (int) env('OFFICE_SSO_TTL', 120),

    // URL portal office untuk redirect balik saat gagal / logout.
    'url' => env('OFFICE_URL', 'https://office.laksamanamuda.id'),
];
