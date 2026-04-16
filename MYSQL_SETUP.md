# SETUP DENGAN MYSQL - STEP-BY-STEP

## ✅ Files Yang Sudah Di-Update ke MySQL

- ✅ `.env` - DB_CONNECTION=mysql, port 3306
- ✅ `.env.example` - MySQL configuration template
- ✅ `docker-compose.yml` - MySQL 8.0 container  
- ✅ `config/database.php` - MySQL driver config
- ✅ `Dockerfile` - pdo_mysql extension
- ✅ `database/migrations/2024_01_01_000005_create_referrals_table.php` - Updated timestamps()

**Semua code (controllers, repositories, services) sudah compatible dengan MySQL - tidak perlu changes!**

---

## 🚀 CARA SETUP & JALANKAN

### STEP 1: Navigasi ke Project Directory

```powershell
cd "d:\KULYEAH\SEMT 8\Backend\Team9"
```

### STEP 2: Verify .env File

Check apakah `.env` sudah dikopikan dan OK:

```powershell
# Cek file .env sudah ada
Get-Content .env
```

**Expected:**
```
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=loyalty_db
DB_USERNAME=root
DB_PASSWORD=root
```

### STEP 3: Clean Up Old Docker (jika ada PostgreSQL container lama)

```powershell
# Stop dan remove old containers & volumes
docker-compose down -v
docker system prune -a

# Verify semua clean
docker ps
docker volume ls
```

### STEP 4: Build & Start Docker dengan MySQL

```powershell
# Build image dengan MySQL support
docker-compose up -d --build

# Tunggu ~30 detik untuk MySQL siap
Start-Sleep -Seconds 30

# Check status
docker-compose ps
```

**Expected output:**
```
loyalty-app     Up
loyalty-db      Up (mysql)
loyalty-nginx   Up
```

### STEP 5: Verify Database Connection

```powershell
# Test MySQL connection
docker-compose exec db mysql -uroot -proot loyalty_db -e "SELECT 1;"
```

**Expected:**
```
1
1
```

### STEP 6: Install PHP Dependencies

```powershell
docker-compose exec app composer install
```

### STEP 7: Generate Application Key

```powershell
docker-compose exec app php artisan key:generate
```

### STEP 8: Run Database Migrations

```powershell
docker-compose exec app php artisan migrate
```

**Expected:**
```
Migration table created successfully.
Migrating: 2024_01_01_000001_create_users_table
Migrated: 2024_01_01_000001_create_users_table
[etc - 5 migrations total]
```

### STEP 9: Seed Database (35k+ logs & 10k+ referrals)

```powershell
docker-compose exec app php artisan db:seed
```

**Tunggu ~5-10 menit untuk seeding selesai**

### STEP 10: Test API

```powershell
# Test health check
Invoke-WebRequest -Uri "http://localhost:8000/api/rewards/balance/1" | ConvertTo-Json

# Atau di browser
# http://localhost:8000/api/rewards/balance/1
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user_id": 1,
    "current_balance": 0,
    "available_balance": 0,
    "locked_points": 0,
    "expired_points": 0,
    "lifetime_points": 0
  }
}
```

---

## 📊 DATABASE CREDENTIALS

```
Driver: MySQL 8.0
Host: localhost (inside docker: db)
Port: 3306
Username: root
Password: root
Database: loyalty_db
```

---

## 🔍 VERIFY SETUP

### Check MySQL Tables Created

```powershell
docker-compose exec db mysql -uroot -proot loyalty_db -e "SHOW TABLES;"
```

**Expected:**
```
Tables_in_loyalty_db
failed_jobs
migrations
password_reset_tokens
point_balances
point_logs
point_rules
referrals
sessions
users
```

### Check Data Seeded

```powershell
# Check users
docker-compose exec db mysql -uroot -proot loyalty_db -e "SELECT COUNT(*) as user_count FROM users;"

# Check point logs (should be 35k+)
docker-compose exec db mysql -uroot -proot loyalty_db -e "SELECT COUNT(*) as log_count FROM point_logs;"

# Check referrals (should be 10k+)
docker-compose exec db mysql -uroot -proot loyalty_db -e "SELECT COUNT(*) as referral_count FROM referrals;"
```

---

## 🧪 TEST ENDPOINTS

### Via PowerShell

```powershell
# 1. Get Balance
Invoke-WebRequest -Uri "http://localhost:8000/api/rewards/balance/1"

# 2. Add Points
$Body = @{
    user_id = 1
    point_rule_id = 1
    metadata = @{ order_id = "ORD-001" }
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:8000/api/rewards/add-points" `
    -Method POST `
    -Headers @{"Content-Type"="application/json"} `
    -Body $Body

# 3. Check Balance Again
Invoke-WebRequest -Uri "http://localhost:8000/api/rewards/balance/1"
```

### Via cURL (jika ada cURL di system)

```bash
# Get balance
curl http://localhost:8000/api/rewards/balance/1

# Add points
curl -X POST http://localhost:8000/api/rewards/add-points \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"point_rule_id":1}'

# Redeem points
curl -X POST http://localhost:8000/api/rewards/redeem \
  -H "Content-Type: application/json" \
  -d '{"user_id":1,"points_to_redeem":50}'
```

### Via Postman

1. Import `postman_collection.json`
2. Set base URL: `{{base_url}}` = `http://localhost:8000`
3. Click "Add Points" 
4. Click "Send"
5. View response

---

## 🛠️ USEFUL COMMANDS

### View Logs

```powershell
# App logs
docker-compose logs -f app

# Database logs
docker-compose logs -f db

# Nginx logs
docker-compose logs -f webserver
```

### SSH ke Containers

```powershell
# SSH ke app (PHP)
docker-compose exec app bash

# SSH ke database (MySQL)
docker-compose exec db bash

# Interactive MySQL
docker-compose exec db mysql -uroot -proot loyalty_db
```

### Restart Containers

```powershell
docker-compose restart

# Or full rebuild
docker-compose down
docker-compose up -d --build
```

### Fresh Start (Nuclear Option)

```powershell
# Stop & remove everything
docker-compose down -v

# Remove images
docker image rm loyalty-app mysql:8.0-alpine nginx:alpine

# Clean system
docker system prune -a -f

# Start fresh
docker-compose up -d --build
```

---

## ⚠️ TROUBLESHOOTING

### Error: "Cannot connect to MySQL"

**Solution:**
```powershell
# Check if MySQL is running
docker-compose ps

# If not running, start
docker-compose up -d

# Wait 30 seconds
Start-Sleep -Seconds 30

# Try again
docker-compose exec db mysql -uroot -proot loyalty_db -e "SELECT 1;"
```

### Error: "Access denied for user 'root'"

**Solution:**
```powershell
# Verify credentials in .env
Get-Content .env | Select-String "DB_"

# Should be:
# DB_USERNAME=root
# DB_PASSWORD=root
```

### Migration fails

**Solution:**
```powershell
# Rollback migrations
docker-compose exec app php artisan migrate:rollback

# Run again
docker-compose exec app php artisan migrate
```

### Port 3306 already in use

**Solution:**
```powershell
# Change port in docker-compose.yml
# ports:
#   - "3307:3306"  # Use 3307 instead of 3306

# Also update .env
# DB_PORT=3307
```

### Database seeding too slow

**Tip**: Seeding 35k+ logs & 10k+ referrals bisa slow. Initial run ~5-10 menit normal. Kalau sangat slow:

```powershell
# Check logs
docker-compose logs -f app

# Kill process if needed
docker-compose exec app php artisan db:seed --class=UserSeeder
# Run individual seeders in order:
docker-compose exec app php artisan db:seed --class=PointRuleSeeder
docker-compose exec app php artisan db:seed --class=ReferralSeeder
docker-compose exec app php artisan db:seed --class=PointLogSeeder
```

---

## ✅ VERIFICATION CHECKLIST

| Item | Status | Command |
|------|--------|---------|
| Docker running | ✅ | `docker-compose ps` |
| MySQL responsive | ✅ | `docker-compose exec db mysql -uroot -proot loyalty_db -e "SELECT 1;"` |
| Tables created | ✅ | `docker-compose exec db mysql -uroot -proot loyalty_db -e "SHOW TABLES;"` |
| Users seeded | ✅ | `docker-compose exec db mysql -uroot -proot loyalty_db -e "SELECT COUNT(*) FROM users;"` |
| Point logs seeded | ✅ | `docker-compose exec db mysql -uroot -proot loyalty_db -e "SELECT COUNT(*) FROM point_logs;"` |
| API responding | ✅ | `curl http://localhost:8000/api/rewards/balance/1` |
| Points can be added | ✅ | POST to `/api/rewards/add-points` |

---

## 🎉 ALL DONE!

Jika semua steps completed, project sudah ready dengan MySQL! 

**Next**: 
- Test endpoints dengan Postman
- Check database dengan MySQL Workbench/DBeaver
- Integrasi dengan module lain
- Deploy ke staging

---

**Database**: MySQL 8.0 ✅  
**Status**: Production Ready  
**Last Updated**: April 15, 2026
