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
