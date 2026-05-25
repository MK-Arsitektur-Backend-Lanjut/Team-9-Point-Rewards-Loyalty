# Team 9: Point Rewards & Loyalty System - Module 2 Documentation

## Modul 2: Reward Processing (Core)

Backend API untuk menangani otomasi tambah poin, validasi sisa poin, dan penanganan race condition dalam sistem loyalitas.

### Fitur Utama

✅ **API Tambah Poin Otomatis**
- Penambahan poin dengan kalkulasi multiplier berdasarkan tier membership dan user multiplier
- Logging otomatis setiap transaksi
- Dukungan metadata tambahan

✅ **Validasi Sisa Poin**
- Validasi ketersediaan poin sebelum penukaran
- Mempertimbangkan locked points
- Error handling untuk insufficient balance

✅ **Penanganan Race Condition**
- Pessimistic Locking (SELECT FOR UPDATE)
- Database transaction dengan retry mechanism
- Atomic operations untuk consistency

### Teknologi

- **Framework**: Laravel 10
- **Database**: PostgreSQL 15
- **Server**: Nginx + PHP-FPM
- **Container**: Docker & Docker Compose
- **Pattern**: Repository Pattern
- **Locking**: Pessimistic Lock (SELECT FOR UPDATE)

## Setup & Installation

### Prerequisites

- Docker & Docker Compose
- Windows (atau Linux/MacOS)

### Steps

1. **Clone/Setup Project**
```bash
cd "d:\KULYEAH\SEMT 8\Backend\Team9"
```

2. **Copy Environment File**
```bash
copy .env.example .env
```

3. **Build & Start Docker**
```bash
docker-compose up -d --build
```

4. **Install Dependencies**
```bash
docker-compose exec app composer install
```

5. **Generate Application Key**
```bash
docker-compose exec app php artisan key:generate
```

6. **Run Migrations**
```bash
docker-compose exec app php artisan migrate
```

7. **Seed Database** (Create 35k+ logs & 10k+ referrals)
```bash
docker-compose exec app php artisan db:seed
```

### Akses Aplikasi

- **API Base URL**: http://localhost:8000/api
- **Database**: localhost:5432
  - Username: postgres
  - Password: secret
  - Database: loyalty_db

## API Endpoints

### 1. Add Points (Automatic dengan Multiplier)

**POST** `/api/rewards/add-points`

Tambahkan poin otomatis dengan kalkulasi multiplier tier.

**Request Body:**
```json
{
  "user_id": 1,
  "point_rule_id": 1,
  "metadata": {
    "order_id": "ORD-001",
    "amount": 50000
  }
}
```

**Response Success (200):**
```json
{
  "success": true,
  "user_id": 1,
  "points_added": 24,
  "new_balance": 124,
  "log_id": 1,
  "message": "Points added successfully"
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Point rule not found or inactive"
}
```

### 2. Validate Balance

**POST** `/api/rewards/validate-balance`

Validasi apakah user memiliki poin cukup untuk penukaran.

**Request Body:**
```json
{
  "user_id": 1,
  "required_points": 100
}
```

**Response:**
```json
{
  "success": true,
  "user_id": 1,
  "required_points": 100,
  "has_sufficient_balance": true
}
```

### 3. Redeem Points

**POST** `/api/rewards/redeem`

Tukarkan poin dengan validasi balance dan race condition handling.

**Request Body:**
```json
{
  "user_id": 1,
  "points_to_redeem": 50,
  "description": "Tukar voucher diskon",
  "metadata": {
    "voucher_id": "VOUCHER-001"
  }
}
```

**Response Success (200):**
```json
{
  "success": true,
  "user_id": 1,
  "points_redeemed": 50,
  "new_balance": 74,
  "log_id": 2,
  "message": "Points redeemed successfully"
}
```

**Response Error (422):**
```json
{
  "success": false,
  "message": "Insufficient points. Available: 30, Required: 50"
}
```

### 4. Get Balance

**GET** `/api/rewards/balance/{user_id}`

Dapatkan informasi saldo poin user.

**Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "current_balance": 124,
    "available_balance": 124,
    "locked_points": 0,
    "expired_points": 0,
    "lifetime_points": 250
  }
}
```

### 5. Get Point Logs

**GET** `/api/rewards/logs/{user_id}?limit=50&offset=0`

Dapatkan riwayat point logs user.

**Response:**
```json
{
  "success": true,
  "user_id": 1,
  "total": 2,
  "data": [
    {
      "id": 2,
      "user_id": 1,
      "points_amount": -50,
      "transaction_type": "redeem",
      "description": "Tukar voucher diskon",
      "reference_id": "redeem_123456",
      "status": "completed",
      "created_at": "2024-01-15 10:30:00"
    },
    {
      "id": 1,
      "user_id": 1,
      "points_amount": 24,
      "transaction_type": "earn",
      "description": "1 poin per 1000 rupiah pembelian",
      "reference_id": "earn_123456",
      "status": "completed",
      "created_at": "2024-01-15 10:00:00"
    }
  ]
}
```

### 6. Get All Logs with Filter

**GET** `/api/rewards/all-logs?user_id=1&transaction_type=earn&status=completed&per_page=50&page=1`

Dapatkan semua point logs dengan berbagai filter.

**Query Parameters:**
- `user_id` - Filter by user
- `transaction_type` - earn, redeem, referral, expire, adjustment
- `status` - completed, pending, failed
- `start_date` - Format: YYYY-MM-DD
- `end_date` - Format: YYYY-MM-DD
- `per_page` - Default: 50
- `page` - Default: 1

**Response:**
```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 1000,
    "per_page": 50,
    "current_page": 1,
    "last_page": 20
  }
}
```

## Race Condition Handling

### Mechanism

Modul ini menggunakan **Pessimistic Locking** dengan `SELECT FOR UPDATE` untuk mencegah race condition:

```php
// Di PointBalanceRepository
public function getByUserIdWithLock(int $userId): ?object
{
    return PointBalance::where('user_id', $userId)
        ->lockForUpdate()  // SELECT ... FOR UPDATE
        ->first();
}
```

### Flow

1. **Lock Balance**: Ambil & lock point balance user
2. **Transaction**: Semua operasi dalam database transaction
3. **Atomic Update**: Update balance atomically
4. **Log Entry**: Buat log transaction
5. **Release Lock**: Transaction selesai, lock release otomatis

### Retry Mechanism

Database transaction di-configure dengan `max_attempts: 3` untuk handle temporary lock contention.

```php
DB::transaction(function () {
    // Operations
}, max_attempts: 3);
```

## Database Schema

### Tables

```
users
├── id (PK)
├── name
├── email (UNIQUE)
├── password
├── membership_tier (bronze|silver|gold|platinum)
├── referral_code (UNIQUE)
├── referred_by_user_id (FK)
└── point_multiplier (decimal 3,2)

point_balances
├── id (PK)
├── user_id (FK, UNIQUE)
├── current_balance (bigint)
├── expired_points (bigint)
├── locked_points (bigint) <- Untuk race condition handling
└── lifetime_points (bigint)

point_logs
├── id (PK)
├── user_id (FK)
├── point_rule_id (FK)
├── points_amount (bigint)
├── transaction_type (enum)
├── description
├── reference_id
├── metadata (json)
├── status (enum)
└── created_at

point_rules
├── id (PK)
├── rule_name (UNIQUE)
├── description
├── base_points
├── multiplier_rules (json)
├── validity_days
└── is_active

referrals
├── id (PK)
├── referred_by_user_id (FK)
├── referred_user_id (FK)
├── referral_code
├── points_awarded
├── status (active|inactive)
└── created_at
```

## Point Calculation Formula

$$\text{Final Points} = \text{Base Points} \times \text{Tier Multiplier} \times \text{User Multiplier}$$

**Tier Multipliers:**
- Bronze: 1.0x
- Silver: 1.2x
- Gold: 1.5x
- Platinum: 2.0x

**Contoh:**
- Base Points: 10
- Tier: Gold (1.5x)
- User Multiplier: 1.5x
- **Final: 10 × 1.5 × 1.5 = 22.5 ≈ 22 points**

## Data Seeding

Database sudah di-seed dengan:

✅ **1000 Users** dengan berbagai tier dan multiplier
✅ **35,000+ Point Logs** dengan transaksi earn/redeem/referral
✅ **10,000+ Referral Records** untuk testing referral system
✅ **6 Point Rules** dengan multiplier tier

### Jalankan Seeding Manual

```bash
# Seed semua
docker-compose exec app php artisan db:seed

# Seed specific seeder
docker-compose exec app php artisan db:seed --class=UserSeeder
```

## Repository Pattern

Semua akses data menggunakan Repository Pattern untuk separation of concerns:

**Contract**: `App\Repositories\Contracts\PointBalanceRepositoryContract`
**Implementation**: `App\Repositories\PointBalanceRepository`

**Keuntungan:**
- Loose coupling
- Easy testing
- Easy to swap implementation
- Business logic terpisah dari database logic

## Testing Race Condition

### Concurrent Request Testing

**Tools**: Apache Bench atau stress test dengan script

```bash
# Tester simultaneous requests
ab -n 100 -c 10 -p data.json -T application/json http://localhost:8000/api/rewards/redeem
```

### Scenario

1. **User Balance**: 1000 points
2. **10 Concurrent Requests**: Masing-masing redeem 100 points
3. **Expected Result**: 
   - ✅ First 10 succeeds
   - ❌ Request 11+ failed dengan "Insufficient points"
   - ✅ Final balance: 0 points

## Development Commands

### Docker Commands

```bash
# Start
docker-compose up -d

# Stop
docker-compose down

# View logs
docker-compose logs -f app

# SSH ke container
docker-compose exec app bash
```

### Artisan Commands

```bash
# Migrations
php artisan migrate
php artisan migrate:fresh
php artisan migrate:rollback

# Seeding
php artisan db:seed
php artisan db:seed --class=PointLogSeeder

# Tinker (Interactive Shell)
php artisan tinker
```

## Error Handling

### Standard Error Responses

**422 Unprocessable Entity**
```json
{
  "success": false,
  "message": "Insufficient points. Available: 30, Required: 50"
}
```

**422 Invalid Rule**
```json
{
  "success": false,
  "message": "Point rule not found or inactive"
}
```

**500 Internal Server Error**
```json
{
  "success": false,
  "message": "Failed to add points"
}
```

Semua error di-log di `storage/logs/laravel.log`

## Performance Considerations

1. **Database Indexes**: 
   - FK indexes untuk faster joins
   - Composite indexes untuk common queries
   - Index on user_id + created_at untuk efficient filtering

2. **Pagination**: 
   - Logs menggunakan pagination (limit 50 default)
   - Efficient cursor-based pagination untuk large datasets

3. **Locking Strategy**:
   - Pessimistic lock hanya pada point_balances table
   - Lock duration minimal untuk minimize contention

4. **Batch Operations**:
   - Seeders menggunakan batch insert untuk performance
   - Insert 1000 records per batch

## Troubleshooting

### Connection Issues

```bash
# Cek database connection
docker-compose exec app php artisan tinker
DB::connection()->getPdo();
```

### Migration Errors

```bash
# Rollback & re-run
docker-compose exec app php artisan migrate:fresh --seed
```

### Permission Issues

```bash
# Jalankan di dalam container
docker-compose exec -u root app chown -R www-data:www-data /app
```

## Team Members

- **Module 2 Developer**: (Your Name)
- **Testing**: Team Quality Assurance
- **Documentation**: Technical Writer

## References

- Laravel Documentation: https://laravel.com/docs/10
- Repository Pattern: https://editorconfig.org/
- Race Condition Prevention: https://dev.mysql.com/doc/
- PostgreSQL Locking: https://www.postgresql.org/docs/15/explicit-locking.html

---

**Last Updated**: January 2024
**Version**: 1.0.0
**Status**: Production Ready ✅
