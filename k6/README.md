# K6 Stress Test Guide — Referral & Tiering System (Team 9)

## Target Endpoints

| Endpoint | Method | Deskripsi |
|---|---|---|
| `/api/membership/referrals/generate` | POST | Generate referral code untuk user |
| `/api/membership/referrals/apply`    | POST | Apply referral code (referee) |
| `/api/membership/tiers/recalculate` | POST | Recalculate tier user |
| `/api/membership/tiers`             | GET  | List semua tier |
| `/api/register`                     | POST | Register user baru (dipakai skenario normal) |

---

## File yang Tersedia

| File | Fungsi |
|---|---|
| `referral-tiering-stress.js` | **Script utama** — stress test referral + tiering |
| `k6-run-referral-tiering.ps1` | **Runner** — jalankan semua level load otomatis |
| `k6-analyze-referral-tiering.ps1` | **Analyzer** — analisis hasil & generate report |
| `referral-load.js` | Script lama — referral saja (masih bisa dipakai) |
| `referral-generate-load.js` | Script lama — generate referral saja |
| `tiering-load.js` | Script lama — tiering saja |

---

## Prerequisite

### 1. Jalankan stack Docker
```bash
docker compose up -d --build
docker compose ps
```
Harus ada: `loyalty-app`, `membership-webserver-1`, `loyalty-db`, `membership-redis-1`.

### 2. Setup database (sekali saja)
```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

### 3. Verifikasi endpoint aktif
```bash
curl http://localhost:8000/api/membership/tiers
```

### 4. Install & verifikasi k6
```bash
# Windows — cek instalasi
"C:\Program Files\k6\k6.exe" version

# Atau pakai Chocolatey / Scoop
choco install k6
scoop install k6
```

---

## Cara Menjalankan Test

### 🚀 Cara Cepat — Runner Otomatis (Direkomendasikan)

Runner ini menjalankan **semua level** (10, 50, 100, 500, 1000 VUs) secara berurutan
dan menyimpan hasilnya ke folder `results/`.

```powershell
# Jalankan dari root project
.\k6\k6-run-referral-tiering.ps1
```

Opsi lainnya:

```powershell
# Hanya level tertentu
.\k6\k6-run-referral-tiering.ps1 -Loads @(10, 50, 100)

# Skenario race condition
.\k6\k6-run-referral-tiering.ps1 -Scenario race_condition -Loads @(50, 100, 500)

# Durasi lebih panjang per level
.\k6\k6-run-referral-tiering.ps1 -Duration 2m

# Tanpa konfirmasi (untuk CI/CD)
.\k6\k6-run-referral-tiering.ps1 -SkipConfirm
```

---

### 🔧 Cara Manual — k6 Langsung

#### Normal scenario (full workflow)
```bash
# 10 users
k6 run --env BASE_URL=http://localhost:8000 --env SCENARIO=normal --env LOAD=10 --env DURATION=1m k6/referral-tiering-stress.js

# 50 users
k6 run --env BASE_URL=http://localhost:8000 --env SCENARIO=normal --env LOAD=50 --env DURATION=1m k6/referral-tiering-stress.js

# 100 users
k6 run --env BASE_URL=http://localhost:8000 --env SCENARIO=normal --env LOAD=100 --env DURATION=1m k6/referral-tiering-stress.js

# 500 users
k6 run --env BASE_URL=http://localhost:8000 --env SCENARIO=normal --env LOAD=500 --env DURATION=1m k6/referral-tiering-stress.js

# 1000 users
k6 run --env BASE_URL=http://localhost:8000 --env SCENARIO=normal --env LOAD=1000 --env DURATION=1m k6/referral-tiering-stress.js
```

#### Race condition scenario
```bash
# Simulasi banyak user apply referral secara bersamaan
k6 run --env BASE_URL=http://localhost:8000 --env SCENARIO=race_condition --env LOAD=100 --env DURATION=1m k6/referral-tiering-stress.js
```

#### Mixed scenario
```bash
# Campuran generate, apply, recalculate, list tiers
k6 run --env BASE_URL=http://localhost:8000 --env SCENARIO=mixed --env LOAD=100 --env DURATION=1m k6/referral-tiering-stress.js
```

#### Simpan hasil ke JSON
```bash
k6 run \
  --env BASE_URL=http://localhost:8000 \
  --env SCENARIO=normal \
  --env LOAD=100 \
  --summary-export=k6/results/normal_load100.json \
  k6/referral-tiering-stress.js
```

---

## Skenario Test

### `normal` — Full Workflow (Default)
Mensimulasikan alur lengkap per user:
1. Generate referral code (referrer)
2. Register user baru (referee)
3. Apply referral code
4. Recalculate tier referrer (dapat poin referral)
5. Recalculate tier referee (tier baru)
6. List all tiers (read-only)

### `race_condition` — Concurrent Apply
Mensimulasikan banyak user yang mencoba apply referral code yang sama secara bersamaan.
- Menguji konsistensi database (tidak boleh ada data corrupt)
- Response 422 adalah **expected** (sudah pernah apply)
- Yang dihitung error hanya HTTP 5xx

### `mixed` — Beban Campuran
Proporsi realistis:
- 35% → Full normal workflow
- 25% → Recalculate tier saja
- 20% → Generate referral code saja
- 15% → List tiers (read-only)
- 5%  → Race condition mini

---

## Membaca Hasil Test

### Di terminal saat test berjalan:
```
✓ generate: status 200
✓ apply: status 200 or 422
✓ recalculate: has tier field

generate_referral_duration p(95)=245ms  ✅
apply_referral_duration    p(95)=412ms  ✅
recalculate_tier_duration  p(95)=889ms  ⚠️
```

### Metric yang perlu diperhatikan:

| Metric | Target |
|---|---|
| `generate_referral_duration` p95 | < 500ms |
| `apply_referral_duration` p95 | < 800ms |
| `recalculate_tier_duration` p95 | < 1000ms |
| `list_tiers_duration` p95 | < 300ms |
| `generate_referral_errors` rate | < 5% |
| `apply_referral_errors` rate | < 10% |
| `http_req_failed` rate | < 10% |

### Analisis setelah test:
```powershell
# Auto-detect file terbaru
.\k6\k6-analyze-referral-tiering.ps1

# File spesifik
.\k6\k6-analyze-referral-tiering.ps1 -ResultFile "k6/results/normal_load100_20260622_120000.json"

# Filter skenario
.\k6\k6-analyze-referral-tiering.ps1 -Scenario race_condition
```

---

## Cara Membaca Hasil

- **Response time naik perlahan** saat VU naik → normal, server masih bisa handle
- **Response time melonjak tajam** di level VU tertentu → bottleneck ditemukan
- **`http_req_failed` naik** → masalah query, locking, atau validasi data
- **`apply_referral_errors` tinggi** → kemungkinan double-apply atau constraint error
- **`recalculate_tier_duration` tinggi** → query tier butuh optimasi (index/caching)

---

## Monitoring Saat Test Berjalan

```bash
# Pantau resource Docker secara real-time
docker stats

# Lihat log aplikasi (error, slow query, dll)
docker compose logs -f app

# Lihat log Nginx
docker compose logs -f webserver
```

---

## Troubleshooting

**502 Bad Gateway**
- Container `loyalty-app` belum jalan: `docker compose up -d app`

**`apply_referral_errors` sangat tinggi (>50%)**
- Pastikan seeder sudah dijalankan: `docker compose exec app php artisan db:seed --force`
- Pastikan user ID 1 ada di database (REFERRER_ID default = 1)

**`register_errors` tinggi**
- Cek constraint unique email di database
- Pastikan endpoint `/api/register` tidak memerlukan autentikasi JWT

**k6 tidak ditemukan**
- Tambahkan ke PATH: `C:\Program Files\k6\`
- Atau gunakan path lengkap: `"C:\Program Files\k6\k6.exe" run ...`

**Koneksi ditolak**
- Pastikan port 8000 tidak diblokir: `docker compose ps`
- Coba: `curl http://localhost:8000/api/membership/tiers`

---

## Rekomendasi Pengamatan

1. Catat **p95**, **error rate**, **throughput** di setiap level VU
2. Bandingkan hasil **referral** vs **tiering** — mana yang lebih lambat?
3. Pada level berapa response time mulai melebihi threshold?
4. Apakah race condition memunculkan data inconsistency?
5. Pantau **CPU & memory** Docker saat load tinggi: `docker stats`
