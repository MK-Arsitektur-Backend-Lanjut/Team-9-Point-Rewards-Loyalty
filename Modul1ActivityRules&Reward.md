# Laporan Modul 1: Reward & Activity Rules

**Mata Kuliah:** Arsitektur dan Pengembangan Backend
**Kelompok:** 9
**Anggota/Penanggung Jawab Modul 1:** Alfina Nur Fadilla

---

# 📖 Deskripsi Modul

Modul Reward & Activity Rules bertanggung jawab untuk mengelola sistem reward pada aplikasi loyalty. Modul ini menyediakan layanan backend untuk pengelolaan data reward yang dapat ditukarkan oleh pengguna menggunakan poin yang telah dikumpulkan dari berbagai aktivitas.

Selain itu, modul ini menjadi pusat pengelolaan katalog reward yang digunakan oleh modul lain dalam sistem loyalty untuk proses penukaran poin dan validasi ketersediaan reward.

---

# 🛠️ Pemenuhan Ketentuan Teknis

Sesuai dengan ketentuan yang diberikan oleh dosen pengampu, modul ini telah memenuhi seluruh persyaratan teknis yang ditentukan.

## 1. Penggunaan Framework Laravel

Backend dikembangkan menggunakan framework Laravel yang menyediakan struktur pengembangan API yang terorganisir, aman, dan mudah dipelihara.

Laravel digunakan untuk mengelola routing, middleware, validasi data, ORM Eloquent, caching, serta integrasi database.

---

## 2. Berjalan di Dalam Docker Container

Aplikasi telah dikonfigurasi menggunakan Docker sehingga seluruh layanan dapat dijalankan dalam container yang terisolasi.

Komponen yang dijalankan melalui Docker meliputi:

* Laravel Application
* Nginx Web Server
* MySQL Database
* Redis Cache

Penggunaan Docker mempermudah deployment dan menjaga konsistensi environment antara pengembangan dan produksi.

---

## 3. Implementasi Repository Pattern

Untuk memisahkan logika bisnis dan akses data, modul ini menerapkan Repository Pattern.

Keuntungan penggunaan Repository Pattern:

* Mempermudah maintenance kode
* Memisahkan business logic dari database logic
* Meningkatkan fleksibilitas pengembangan
* Mempermudah proses testing

Struktur repository digunakan untuk mengelola data reward dan activity rules secara terpisah dari controller.

---

## 4. Penggunaan Redis Cache

Redis digunakan sebagai mekanisme caching untuk meningkatkan performa pembacaan data.

Data reward yang sering diakses dapat disimpan sementara pada Redis sehingga mengurangi beban query langsung ke database MySQL.

Berdasarkan hasil konfigurasi sistem:

* Cache Driver : Redis
* Queue Driver : Redis
* Session Driver : Redis

Implementasi ini membantu meningkatkan kecepatan respon API terutama saat menerima banyak request secara bersamaan.

---

## 5. Database Indexing

Untuk meningkatkan performa query database, dilakukan optimasi menggunakan indexing pada tabel rewards.

Index yang tersedia:

### Primary Key

```sql
PRIMARY KEY (id)
```

Digunakan untuk mempercepat pencarian berdasarkan ID reward.

### Unique Index

```sql
UNIQUE INDEX rewards_sku_unique (sku)
```

Digunakan untuk memastikan setiap reward memiliki SKU yang unik sekaligus mempercepat proses pencarian berdasarkan SKU.

### Composite Index

```sql
INDEX rewards_is_physical_is_active_index (is_physical, is_active)
```

Digunakan untuk mempercepat filtering reward berdasarkan:

* Jenis reward fisik/nonfisik
* Status aktif/tidak aktif

Index ini sangat membantu ketika sistem menampilkan daftar reward yang tersedia untuk ditukarkan pengguna.

---

# Fitur dan Endpoint API

Modul Reward & Activity Rules menyediakan beberapa layanan API utama sebagai berikut.

## Manajemen Reward

### Menampilkan Daftar Reward

```http
GET /api/rewards
```

Digunakan untuk mengambil seluruh daftar reward yang tersedia.

### Menampilkan Detail Reward

```http
GET /api/rewards/{id}
```

Digunakan untuk melihat informasi detail reward tertentu.

### Menambahkan Reward

```http
POST /api/rewards
```

Digunakan untuk menambahkan reward baru ke dalam sistem.

### Memperbarui Reward

```http
PUT /api/rewards/{id}
```

Digunakan untuk memperbarui informasi reward.

### Menghapus Reward

```http
DELETE /api/rewards/{id}
```

Digunakan untuk menghapus reward dari sistem.

---

# Analisis Stress Testing

Pengujian performa dilakukan menggunakan Grafana k6 untuk mengetahui kemampuan sistem dalam menangani banyak request secara bersamaan.

## Endpoint yang Diuji

```http
GET /api/rewards
```

Endpoint ini dipilih karena merupakan endpoint yang paling sering diakses pengguna saat melihat katalog reward yang tersedia.

---

## Hasil Pengujian

### Skenario 1 — 10 Virtual Users

| Metrik                | Hasil        |
| --------------------- | ------------ |
| Virtual Users         | 10           |
| Duration              | 10 detik     |
| Request               | 1024         |
| Failed Request        | 0%           |
| Average Response Time | 97.66 ms     |
| Throughput            | 101.95 req/s |

### Skenario 2 — 50 Virtual Users

| Metrik                | Hasil        |
| --------------------- | ------------ |
| Virtual Users         | 50           |
| Duration              | 10 detik     |
| Request               | 1137         |
| Failed Request        | 0%           |
| Average Response Time | 446.63 ms    |
| Throughput            | 110.20 req/s |

### Skenario 3 — 100 Virtual Users

| Metrik                | Hasil       |
| --------------------- | ----------- |
| Virtual Users         | 100         |
| Duration              | 10 detik    |
| Request               | 897         |
| Failed Request        | 0%          |
| Average Response Time | 1.16 s      |
| Throughput            | 82.71 req/s |

---

# Analisis Hasil Pengujian

Berdasarkan hasil stress testing, sistem mampu mempertahankan tingkat keberhasilan request sebesar 100% pada seluruh skenario pengujian.

Walaupun response time meningkat seiring bertambahnya jumlah pengguna simultan, server tetap dapat memproses seluruh request tanpa menghasilkan error.

Implementasi Redis Cache dan Database Indexing memberikan kontribusi terhadap kestabilan sistem saat menerima beban tinggi.

---

# Kesimpulan

Modul Reward & Activity Rules berhasil memenuhi seluruh persyaratan teknis yang diberikan pada mata kuliah Arsitektur dan Pengembangan Backend.

Hasil stress testing menunjukkan bahwa sistem mampu menangani hingga 100 Virtual Users secara bersamaan tanpa kegagalan request. Penggunaan Laravel, Docker, Redis, Repository Pattern, dan Database Indexing berhasil meningkatkan performa serta menjaga skalabilitas aplikasi.

Dengan hasil tersebut, modul Reward & Activity Rules dapat dinyatakan siap digunakan dan memiliki performa yang baik untuk mendukung operasional sistem loyalty.
