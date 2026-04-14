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
