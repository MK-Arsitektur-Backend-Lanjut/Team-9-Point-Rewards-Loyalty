# 🏆 Point Rewards & Loyalty System — Team 9

> Backend API untuk sistem loyalitas member berbasis poin, mencakup Activity Rules, Reward Processing, Autentikasi JWT, Membership Tiering, dan Referral System.

---

## 📦 Tech Stack

| Layer | Teknologi |
|---|---|
| Framework | Laravel (PHP) |
| Database | PostgreSQL 15 |
| Auth | JWT (tymon/jwt-auth) |
| Container | Docker + Nginx |
| Pattern | Repository Pattern + Service Layer |

---

## 🚀 Quick Start

### 1. Clone & Setup Environment

```bash
cd "d:\KULYEAH\SEMT 8\Backend\Team9"
copy .env.example .env
```

### 2. Jalankan Docker

```bash
docker-compose up -d --build
```

### 3. Setup Database

```bash
# Install dependencies
docker-compose exec app composer install

# Generate app key
docker-compose exec app php artisan key:generate

# Jalankan migrations
docker-compose exec app php artisan migrate

# Seed database (35k+ logs, 10k+ referrals)
docker-compose exec app php artisan db:seed
```

### 4. Akses Aplikasi

```
http://localhost:8000
```

---

## 🗂️ Struktur Folder

```
Team9/
├── app/
│   ├── Models/                          # Eloquent Models
│   ├── Repositories/
│   │   ├── Contracts/                   # Repository Interfaces
│   │   └── Eloquent/                    # Repository Implementations
│   ├── Services/                        # Business Logic
│   ├── Http/
│   │   ├── Controllers/Api/             # API Controllers
│   │   ├── Middleware/                  # JWT Middleware
│   │   └── Requests/                    # Form Request Validation
│   ├── Exceptions/                      # Custom Exceptions
│   └── Providers/                       # Service Providers
├── database/
│   ├── migrations/                      # Database Migrations
│   └── seeders/                         # Database Seeders
├── routes/
│   └── api.php                          # API Routes
├── resources/views/
│   └── welcome.blade.php                # Dashboard UI
├── tests/
│   ├── Unit/                            # Unit Tests
│   └── Feature/                         # Feature Tests
├── docker-compose.yml
├── Dockerfile
├── nginx.conf
└── API_DOCUMENTATION.md
```

---

## 📋 Database

**Engine**: PostgreSQL 15

| Setting | Value |
|---|---|
| Host | localhost:5432 |
| Username | postgres |
| Password | secret |
| Database | loyalty_db |

**Tables**: `users`, `activity_rules`, `rewards`, `reward_redemptions`, `point_activity_logs`, `membership_tiers`, `referral_logs`, `point_balances`, `point_logs`, `point_rules`, `referrals`

---

## 📊 Data Seeding

| Data | Jumlah |
|---|---|
| Users | 1.000 (berbagai tier: Bronze, Silver, Gold, Platinum) |
| Point Activity Logs | 35.000+ |
| Referral Records | 10.000+ |
| Point Rules | 6 (dengan multiplier tier) |
| Membership Tiers | 3 (Bronze, Silver, Gold) |

---

## 🔵 Modul 1 — Activity Rules & Rewards

> **Dikerjakan oleh**: Team Member 1

### Deskripsi
Modul ini mengatur master data aturan poin (`activity_rules`) dan katalog hadiah (`rewards`), termasuk manajemen stok hadiah fisik secara atomic.

### Fitur
- Master aturan poin untuk setiap aktivitas member
- Katalog hadiah fisik dan non-fisik
- Manajemen stok hadiah fisik dengan endpoint atomic `decrement-stock`
- Seeder 35.000 log aktivitas poin (`point_activity_logs`)
- Scale horizontal: Nginx sebagai load balancer ke beberapa instance `app`

### Arsitektur
```
Controllers : app/Http/Controllers/Api/ActivityRuleController.php
              app/Http/Controllers/Api/RewardController.php
Services    : app/Services/ActivityRuleService.php
              app/Services/RewardService.php
Repositories: app/Repositories/Contracts/ActivityRuleRepositoryInterface.php
              app/Repositories/Eloquent/ActivityRuleRepository.php
              app/Repositories/Contracts/RewardRepositoryInterface.php
              app/Repositories/Eloquent/RewardRepository.php
```

### Endpoint API

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/activity-rules` | Daftar semua activity rules |
| POST | `/api/activity-rules` | Buat activity rule baru |
| PUT | `/api/activity-rules/{id}` | Update activity rule |
| DELETE | `/api/activity-rules/{id}` | Hapus activity rule |
| GET | `/api/rewards` | Daftar semua rewards |
| POST | `/api/rewards` | Buat reward baru |
| PUT | `/api/rewards/{id}` | Update reward |
| DELETE | `/api/rewards/{id}` | Hapus reward |
| POST | `/api/rewards/{id}/decrement-stock` | Kurangi stok reward (atomic) |

### Testing
```bash
# Unit test
docker-compose exec app php artisan test tests/Unit/Services/ActivityRuleServiceTest.php
docker-compose exec app php artisan test tests/Unit/Services/RewardServiceTest.php

# Feature test
docker-compose exec app php artisan test tests/Feature/ActivityRuleApiTest.php
docker-compose exec app php artisan test tests/Feature/RewardApiTest.php
```

### Scale Up / Down
```bash
# Scale up (saat traffic tinggi)
docker compose up -d --scale app=3

# Scale down (kembali normal)
docker compose up -d --scale app=1
```

---

## 🟡 Modul 2 — Reward Processing Core

> **Dikerjakan oleh**: Team Member 2

### Deskripsi
Modul ini menangani inti pemrosesan reward: penambahan poin otomatis dengan kalkulasi multiplier, validasi saldo poin, dan penanganan race condition menggunakan Pessimistic Locking.

### Fitur
- Tambah poin otomatis dengan kalkulasi multiplier tier & user
- Validasi saldo poin sebelum penukaran
- Race condition handling dengan Pessimistic Locking
- Logging lengkap setiap transaksi (earn, redeem, referral, expire, adjustment)

### Kalkulasi Poin

```
Final Points = Base Points × Tier Multiplier × User Multiplier

Contoh:
  Base Points     : 10
  Tier (Gold)     : 1.5x
  User Multiplier : 1.5x
  Final           : 10 × 1.5 × 1.5 = 22 points
```

### Race Condition Handling
```php
// Pessimistic Locking untuk atomic operations
$balance = PointBalance::where('user_id', $userId)
    ->lockForUpdate()   // SELECT ... FOR UPDATE
    ->first();

// Transaction dengan retry
DB::transaction(function () { ... }, max_attempts: 3);
```

### Arsitektur
```
Controllers : app/Http/Controllers/Api/RewardProcessingController.php
Services    : app/Services/RewardProcessingService.php
Repositories: app/Repositories/Contracts/PointBalanceRepositoryContract.php
              app/Repositories/PointBalanceRepository.php
```

### Endpoint API

| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/rewards/add-points` | Tambah poin otomatis |
| POST | `/api/rewards/redeem` | Tukar poin |
| GET | `/api/rewards/balance/{userId}` | Lihat saldo poin |
| POST | `/api/rewards/validate-balance` | Validasi kecukupan saldo |
| GET | `/api/rewards/logs/{userId}` | Riwayat poin user |
| GET | `/api/rewards/all-logs` | Semua log (dengan filter) |

### Testing
```bash
# Manual test dengan curl
curl -X POST http://localhost:8000/api/rewards/add-points \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "point_rule_id": 1, "metadata": {"order_id": "ORD-001"}}'

# Cek saldo
curl http://localhost:8000/api/rewards/balance/1

# Race condition test (concurrent requests)
ab -n 100 -c 10 -p data.json -T application/json \
  http://localhost:8000/api/rewards/redeem
```

---

## 🟣 Modul 3 — Autentikasi Member (JWT) & E-Statement

> **Dikerjakan oleh**: Team Member 3 (Hafizhah)

### Deskripsi
Modul ini menangani autentikasi member menggunakan JSON Web Token (JWT), riwayat poin (e-statement), dan masa berlaku poin.

### Fitur
- Register & Login dengan JWT token
- Logout dan invalidate token
- Profil user terautentikasi (`/api/me`)
- E-Statement: riwayat perolehan dan penggunaan poin
- Masa berlaku poin: poin berlaku 1 tahun dari tanggal perolehan
- Dashboard UI terintegrasi dengan modul lain

### Instalasi JWT
```bash
# Install package
composer require tymon/jwt-auth

# Publish config & generate secret
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

### Konfigurasi `config/auth.php`
```php
'guards' => [
    'api' => [
        'driver'   => 'jwt',
        'provider' => 'users',
    ],
],
```

### Arsitektur
```
Controllers : app/Http/Controllers/API/AuthController.php
              app/Http/Controllers/API/StatementController.php
Services    : app/Services/AuthService.php
              app/Services/PointStatementService.php
Repositories: app/Repositories/Contracts/UserRepositoryInterface.php
              app/Repositories/Eloquent/UserRepository.php
Middleware  : app/Http/Middleware/JwtMiddleware.php
Requests    : app/Http/Requests/RegisterRequest.php
              app/Http/Requests/LoginRequest.php
              app/Http/Requests/StatementRequest.php
```

### Endpoint API

| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/api/register` | ❌ | Registrasi member baru |
| POST | `/api/login` | ❌ | Login & dapatkan token |
| POST | `/api/logout` | ✅ JWT | Logout & invalidate token |
| GET | `/api/me` | ✅ JWT | Profil user yang login |
| GET | `/api/points/balance` | ✅ JWT | Saldo poin & info expiry |
| GET | `/api/statement` | ✅ JWT | Riwayat poin (e-statement) |

### Testing
```bash
# Register
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@mail.com","password":"password","password_confirmation":"password"}'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@mail.com","password":"password"}'

# Cek profil (gunakan token dari login)
curl http://localhost:8000/api/me \
  -H "Authorization: Bearer <TOKEN>"
```

---

## 🟢 Modul 4 — Membership Tiering, Referral & Redemption

> **Dikerjakan oleh**: Team Member 4

### Deskripsi
Modul ini mengelola sistem membership tier otomatis, program referral, activity trigger dengan multiplier tier, dan reward redemption terintegrasi.

### Fitur
- Membership Tier (Bronze, Silver, Gold) dengan rentang poin dan multiplier
- Auto-assign & recalculate tier berdasarkan total poin user
- Generate & apply referral code (validasi anti self-referral & anti duplicate)
- Bonus poin otomatis untuk referrer dan referee
- Activity trigger dengan multiplier tier (integrasi Modul 1)
- Reward redemption: cek poin, atomic stock decrement, simpan histori
- Hapus tier dengan auto-null `membership_tier_id` pada user

### Arsitektur
```
Controllers : app/Http/Controllers/Api/MembershipController.php
Services    : app/Services/MembershipTierService.php
              app/Services/ReferralService.php
              app/Services/MembershipActivityService.php
              app/Services/RewardRedemptionService.php
Repositories: app/Repositories/Eloquent/MembershipTierRepository.php
              app/Repositories/Eloquent/UserRepository.php
              app/Repositories/Eloquent/ReferralLogRepository.php
              app/Repositories/Eloquent/RewardRedemptionRepository.php
Models baru : app/Models/MembershipTier.php
              app/Models/ReferralLog.php
```

### Migrations Modul 4
```
database/migrations/2026_04_16_120000_create_membership_tiers_table.php
database/migrations/2026_04_16_120100_add_membership_and_referral_columns_to_users_table.php
database/migrations/2026_04_16_120200_create_referral_logs_table.php
```

### Endpoint API

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/membership/tiers` | Daftar semua tier |
| POST | `/api/membership/tiers` | Buat tier baru |
| PUT | `/api/membership/tiers/{id}` | Update tier |
| DELETE | `/api/membership/tiers/{id}` | Hapus tier |
| POST | `/api/membership/tiers/recalculate` | Recalculate tier user |
| POST | `/api/membership/referrals/generate` | Generate referral code |
| POST | `/api/membership/referrals/apply` | Apply referral code |
| POST | `/api/membership/activity/trigger` | Trigger activity + multiplier |
| POST | `/api/membership/rewards/{id}/redeem` | Redeem reward |

### Testing
```bash
# Jalankan semua feature test Modul 4
docker-compose exec app php artisan test tests/Feature/MembershipModuleApiTest.php
```

---

## 🖥️ Dashboard UI

Akses `http://localhost:8000` untuk membuka dashboard uji lokal yang menampilkan:

| Section | Konten |
|---|---|
| **Modul 1** | Stats Activity Rules, Rewards, Activity Logs + Quick API Links |
| **Modul 3** | Stats Users & Poin, Form Register, Form Login, Test Protected Endpoints |
| **Modul 4** | Data Helper (user/reward/activity tables), Form semua action endpoint |

---

## 🔗 Semua API Routes

### Public
```
POST   /api/register
POST   /api/login
GET    /api/activity-rules
POST   /api/activity-rules
PUT    /api/activity-rules/{id}
DELETE /api/activity-rules/{id}
GET    /api/rewards
POST   /api/rewards
PUT    /api/rewards/{id}
DELETE /api/rewards/{id}
POST   /api/rewards/{id}/decrement-stock
POST   /api/activity/trigger
POST   /api/rewards/add-points
POST   /api/rewards/redeem
GET    /api/rewards/balance/{userId}
POST   /api/rewards/validate-balance
GET    /api/rewards/logs/{userId}
GET    /api/rewards/all-logs
GET    /api/membership/tiers
POST   /api/membership/tiers
PUT    /api/membership/tiers/{id}
DELETE /api/membership/tiers/{id}
POST   /api/membership/tiers/recalculate
POST   /api/membership/referrals/generate
POST   /api/membership/referrals/apply
POST   /api/membership/activity/trigger
POST   /api/membership/rewards/{id}/redeem
```

### Protected (JWT)
```
POST   /api/logout
GET    /api/me
GET    /api/points/balance
GET    /api/statement
GET    /api/statement/export-pdf
```

---

## 🐳 Docker Commands

```bash
# Start semua container
docker-compose up -d

# Lihat logs
docker-compose logs -f app

# Masuk ke app container
docker-compose exec app bash

# Masuk ke database
docker-compose exec db psql -U postgres -d loyalty_db

# Stop container
docker-compose down

# Reset total (hapus volume)
docker-compose down -v
```

---

## 🛠️ Troubleshooting

**Database tidak terkoneksi**
```bash
docker-compose exec app php artisan tinker
> DB::connection()->getPdo();
```

**Migration error**
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

**Permission denied**
```bash
docker-compose exec -u root app chown -R www-data:www-data /app
```

**Port sudah digunakan**
Ubah port di `docker-compose.yml` atau `.env`

---

## ✅ Status Pengerjaan

| Modul | Deskripsi | Status |
|---|---|---|
| Modul 1 | Activity Rules & Rewards | ✅ Done |
| Modul 2 | Reward Processing Core | ✅ Done |
| Modul 3 | Autentikasi JWT & E-Statement | ✅ Done |
| Modul 4 | Membership Tiering & Referral | ✅ Done |

---

## 📁 File Penting

| File | Keterangan |
|---|---|
| `API_DOCUMENTATION.md` | Dokumentasi API lengkap |
| `postman_collection.json` | Postman collection untuk testing |
| `docker-compose.yml` | Docker orchestration |
| `routes/api.php` | Definisi semua API routes |
| `app/Services/RewardProcessingService.php` | Business logic utama Modul 2 |
| `app/Services/AuthService.php` | Business logic autentikasi Modul 3 |
| `app/Services/MembershipTierService.php` | Business logic tier Modul 4 |
| `resources/views/welcome.blade.php` | Dashboard UI |

---

## 🤝 Kontribusi & Workflow Git

```bash
# Pull latest
git pull origin main

# Buat branch baru
git checkout -b feature/nama-fitur

# Develop & test
# ...

# Commit
git add .
git commit -m "feat: deskripsi perubahan"

# Push
git push origin feature/nama-fitur

# Buat Pull Request di GitHub
```

---

**Version**: 1.0.0 &nbsp;|&nbsp; **Team**: Tim 9 Backend Development &nbsp;|&nbsp; **Semester**: 8