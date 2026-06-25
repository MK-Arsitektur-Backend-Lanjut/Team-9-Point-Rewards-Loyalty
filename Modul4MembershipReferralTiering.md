# Laporan Modul 4: Membership, Referral & Tiering

**Mata Kuliah:** Arsitektur dan Pengembangan Backend
**Kelompok:** 9
**Anggota/Penanggung Jawab Modul 4:** Rafi AF

---

# 📖 Deskripsi Modul

Modul Membership, Referral & Tiering bertanggung jawab untuk mengelola sistem keanggotaan berjenjang (tiering), program referral antar pengguna, serta perhitungan ulang tingkatan membership berdasarkan akumulasi poin pada aplikasi loyalty.

Modul ini menyediakan layanan backend untuk:

* Pengelolaan tingkatan membership (Bronze, Silver, Gold, Platinum) berdasarkan total poin pengguna
* Sistem referral yang memungkinkan pengguna mengajak pengguna lain dan mendapatkan bonus poin
* Perhitungan otomatis tier membership ketika poin pengguna berubah
* Integrasi dengan modul lain melalui point multiplier berdasarkan tier membership

Selain itu, modul ini menjadi komponen kritis dalam sistem loyalty karena mengelola mekanisme reward berbasis referral yang membutuhkan penanganan concurrency dan race condition secara ketat.

---

# 🛠️ Pemenuhan Ketentuan Teknis

Sesuai dengan ketentuan yang diberikan oleh dosen pengampu, modul ini telah memenuhi seluruh persyaratan teknis yang ditentukan.

## 1. Penggunaan Framework Laravel

Backend dikembangkan menggunakan framework Laravel yang menyediakan struktur pengembangan API yang terorganisir, aman, dan mudah dipelihara.

Laravel digunakan untuk mengelola routing, middleware, validasi data, ORM Eloquent, Queue Jobs, serta integrasi database dan Redis.

Fitur Laravel yang dimanfaatkan secara khusus pada modul ini:

* **Eloquent ORM** untuk manajemen data membership tier, referral log, dan point activity log
* **Database Transactions** dengan Pessimistic Locking (`lockForUpdate`) untuk mencegah race condition
* **Queue Jobs** untuk pemrosesan asinkron pemberian bonus poin referral
* **Validation** untuk memastikan integritas data pada setiap endpoint API

---

## 2. Berjalan di Dalam Docker Container

Aplikasi telah dikonfigurasi menggunakan Docker sehingga seluruh layanan dapat dijalankan dalam container yang terisolasi.

Komponen yang dijalankan melalui Docker meliputi:

* **Laravel Application** (PHP 8.1 FPM)
* **Nginx Web Server** (Alpine)
* **MySQL 8.0 Database**
* **Redis 7.0** (Alpine)

Penggunaan Docker mempermudah deployment dan menjaga konsistensi environment antara pengembangan dan produksi.

---

## 3. Implementasi Repository Pattern

Untuk memisahkan logika bisnis dan akses data, modul ini menerapkan Repository Pattern secara konsisten.

Keuntungan penggunaan Repository Pattern:

* Mempermudah maintenance kode
* Memisahkan business logic dari database logic
* Meningkatkan fleksibilitas pengembangan
* Mempermudah proses testing

Struktur repository yang digunakan pada modul ini:

| Interface (Contract)                   | Implementasi (Eloquent)         |
| -------------------------------------- | ------------------------------- |
| `UserRepositoryInterface`              | `UserRepository`                |
| `MembershipTierRepositoryInterface`    | `MembershipTierRepository`      |
| `ReferralLogRepositoryInterface`       | `ReferralLogRepository`         |

Service layer (`ReferralService`, `MembershipTierService`) mengakses data secara eksklusif melalui repository interface, sehingga tidak bergantung langsung pada implementasi Eloquent.

---

## 4. Penggunaan Redis Cache

Redis digunakan sebagai mekanisme caching dan queue processing untuk meningkatkan performa sistem.

Berdasarkan konfigurasi sistem:

* Cache Driver : Redis
* Queue Driver : Redis
* Session Driver : Redis

Implementasi Redis pada modul ini meliputi:

* **Queue Processing**: Proses pemberian bonus poin referral dijalankan secara asinkron melalui Laravel Queue dengan driver Redis, sehingga endpoint `apply_referral` tidak memblokir response HTTP
* **Job Queue**: Class `ProcessReferralReward` mengimplementasikan `ShouldQueue` yang memproses pemberian poin, pencatatan log, dan recalculate tier di background

Penggunaan queue berbasis Redis ini secara signifikan mengurangi response time endpoint `apply_referral` karena operasi berat (database transaction dengan locking) diproses di background worker.

---

## 5. Database Indexing

Untuk meningkatkan performa query database, dilakukan optimasi menggunakan indexing pada tabel-tabel yang terkait modul ini.

### Tabel `membership_tiers`

#### Primary Key

```sql
PRIMARY KEY (id)
```

Digunakan untuk mempercepat pencarian berdasarkan ID tier.

#### Unique Index

```sql
UNIQUE INDEX membership_tiers_code_unique (code)
```

Digunakan untuk memastikan setiap tier memiliki kode unik sekaligus mempercepat pencarian berdasarkan kode tier.

#### Composite Index

```sql
INDEX membership_tiers_is_active_min_points_max_points_index (is_active, min_points, max_points)
```

Digunakan untuk mempercepat query `resolveTierByPoints` yang melakukan filtering berdasarkan:

* Status aktif tier
* Range poin minimum dan maksimum

Index ini sangat penting karena digunakan setiap kali sistem menghitung ulang tier membership pengguna.

### Tabel `users`

#### Unique Index

```sql
UNIQUE INDEX users_referral_code_unique (referral_code)
```

Digunakan untuk memastikan setiap referral code bersifat unik dan mempercepat pencarian pengguna berdasarkan referral code saat proses apply referral.

#### Foreign Key Index

```sql
FOREIGN KEY (membership_tier_id) REFERENCES membership_tiers(id)
FOREIGN KEY (referred_by_user_id) REFERENCES users(id)
```

Digunakan untuk menjaga integritas relasi antar tabel dan mempercepat join query.

### Tabel `referral_logs`

#### Unique Index

```sql
UNIQUE INDEX referral_logs_referee_user_id_unique (referee_user_id)
```

Digunakan untuk memastikan setiap pengguna hanya dapat menjadi referee satu kali, sekaligus mempercepat pengecekan duplikasi referral.

#### Composite Index

```sql
INDEX referral_logs_referrer_user_id_rewarded_at_index (referrer_user_id, rewarded_at)
```

Digunakan untuk mempercepat query riwayat referral berdasarkan referrer dan waktu pemberian reward.

---

# Fitur dan Endpoint API

Modul Membership, Referral & Tiering menyediakan beberapa layanan API utama sebagai berikut.

## Manajemen Membership Tier

### Menampilkan Daftar Tier

```http
GET /api/membership/tiers
```

Digunakan untuk mengambil seluruh daftar tingkatan membership yang tersedia beserta informasi min/max poin dan point multiplier.

### Menambahkan Tier

```http
POST /api/membership/tiers
```

Digunakan untuk menambahkan tingkatan membership baru ke dalam sistem.

### Memperbarui Tier

```http
PUT /api/membership/tiers/{id}
```

Digunakan untuk memperbarui informasi tingkatan membership.

### Menghapus Tier

```http
DELETE /api/membership/tiers/{id}
```

Digunakan untuk menghapus tingkatan membership dari sistem.

### Recalculate Tier Pengguna

```http
POST /api/membership/tiers/recalculate
```

Digunakan untuk menghitung ulang tier membership pengguna berdasarkan total poin terkini.

---

## Sistem Referral

### Generate Referral Code

```http
POST /api/membership/referrals/generate
```

Digunakan untuk menghasilkan kode referral unik bagi pengguna. Kode referral menggunakan format `RF{userId}{randomHex}`.

### Apply Referral Code

```http
POST /api/membership/referrals/apply
```

Digunakan untuk menerapkan kode referral. Proses ini memberikan:

* **50 poin** bonus kepada referrer (pemilik kode)
* **25 poin** bonus kepada referee (pengguna baru)

Proses pemberian poin dijalankan secara asinkron melalui Queue Job (`ProcessReferralReward`) untuk menghindari blocking pada response HTTP.

---

# Penanganan Concurrency & Race Condition

Modul referral memiliki potensi race condition yang tinggi karena:

* Banyak pengguna dapat mencoba apply referral code yang sama secara bersamaan
* Penambahan poin harus bersifat atomic untuk mencegah data inconsistency

Strategi yang diterapkan:

1. **Fast-Fail Validation**: Validasi awal dilakukan tanpa database lock untuk menolak request yang jelas invalid secepat mungkin
2. **Asynchronous Processing via Queue**: Endpoint `apply_referral` langsung mengembalikan response setelah validasi awal, sementara proses berat didelegasikan ke background job
3. **Pessimistic Locking (SELECT FOR UPDATE)**: Di dalam queue job, digunakan `lockForUpdate()` untuk mencegah race condition saat update poin pengguna
4. **Double-Apply Prevention**: Pengecekan `existsByReferee` di dalam transaction memastikan satu pengguna tidak dapat menerima referral lebih dari satu kali

---

# Analisis Stress Testing

Pengujian performa dilakukan menggunakan Grafana k6 untuk mengetahui kemampuan sistem dalam menangani banyak request secara bersamaan.

## Endpoint yang Diuji

Stress test dilakukan pada workflow lengkap (normal scenario) yang mencakup seluruh endpoint modul:

```http
POST /api/membership/referrals/generate   → Generate referral code
POST /api/register                        → Registrasi pengguna baru
POST /api/membership/referrals/apply      → Apply referral code
POST /api/membership/tiers/recalculate    → Recalculate tier membership
GET  /api/membership/tiers               → List semua tier
```

Workflow ini dipilih karena mensimulasikan alur penggunaan nyata: seorang pengguna membagikan kode referral, pengguna baru mendaftar dan menerapkan kode tersebut, lalu tier kedua pengguna dihitung ulang.

---

## Hasil Pengujian

### Skenario 1 — 10 Virtual Users

| Metrik                     | Hasil          |
| -------------------------- | -------------- |
| Virtual Users              | 10             |
| Duration                   | 1 menit        |
| Total HTTP Requests        | 83             |
| Failed Request             | 0%             |
| Average Response Time      | 9.054 ms       |
| p95 Response Time          | 17.210 ms      |
| Generate Referral Error    | 0%             |
| Apply Referral Error       | 0%             |
| Recalculate Tier Error     | 0%             |
| List Tiers Error           | 0%             |

### Skenario 2 — 50 Virtual Users

| Metrik                     | Hasil          |
| -------------------------- | -------------- |
| Virtual Users              | 50             |
| Duration                   | 1 menit        |
| Total HTTP Requests        | 227            |
| Failed Request             | 0%             |
| Average Response Time      | 16.072 ms      |
| p95 Response Time          | 23.314 ms      |
| Generate Referral Error    | 0%             |
| Apply Referral Error       | 0%             |
| Recalculate Tier Error     | 0%             |
| List Tiers Error           | 0%             |

### Skenario 3 — 100 Virtual Users

| Metrik                     | Hasil          |
| -------------------------- | -------------- |
| Virtual Users              | 100            |
| Duration                   | 1 menit        |
| Total HTTP Requests        | 310            |
| Failed Request             | 0%             |
| Average Response Time      | 19.840 ms      |
| p95 Response Time          | 33.454 ms      |
| Generate Referral Error    | 0%             |
| Apply Referral Error       | 0%             |
| Recalculate Tier Error     | 0%             |
| List Tiers Error           | 0%             |

---

## Detail Response Time Per Endpoint (100 Virtual Users)

| Endpoint                  | Avg Response Time | p95 Response Time |
| ------------------------- | ----------------- | ----------------- |
| Generate Referral Code    | 9.739 ms          | 20.031 ms         |
| Register User             | 20.641 ms         | —                 |
| Apply Referral Code       | 26.872 ms         | 30.884 ms         |
| Recalculate Tier          | 30.515 ms         | 33.489 ms         |

---

# Analisis Hasil Pengujian

Berdasarkan hasil stress testing, sistem mampu mempertahankan tingkat keberhasilan request sebesar **100%** pada skenario 10, 50, dan 100 Virtual Users tanpa menghasilkan error pada seluruh endpoint.

Beberapa temuan penting:

1. **Zero Error Rate**: Seluruh skenario (10, 50, 100 VUs) mencatat 0% error rate pada semua endpoint, menunjukkan stabilitas sistem yang sangat baik
2. **Scalable Response Time**: Response time meningkat secara proporsional seiring bertambahnya jumlah pengguna simultan, namun tetap dalam batas yang dapat diterima
3. **Queue Processing Efektif**: Endpoint `apply_referral` yang menggunakan asynchronous queue processing berhasil menghindari bottleneck meskipun melibatkan operasi database transaction yang kompleks
4. **Tidak Ada Race Condition**: Selama pengujian, tidak ditemukan kasus double-apply referral atau data inconsistency, membuktikan efektivitas implementasi Pessimistic Locking pada queue job

Implementasi Redis Queue, Database Indexing, dan Pessimistic Locking memberikan kontribusi signifikan terhadap kestabilan dan konsistensi data sistem saat menerima beban tinggi.

---

# Kesimpulan

Modul Membership, Referral & Tiering berhasil memenuhi seluruh persyaratan teknis yang diberikan pada mata kuliah Arsitektur dan Pengembangan Backend.

Hasil stress testing menunjukkan bahwa sistem mampu menangani hingga 100 Virtual Users secara bersamaan tanpa kegagalan request dan tanpa race condition. Penggunaan Laravel, Docker, Redis (Queue & Cache), Repository Pattern, Database Indexing, serta Pessimistic Locking berhasil meningkatkan performa, menjaga konsistensi data, dan memastikan skalabilitas aplikasi.

Dengan hasil tersebut, modul Membership, Referral & Tiering dapat dinyatakan siap digunakan dan memiliki performa yang baik untuk mendukung operasional sistem loyalty.
