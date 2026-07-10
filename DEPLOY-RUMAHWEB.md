# Panduan Deploy ke Rumahweb (Shared Hosting cPanel + Terminal)

Aplikasi: **Laksamana Muda** — Laravel 12 + Inertia (React) + Pusher.
Hosting: Rumahweb **Unlimited M**, cPanel user **`lakk5493`**, domain **`laksamanamuda.id`**.
Akses: **Terminal cPanel tersedia** ✅ (jadi bisa jalankan composer & artisan langsung).

Skema domain (2 subdomain):
- **`client.laksamanamuda.id`** → area publik/klien
- **`backstage.laksamanamuda.id`** → area admin/pegawai
- Cookie login dibagi via `SESSION_DOMAIN=.laksamanamuda.id`

Path aplikasi di server: **`/home/lakk5493/laravel_app`** (di luar `public_html`).

---

## Ringkasan perubahan dari versi VPS/Docker

| Aspek | VPS/Docker (`.env.production`) | Shared hosting (`.env.rumahweb.example`) |
|---|---|---|
| Broadcasting | Reverb (self-host) | **Pusher (cloud)** |
| Queue / Cache | Redis | **database** |
| Session | database | database (cookie subdomain) |
| DB host | `mysql` (container) | **`localhost`** |

`docker-compose.yml`, `Dockerfile`, `docker/`, `.env.production` **tidak dipakai** — jangan diupload.

---

## FASE 1 — Persiapan di komputer lokal

### 1.1 Pusher
1. Daftar https://pusher.com → **Channels** → **Create app** → cluster **ap1 (Singapore)**.
2. Catat `app_id`, `key`, `secret`.

### 1.2 File `.env` produksi
1. Salin `.env.rumahweb.example` → `.env.rumahweb` (di-gitignore, aman untuk secret).
2. Isi `<ISI_...>`: DB (Fase 3), Pusher, SMTP, Google OAuth.

### 1.3 Build asset (Vite membakar VITE_* saat build → lakukan SETELAH .env terisi)
```bash
cp .env.rumahweb .env       # sementara, agar Vite baca var produksi
npm ci
npm run build               # menghasilkan public/build/  (WAJIB diupload)
```

### 1.4 Commit & push kode ke GitHub
```bash
git add -A && git commit -m "chore: konfigurasi deploy Rumahweb"
git push
```
`vendor/` & `public/build/` tidak ikut Git (gitignore) — ditangani di server (composer) & upload manual.

---

## FASE 2 — Subdomain, PHP & SSL di cPanel

### 2.1 Versi PHP
**MultiPHP Manager** → set domain ke **PHP 8.2/8.3**.
**Select PHP Version → Extensions**, aktifkan:
`bcmath, ctype, curl, dom, fileinfo, filter, gd, intl, mbstring, openssl, pdo, pdo_mysql,
tokenizer, xml, zip`.

### 2.2 Buat 2 subdomain → arahkan ke folder public Laravel yang SAMA
cPanel → **Domains / Subdomains** → buat:
| Subdomain | Document Root |
|---|---|
| `client.laksamanamuda.id` | `/home/lakk5493/laravel_app/public` |
| `backstage.laksamanamuda.id` | `/home/lakk5493/laravel_app/public` |

> Dengan mengarahkan docroot langsung ke `laravel_app/public`, **tidak perlu** menyalin
> isi `public/` ke `public_html` atau mengedit `index.php`. Aplikasi membedakan tampilan
> per subdomain lewat `Route::domain()` (APP_DOMAIN / BACKSTAGE_DOMAIN).

### 2.3 SSL (WAJIB)
**SSL/TLS Status** → jalankan **AutoSSL** untuk kedua subdomain.
Wajib karena `SESSION_SECURE_COOKIE=true` & Pusher `forceTLS`. Tanpa HTTPS, login gagal.

---

## FASE 3 — Database

1. cPanel → **MySQL Databases** → buat DB (mis. `lakk5493_db_lmb`).
2. Buat user DB + password kuat → **Add User To Database** → **ALL PRIVILEGES**.
3. Masukkan ke `.env`: `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_HOST=localhost`.
   (Migrasi dijalankan di Fase 5 — tabel dibuat otomatis, tidak perlu import SQL manual.)

---

## FASE 4 — Ambil kode ke server (`laravel_app`)

**Opsi A — cPanel Git Version Control (disarankan):**
1. Repo ini privat → beri akses: cPanel **SSH Access → Manage SSH Keys** → buat key →
   salin **public key** → GitHub repo **Settings → Deploy keys → Add**.
2. cPanel → **Git Version Control** → **Create** → Clone URL (SSH):
   `git@github.com:StevenTI1191/EventSystem-LaksamanaMuda.git` →
   Repository Path: `/home/lakk5493/laravel_app`.
3. `.cpanel.yml` di repo akan menyalin kode + rebuild cache saat **Deploy HEAD Commit**.

**Opsi B — Upload ZIP:** zip project (kecuali `node_modules/`, `.git/`, `docker/`,
`.env.production`) **termasuk `public/build/`** → File Manager → extract ke
`/home/lakk5493/laravel_app`.

---

## FASE 5 — Terminal cPanel: composer, .env, artisan

cPanel → **Terminal**, lalu:

```bash
cd ~/laravel_app

# 1. Install dependency PHP (produksi, tanpa dev)
composer install --no-dev --optimize-autoloader

# 2. Buat file .env (paste isi dari .env.rumahweb yang sudah kamu lengkapi)
nano .env         # tempel isi, simpan (Ctrl+O, Enter, Ctrl+X)

# 3. APP_KEY sudah ada di .env. Kalau kosong: php artisan key:generate

# 4. Migrasi database
php artisan migrate --force

# 5. Symlink storage publik
php artisan storage:link

# 6. Optimasi cache
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

> Jika `composer` tidak ditemukan: coba `php -d memory_limit=-1 $(which composer.phar)` atau
> tanya support Rumahweb path composer-nya.

### Permission
```bash
chmod -R 775 ~/laravel_app/storage ~/laravel_app/bootstrap/cache
```

---

## FASE 6 — Queue via Cron (pengganti Redis worker)

cPanel → **Cron Jobs** → tambah (tiap menit):
```
* * * * * /usr/local/bin/ea-php82 /home/lakk5493/laravel_app/artisan queue:work --stop-when-empty >> /dev/null 2>&1
* * * * * /usr/local/bin/ea-php82 /home/lakk5493/laravel_app/artisan schedule:run >> /dev/null 2>&1
```
> Sesuaikan path PHP (`ea-php82`/`ea-php83`). Cek: `which php` di Terminal.

---

## FASE 7 — Integrasi eksternal

- **Pusher:** kredensial di `.env`, `BROADCAST_CONNECTION=pusher`.
- **Google OAuth:** Google Console → Authorized redirect URIs tambahkan
  `https://client.laksamanamuda.id/auth/google/callback` (samakan dgn `GOOGLE_REDIRECT_URI`).
- **Email:** isi SMTP di `.env`. Email Rumahweb: `MAIL_HOST=mail.laksamanamuda.id`,
  port `465` (SSL) / `587` (TLS).

---

## FASE 8 — Checklist final

- [ ] PHP 8.2/8.3 + ekstensi lengkap
- [ ] SSL hijau untuk `client.*` dan `backstage.*`
- [ ] Docroot kedua subdomain → `laravel_app/public`
- [ ] DB dibuat, user ALL PRIVILEGES
- [ ] `composer install --no-dev` sukses; `vendor/` ada
- [ ] `public/build/` terupload
- [ ] `.env` benar (DB localhost, Pusher, `APP_DEBUG=false`, domain `.id`)
- [ ] `migrate --force`, `storage:link`, `optimize` sukses
- [ ] permission `storage/` & `bootstrap/cache/` = 775
- [ ] Cron `queue:work` + `schedule:run`
- [ ] Google redirect URI diperbarui
- [ ] Uji: login klien (client.*), login pegawai (backstage.*), upload bukti, notifikasi realtime

---

## FASE 9 — Update website setelah live

Server menjalankan hasil jadi; build dilakukan di lokal.

**Kirim perubahan (pilih satu):**
- **cPanel Git:** `git push` → cPanel Git → **Update from Remote** → **Deploy HEAD Commit**
  (`.cpanel.yml` menyalin kode + rebuild cache otomatis).
- **Terminal:** `cd ~/laravel_app && git pull`.

**Aksi tambahan sesuai jenis perubahan:**
| Berubah | Aksi |
|---|---|
| Backend PHP | cukup deploy kode (`optimize:clear` sudah otomatis di `.cpanel.yml`) |
| Frontend (`.jsx`/CSS) | `npm run build` di lokal → upload `public/build/` |
| Package Composer | Terminal: `composer install --no-dev --optimize-autoloader` |
| Migration baru | Terminal: `php artisan migrate --force` |
| Ubah `.env` | Terminal: `php artisan optimize:clear && php artisan config:cache` |

---

## Troubleshooting cepat

| Gejala | Sebab | Solusi |
|---|---|---|
| HTTP 500 layar putih | permission / .env | cek `storage/logs/laravel.log`; `APP_DEBUG=true` sementara |
| Aset CSS/JS 404 | `public/build` tak terupload | upload ulang `build/` |
| Login gagal / logout terus | cookie/HTTPS | pastikan SSL aktif & `SESSION_DOMAIN=.laksamanamuda.id` |
| Realtime mati | Pusher key salah / build lama | cek `VITE_PUSHER_*`, build ulang, cek console |
| "could not find driver" | pdo_mysql off | aktifkan di PHP Extensions |
| Queue tak jalan | cron / path PHP | cek Cron Jobs & `which php` |

---

## Catatan keamanan

- **Rotasi semua secret** dari `.env.production` lama (DB, Pusher, SMTP, Google) — beda konteks.
- `.env`, `.env.production`, `.env.rumahweb` sudah di-`.gitignore` — jangan pernah commit.
