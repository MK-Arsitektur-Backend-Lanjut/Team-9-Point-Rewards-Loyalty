Analisis yang didapatkan:
- Mengimplementasikan row locking (lockForUpdate) pada data user untuk mencegah race condition saat beberapa request mengakses user yang sama.
- Menambahkan tabel point_balances sebagai cache saldo poin sehingga tidak perlu melakukan agregasi SUM pada point_logs
- Memindahkan proses tier recalculation ke luar transaction agar durasi lock lebih singkat dan throughput meningkat.


Hasil Stress Test (K6)

Test dilakukan menggunakan K6 dengan skenario normal load (add points → check balance → redeem points) pada tiga level concurrent users.

HASIL ANALISIS:

10 Concurent Users :
HTTP Failed	0%
error rate : 0%
Total requests	150 
Throughput 2.75 req/s 
avg response : 2 s

100 Concurent Users :
HTTP Failed	0%
error rate : 30%
Total requests	180 
Throughput 2.75 req/s 
Avg Response : 18 s

1000 Concurent Users :
HTTP Failed	0%
error rate : 80%
Total requests	5000
Throughput 2.75 req/s 
Avg Response : 10 s
