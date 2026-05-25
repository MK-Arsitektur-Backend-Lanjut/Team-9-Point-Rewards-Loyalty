# QUICK START GUIDE

## Untuk Pengguna Windows

### Step 1: Buka Command Prompt atau PowerShell

Navigasi ke folder project:
```
cd "d:\KULYEAH\SEMT 8\Backend\Team9"
```

### Step 2: Jalankan Setup Script

```
setup.bat
```

Script ini akan otomatis:
- ✅ Stop container yang ada
- ✅ Build Docker images
- ✅ Start containers (app, db, nginx)
- ✅ Install PHP dependencies
- ✅ Generate app key
- ✅ Run database migrations
- ✅ Seed database dengan 35k+ logs & 10k+ referral

**Waktu**: ~5-10 menit (tergantung speed)

### Step 3: Test API

Buka browser atau Postman:

```
http://localhost:8000/api/rewards/balance/1
```

Harusnya return JSON dengan point balance user.

### Step 4: Import Postman Collection

1. Buka Postman
2. File → Import → pilih `postman_collection.json`
3. Gunakan pre-built requests untuk test

---

## Docker Commands

```bash
# View logs
docker-compose logs -f app

# SSH ke app container
docker-compose exec app bash

# Jalankan artisan command
docker-compose exec app php artisan <command>

# Stop containers
docker-compose down

# Restart containers
docker-compose restart

# View database
docker-compose exec db psql -U postgres -d loyalty_db
```

---

## API Endpoints

| Endpoint | Method | Deskripsi |
|----------|--------|-----------|
| `/api/rewards/add-points` | POST | Tambah poin otomatis |
| `/api/rewards/redeem` | POST | Tukar poin |
| `/api/rewards/balance/{id}` | GET | Lihat saldo |
| `/api/rewards/validate-balance` | POST | Validasi saldo |
| `/api/rewards/logs/{id}` | GET | Lihat riwayat |

---

## Database Access

**Connection String**:
```
Host: localhost
Port: 3306
Username: root
Password: root
Database: loyalty_db
```

**Tools**: MySQL Client, Navicat, MySQL Workbench, DBeaver

---

## Troubleshooting

### Port 8000 atau 5432 sudah terpakai

Edit `docker-compose.yml`:
```yaml
ports:
  - "8001:80"      # Ubah 8000 jadi 8001
  - "5433:5432"    # Ubah 5432 jadi 5433
```

### Docker image gagal build

```bash
docker system prune -a
docker-compose up -d --build
```

### Database connection error

```bash
# Cek status container
docker-compose ps

# View logs
docker-compose logs db
```

### Permission denied

```bash
# Windows, jalankan Command Prompt as Administrator
# Atau ubah file permissions jika di Linux/Mac
chmod 777 storage/logs
```

---

## Next Steps

1. ✅ Baca `API_DOCUMENTATION.md` untuk detail API
2. ✅ Baca `README.md` untuk overview
3. ✅ Import Postman collection untuk testing
4. ✅ Review `IMPLEMENTATION_SUMMARY.md` untuk technical details

---

## File Penting

- `API_DOCUMENTATION.md` - Full API docs
- `README.md` - Project overview
- `IMPLEMENTATION_SUMMARY.md` - Technical summary
- `postman_collection.json` - Postman requests
- `docker-compose.yml` - Docker configuration
- `routes/api.php` - API routes

---

**Status**: ✅ Production Ready for Testing & Development
