# k6 Test Guide for Tiering and Referral

Target endpoint:
- `POST /api/membership/tiers/recalculate`
- `POST /api/membership/referrals/generate`
- `POST /api/membership/referrals/apply`

## Prerequisite

Aplikasi dijalankan lewat **Docker (Nginx + PHP-FPM)**, bukan `php artisan serve`.

### 1. Jalankan stack Docker

```bash
docker compose up -d --build
```

Pastikan semua service aktif:

```bash
docker compose ps
```

Harus ada: `loyalty-app`, `membership-webserver-1`, `loyalty-db`, `membership-redis-1`.

### 2. Setup database (sekali saja / setelah reset volume)

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

### 3. Cek website

Buka `http://localhost:8000` — dashboard harus tampil tanpa error database.

### 4. Install k6

Pastikan `k6` bisa dipanggil dari terminal. Di Windows:

```bash
"C:\Program Files\k6\k6.exe" version
```

## Jalankan Tiering Test

```bash
k6 run -e BASE_URL=http://localhost:8000 -e USER_ID_START=1 -e USER_ID_END=1000 k6/tiering-load.js
```

Lebih ringan:

```bash
k6 run -e BASE_URL=http://localhost:8000 -e USER_ID_START=1 -e USER_ID_END=1000 -e THINK_TIME=2 k6/tiering-load.js
```

## Jalankan Referral Test

Mode default (`REFEREE_MODE=register`) membuat user baru tiap iterasi supaya apply referral tidak gagal karena user sudah pernah pakai kode referral.

```bash
k6 run -e BASE_URL=http://localhost:8000 -e REFERRER_ID=1 k6/referral-load.js
```

Smoke test dengan user hasil seed (maksimal 1 apply per user):

```bash
k6 run -e BASE_URL=http://localhost:8000 -e REFERRER_ID=1 -e REFEREE_MODE=seeded -e REFEREE_START_ID=2 -e REFEREE_END_ID=1000 k6/referral-load.js
```

## Cara Membaca Hasil

- Kalau `http_req_duration` naik pelan saat VU naik, itu masih wajar.
- Kalau response time melonjak tajam di level VU tertentu, itu tanda bottleneck.
- Kalau `http_req_failed` mulai naik, biasanya ada masalah query, locking, atau validasi data.

## Rekomendasi Pengamatan

- Pantau `docker stats` saat test berjalan.
- Catat `p95`, error rate, throughput, CPU, dan memory per level VU.
- Bandingkan hasil tiering dan referral secara terpisah supaya analisisnya jelas.

## Troubleshooting

**502 Bad Gateway**
- Kontainer `loyalty-app` (PHP-FPM) belum jalan: `docker compose up -d app`

**Error database / Access denied**
- Pastikan `.env` memakai `DB_USERNAME=root` dan `DB_PASSWORD=root` (sesuai `docker-compose.yml`)
- Atau set ulang: `copy .env.example .env` lalu `docker compose up -d --force-recreate app`

**Referral test banyak gagal 422**
- Pakai `REFEREE_MODE=register` (default), bukan `seeded`, untuk load test berulang.
