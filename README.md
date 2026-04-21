# Team 9: Point Rewards & Loyalty System - Module 2

## Deskripsi Modul

Backend API untuk **Reward Processing - Core** yang menangani:

- ✅ **API Tambah Poin Otomatis** - Dengan kalkulasi multiplier berdasarkan tier dan user multiplier
- ✅ **Validasi Sisa Poin** - Memastikan user memiliki poin cukup untuk penukaran
- ✅ **Penanganan Race Condition** - Menggunakan Pessimistic Locking untuk consistency

## Quick Start

### 1. Setup Environment

```bash
cd "d:\KULYEAH\SEMT 8\Backend\Team9"
copy .env.example .env
```

### 2. Start Docker

```bash
docker-compose up -d --build
```

### 3. Setup Database

```bash
# Install dependencies
docker-compose exec app composer install

# Generate app key
docker-compose exec app php artisan key:generate

# Run migrations
docker-compose exec app php artisan migrate

# Seed database (35k+ logs, 10k+ referrals)
docker-compose exec app php artisan db:seed
```

### 4. Test API

```bash
# Base URL
http://localhost:8000/api/rewards/

# Check health
curl http://localhost:8000/api/rewards/balance/1

# Import Postman Collection
postman_collection.json
```

## Struktur Folder

```
Team9/
├── app/
│   ├── Models/                  # Data models
│   ├── Repositories/            # Repository pattern
│   │   ├── Contracts/          # Repository interfaces
│   │   └── *.php               # Repository implementations
│   ├── Services/               # Business logic
│   ├── Http/
│   │   ├── Controllers/        # API Controllers
│   │   └── Requests/           # Form requests (validation)
│   ├── Exceptions/             # Custom exceptions
│   └── Providers/              # Service providers
├── database/
│   ├── migrations/             # Database migrations
│   └── seeders/                # Database seeders
├── routes/
│   └── api.php                 # API routes
├── bootstrap/                  # Bootstrap files
├── storage/                    # Logs & cache
├── public/                     # Public assets
├── docker-compose.yml          # Docker orchestration
├── Dockerfile                  # App container
├── nginx.conf                  # Nginx config
├── composer.json               # PHP dependencies
└── API_DOCUMENTATION.md        # Full API docs
```

## Key Features

### 1. Repository Pattern

Implementasi Repository Pattern untuk separation of concerns:

```php
// Interface
interface PointBalanceRepositoryContract { ... }

// Implementation
class PointBalanceRepository implements PointBalanceRepositoryContract { ... }

// Service menggunakan interface
public function __construct(PointBalanceRepositoryContract $repo) { ... }
```

### 2. Race Condition Handling

Menggunakan Pessimistic Locking untuk atomic operations:

```php
// Lock point balance user
$balance = PointBalance::where('user_id', $userId)
    ->lockForUpdate()  // SELECT ... FOR UPDATE
    ->first();

// Transaction dengan retry
DB::transaction(function () { ... }, max_attempts: 3);
```

### 3. Point Calculation dengan Multiplier

$$\text{Final Points} = \text{Base} × \text{Tier Multiplier} × \text{User Multiplier}$$

```
Contoh:
Base Points: 10
Tier: Gold (1.5x)
User Multiplier: 1.5x
Final: 10 × 1.5 × 1.5 = 22.5 → 22 points
```

### 4. Comprehensive Logging

Setiap transaksi di-log sesuai jenisnya:
- earn: Poin diperoleh
- redeem: Poin ditukar
- referral: Poin dari referral
- expire: Poin kadaluarsa
- adjustment: Penyesuaian manual

## API Endpoints

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/rewards/add-points` | Tambah poin otomatis |
| POST | `/api/rewards/redeem` | Tukar poin |
| GET | `/api/rewards/balance/{userId}` | Lihat saldo |
| POST | `/api/rewards/validate-balance` | Validasi saldo |
| GET | `/api/rewards/logs/{userId}` | Lihat riwayat user |
| GET | `/api/rewards/all-logs` | Lihat semua log (filter) |

## Data Seeding

Database dilengkapi dengan data dummy:

- **1000 Users** (berbagai tier: bronze, silver, gold, platinum)
- **35,000+ Point Logs** (earn, redeem, referral transactions)
- **10,000+ Referral Records** (untuk testing referral system)
- **6 Point Rules** (dengan multiplier tier)

## Database

**Engine**: PostgreSQL 15
**Credentials**: 
- Host: localhost:5432
- Username: postgres
- Password: secret
- Database: loyalty_db

**Tables**: users, point_balances, point_logs, point_rules, referrals

## Testing

### Manual Testing dengan Curl

```bash
# Add points
curl -X POST http://localhost:8000/api/rewards/add-points \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "point_rule_id": 1,
    "metadata": {"order_id": "ORD-001"}
  }'

# Validate balance
curl -X POST http://localhost:8000/api/rewards/validate-balance \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "required_points": 100}'

# Get balance
curl http://localhost:8000/api/rewards/balance/1
```

### Postman Testing

1. Import `postman_collection.json` ke Postman
2. Gunakan pre-defined requests
3. Test semua endpoints

### Race Condition Testing

```bash
# Concurrent requests testing (jumlah request > user balance)
ab -n 100 -c 10 -p data.json -T application/json \
  http://localhost:8000/api/rewards/redeem
```

## Docker Commands

```bash
# Start containers
docker-compose up -d

# View logs
docker-compose logs -f app

# SSH ke app container
docker-compose exec app bash

# SSH ke db container
docker-compose exec db psql -U postgres -d loyalty_db

# Stop containers
docker-compose down

# Remove everything (including volumes)
docker-compose down -v
```

## Troubleshooting

### Database tidak terkoneksi
```bash
docker-compose exec app php artisan tinker
> DB::connection()->getPdo();
```

### Migration error
```bash
docker-compose exec app php artisan migrate:fresh --seed
```

### Permission denied
```bash
docker-compose exec -u root app chown -R www-data:www-data /app
```

### Port sudah digunakan
Ubah port di `docker-compose.yml` atau `.env`

## Performance Notes

- **Pessimistic Locking**: Minimal lock duration untuk reduce contention
- **Database Indexes**: Optimized queries dengan composite indexes
- **Batch Seeding**: 1000 records per batch untuk efficiency
- **Pagination**: Default limit 50 untuk large datasets

## Development Workflow

1. Develop locally di machine sendiri
2. Commit ke git: `git add . && git commit -m "message"`
3. Push ke repository
4. Coordinate dengan team members
5. Merge ke main branch

## File Penting

- `API_DOCUMENTATION.md` - Dokumentasi API lengkap
- `postman_collection.json` - Postman collection untuk testing
- `docker-compose.yml` - Docker orchestration
- `routes/api.php` - API routes definition
- `app/Services/RewardProcessingService.php` - Business logic utama
- `app/Repositories/*` - Data access layer

## Kontribusi

1. Pull latest: `git pull origin main`
2. Create branch: `git checkout -b feature/xxx`
3. Develop & test
4. Commit: `git commit -m "Add xxx"`
5. Push: `git push origin feature/xxx`
6. Create PR di GitHub

## Status

✅ **Produksi Ready**
- Semua requirements terpenuhi
- Race condition handling implemented
- Data seeding complete
- API documentation lengkap
- Docker setup complete

## Next Steps

- ✅ Modul 2 (Reward Processing) - DONE
- ⏳ Modul 1 (Activity Rules & Rewards) - By Team Member 1
- ⏳ Modul 3 (User & Statement) - By Team Member 3
- ⏳ Modul 4 (Membership Tiering & Referral) - By Team Member 4

## Contact & Support

For issues atau questions, hubungi team members atau technical lead.

---

**Version**: 1.0.0  
**Last Updated**: January 2024  
**Team**: Tim 9 Backend Development

---

## Loyalty Backend - Modul 1

Implementasi `Modul Activity Rules & Rewards` menggunakan Laravel + Docker + Repository Pattern.

### Fitur Modul 1

- Master aturan poin (`activity_rules`) untuk aktivitas member.
- Katalog hadiah (`rewards`) termasuk hadiah fisik dan non-fisik.
- Manajemen stok hadiah fisik dengan endpoint atomic `decrement-stock`.
- Data performa seeder `35.000` log aktivitas poin (`point_activity_logs`).

### Arsitektur

- `Repository Pattern` dipisah di:
  - `app/Repositories/Contracts`
  - `app/Repositories/Eloquent`
- `Business Service`:
  - `app/Services/ActivityRuleService.php`
  - `app/Services/RewardService.php`
- `REST API Controller`:
  - `app/Http/Controllers/Api/ActivityRuleController.php`
  - `app/Http/Controllers/Api/RewardController.php`
- Unit test repository delegation:
  - `tests/Unit/Services/ActivityRuleServiceTest.php`
  - `tests/Unit/Services/RewardServiceTest.php`

### Menjalankan dengan Docker

```bash
docker compose up -d --build
docker compose exec app composer install --no-scripts
docker compose exec app php artisan migrate --seed
```

Aplikasi tersedia di [http://localhost:8000](http://localhost:8000).

### Skala Minimal dan Elastis

- Default hemat resource (minimal scale): `app=1`.
- Saat trafik naik, scale up app tanpa ubah kode:

```bash
docker compose up -d --scale app=3
```

- Saat trafik normal kembali, turunkan skala:

```bash
docker compose up -d --scale app=1
```

- Nginx sudah dikonfigurasi sebagai load balancer ke beberapa instance `app`.

### Endpoint API Modul 1

- `GET /api/activity-rules`
- `POST /api/activity-rules`
- `PUT /api/activity-rules/{id}`
- `DELETE /api/activity-rules/{id}`
- `GET /api/rewards`
- `POST /api/rewards`
- `PUT /api/rewards/{id}`
- `DELETE /api/rewards/{id}`
- `POST /api/rewards/{id}/decrement-stock`

### Testing

Test yang sudah disiapkan:

- Service-to-Repository delegation:
  - `tests/Unit/Services/ActivityRuleServiceTest.php`
  - `tests/Unit/Services/RewardServiceTest.php`
- API feature test:
  - `tests/Feature/ActivityRuleApiTest.php`
  - `tests/Feature/RewardApiTest.php`

---

## Update Pengerjaan - Modul 4 (Membership Tiering & Referral)

Implementasi Modul 4 sudah ditambahkan di atas fondasi Modul 1 tanpa mengubah alur utama Modul 1.

### Fitur Modul 4

- Membership tier (`Bronze`, `Silver`, `Gold`) dengan rule rentang poin dan multiplier.
- Auto assign/recalculate tier user berdasarkan total poin.
- Referral flow:
  - Generate referral code.
  - Apply referral code (dengan validasi anti self-referral dan anti duplicate).
  - Bonus poin untuk referrer dan referee.
- Activity trigger dengan multiplier tier (integrasi data `activity_rules` dari Modul 1).
- Reward redemption terintegrasi:
  - Cek kecukupan poin user.
  - Atomic stock decrement reward.
  - Simpan histori redemption.
- Delete tier endpoint (hard delete) dan relasi user aman (`membership_tier_id` otomatis `null` saat tier dihapus).

### Endpoint API Modul 4

- `GET /api/membership/tiers`
- `POST /api/membership/tiers`
- `PUT /api/membership/tiers/{membershipTier}`
- `DELETE /api/membership/tiers/{membershipTier}`
- `POST /api/membership/tiers/recalculate`
- `POST /api/membership/referrals/generate`
- `POST /api/membership/referrals/apply`
- `POST /api/membership/activity/trigger`
- `POST /api/membership/rewards/{reward}/redeem`

### Struktur Kode Modul 4

- Controller:
  - `app/Http/Controllers/Api/MembershipController.php`
- Service:
  - `app/Services/MembershipTierService.php`
  - `app/Services/ReferralService.php`
  - `app/Services/MembershipActivityService.php`
  - `app/Services/RewardRedemptionService.php`
- Repository Contracts & Eloquent:
  - `app/Repositories/Contracts/*Membership*`
  - `app/Repositories/Contracts/UserRepositoryInterface.php`
  - `app/Repositories/Contracts/ReferralLogRepositoryInterface.php`
  - `app/Repositories/Contracts/RewardRedemptionRepositoryInterface.php`
  - `app/Repositories/Eloquent/MembershipTierRepository.php`
  - `app/Repositories/Eloquent/UserRepository.php`
  - `app/Repositories/Eloquent/ReferralLogRepository.php`
  - `app/Repositories/Eloquent/RewardRedemptionRepository.php`

### Perubahan Database (Modul 4)

- Migrations:
  - `database/migrations/2026_04_16_120000_create_membership_tiers_table.php`
  - `database/migrations/2026_04_16_120100_add_membership_and_referral_columns_to_users_table.php`
  - `database/migrations/2026_04_16_120200_create_referral_logs_table.php`
- Model baru:
  - `app/Models/MembershipTier.php`
  - `app/Models/ReferralLog.php`

### Seeder Tambahan

- Seeder default tier ditambahkan:
  - `database/seeders/MembershipTierSeeder.php`
- Sudah diregistrasikan ke:
  - `database/seeders/DatabaseSeeder.php`

### Dashboard Uji Lokal (Tanpa Postman)

- Halaman root `http://localhost:8000` sudah ditingkatkan menjadi dashboard uji Modul 4.
- Menampilkan helper data (`user_id`, `reward_id`, `activity_code`) dan form action endpoint utama.
- File terkait:
  - `routes/web.php`
  - `resources/views/welcome.blade.php`

### Testing Modul 4

- Feature tests Modul 4:
  - `tests/Feature/MembershipModuleApiTest.php`
- Seluruh skenario utama lulus:
  - Trigger activity + multiplier
  - Apply referral
  - Redeem reward
  - Delete tier
