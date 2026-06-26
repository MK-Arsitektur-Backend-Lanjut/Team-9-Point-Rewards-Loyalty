Analisis Modul 3 User & Statement

Nama: Hafizhah Farah Fadhila
NIM: 1202220154
Tim: 9

Modul 3 merupakan modul yang bertanggung jawab mengelola yang berkaitan dengan member dan riwayat poin. Terdapat 3 utama yaitu, fitur autentikasi member menggunakan JSON Web Tokens (JWT) yang membantu dalam proses login dan register member, fitur melihat riwayat poin (E-Statement), dan melihat masa berlaku poin. Selain itu, untuk mempermudah member dalam melihat/mencari riwayat poinnya, pada fitur statement menyediakan berbagai filter poin. Filter ini bisa melihat poin berdasarkan tanggal, jenis aktivitas, atau status poin.

*Untuk memenuhi kebutuhan teknis, modul ini menggunakan:*
1. Laravel sebagai kerangka kerja serta memakai package tymon/jwt-auth untuk menangani token autentikasi.
2. Proyek berjalan di dalam container Docker. Terdapat 4 container yang saling terhubung yaitu loyalty-app-new untuk Laravel, loyalty-db-new untuk database MySQL, loyalty-redis-new untuk Redis, dan loyalty-nginx untuk web server nginx. Hal ini bertujuan untuk memastikan lingkungan pengembangan yang konsisten.
3. Repository Pattern dan Service Layer untuk memisahkan logika untuk dan dengan logika bisnis. Contohnya terdapat pada AuthService, PointStatementService dan UserRepository.
4. Data dummy 35.000 riwayat poin dengan membuat seeder yang ada di \seeders yang saling berhubungan untuk kebutuhan statement.
5. Redis sebagai in-memory cache (menyimpan sementara data yang sering diakses) untuk data member saat login (disimpan 5 menit), saldo poin (disimpan 10 menit) dan pencarian riwaya poin (disimpan 2-5 menit).
6. Index dalam proses optimasi database. Modul ini menambahkan index di:

Tabel users:
- idx_users_email_password: mempercepat proses login
- idx_users_email:  mempercepat pencarian user

Tabel point_activity_logs:
- idx_user_earned_date: mempercepat pengurutan riwayat
- idx_user_id: mempercepat filter berdasarkan user
- idx_user_status: mempercepat filter status poin
- idx_user_activity: mempercepat filter jenis aktivitas
- idx_user_earned_activity_status: index gabungan untuk semua filter
- idx_user_earned_activity: index gabungan untuk tanggal dan aktivitas
- idx_user_status_expired: mempercepat perhitungan saldo
- idx_user_expired_status: mempercepat pencarian poin yang hampir kadaluarsa

*Berikut merupakan daftar fitur beserta endpointnya:*
1. Autentikasi
- Register: POST /api/register 
- Login: POST /api/login
- Logout: POST /api/logout
- Profil: GET /api/me 
2. Riwayat Poin (E-Statement)
- Riwayat poin: GET /api/statement
- Filter tanggal: GET /api/statement?start_date=...&end_date=..
- Filter aktivitas: GET /api/statement?activity_code=...
- Filter status: GET /api/statement?point_status=...
- Atur page: GET /api/statement?page=N&per_page=M
3. Masa Berlaku Poin
- Saldo dan poin kedaluwarsa GET /api/balance 

*Stress Test:*
Pengujian dilakukan menggunakan:
- Tools: Grafana k6
Grafana k6 dipilih menjadi tools saat pengujian karena gratis, mendukung berbagai jenis pengujian salah satunya stress test. Tools ini juga memberikan output yang jelas dan terstruktur sehingga pengguna tidak terlalu sulit untuk memahami hasil pengujian.

- 5 Tahap Stress Test
Tahap 1: 0 - 50 VUs (30 detik)
Tahap 2: 50 - 200 VUs (60 detik)
Tahap 3: 200 VUs (60 detik)
Tahap 4: 200 - 0 VUs (30 detik)
Tahap 5: 0 VUs (30 detik)

Stress test mengggunakan 5 tahapan agar bisa melihat performa dari beban yang diberikan dari tingkat rendah - tingkat tinggi.

Hasil:
Pengujian dilakukan beberapa kali agar mendapatkan hasil yang memuaskan. Pertama, pengujian dilakukan di saat belum menambahkan index pada database dan selanjutnya setelah menambahkan index dengan harapan bisa meningkatkan performa sistem. 

Test dapat dijalankan pada file:
(stress-test-modul-3/login-stress-test.js)
(stress-test-modul-3/login-stress-test.js)
(stress-test-modul-3/points-balance-stress-test.js)
(stress-test-modul-3/statement-filter-stress-test.js)
(stress-test-modul-3/statement-pagination-stress-test.js)
(stress-test-modul-3/statement-stress-test.js)

Berikut merupakan contoh perbandingannya:

**Login**
***Sebelum:***
TOTAL RESULTS 

    checks_total.......: 5981    20.621414/s
    checks_succeeded...: 100.00% 5981 out of 5981
    checks_failed......: 0.00%   0 out of 5981

    ✓ login status is 200
    ✓ login has token
    ✓ login status 200

    CUSTOM
    errors.........................: 0.00%  0 out of 1569
    login_duration.................: avg=18256.787126 min=347      med=17239  max=29639 p(90)=27587.6 p(95)=28742.6

    HTTP
    http_req_duration..............: avg=21.5s        min=345.73ms med=22.32s max=46.4s p(90)=36.91s  p(95)=44.7s  
      { expected_response:true }...: avg=21.5s        min=345.73ms med=22.32s max=46.4s p(90)=36.91s  p(95)=44.7s  
    http_req_failed................: 0.00%  0 out of 1994
    http_reqs......................: 1994   6.874954/s

    EXECUTION
    iteration_duration.............: avg=21.95s       min=846.98ms med=22.78s max=46.6s p(90)=37.16s  p(95)=44.9s  
    iterations.....................: 1993   6.871506/s
    vus............................: 12     min=0         max=315
    vus_max........................: 434    min=434       max=434

    NETWORK
    data_received..................: 1.6 MB 5.6 kB/s
    data_sent......................: 451 kB 1.6 kB/s
running (4m50.0s), 000/434 VUs, 1993 complete and 86 interrupted iterations
stress ✓ [======================================] 000/200 VUs  3m30s
spike  ✓ [======================================] 000/300 VUs  50s


***Setelah:***
TOTAL RESULTS 
    checks_total.......: 6668    22.606657/s
    checks_succeeded...: 100.00% 6668 out of 6668
    checks_failed......: 0.00%   0 out of 6668

    ✓ login status is 200
    ✓ login has token
    ✓ login status 200

    CUSTOM
    errors.........................: 0.00%  0 out of 1746
    login_duration.................: avg=16124.520046 min=298      med=15922  max=26111  p(90)=24560.5 p(95)=25009.5

    HTTP
    http_req_duration..............: avg=19.25s       min=297.58ms med=18.81s max=43.83s p(90)=35.41s  p(95)=42.04s 
      { expected_response:true }...: avg=19.25s       min=297.58ms med=18.81s max=43.83s p(90)=35.41s  p(95)=42.04s 
    http_req_failed................: 0.00%  0 out of 2223
    http_reqs......................: 2223   7.536683/s

    EXECUTION
    iteration_duration.............: avg=19.67s       min=798.68ms med=19.16s max=44.03s p(90)=35s     p(95)=42.23s 
    iterations.....................: 2220   7.526512/s
    vus............................: 2      min=0         max=300
    vus_max........................: 434    min=434       max=434

    NETWORK
    data_received..................: 1.8 MB 6.2 kB/s
    data_sent......................: 496 kB 1.7 kB/s
running (4m55.0s), 000/434 VUs, 2220 complete and 66 interrupted iterations
stress ✓ [======================================] 000/200 VUs  3m30s
spike  ✓ [======================================] 000/300 VUs  50s

Hasilnya terlihat ada peningkatan meskipun masih belum signifikan. Peningkatan terlihat pada rata rata 
{ expected_response:true }...: avg=19.25s
{ expected_response:true }...: avg=21.5s

**Statement**
***Sebelum:***
TOTAL RESULTS 

    checks_total.......: 2736    11.610225/s
    checks_succeeded...: 100.00% 2736 out of 2736
    checks_failed......: 0.00%   0 out of 2736

    ✓ login status is 200
    ✓ login has token
    ✓ statement status 200

    CUSTOM
    statement_duration.............: avg=16791.968688 min=105.5438 med=18387.36955 max=25645.9703 p(90)=24256.14097 p(95)=24645.892945

    HTTP
    http_req_duration..............: avg=16.55s       min=105.54ms med=17.71s      max=26.05s     p(90)=24.31s      p(95)=24.79s      
      { expected_response:true }...: avg=16.55s       min=105.54ms med=17.71s      max=26.05s     p(90)=24.31s      p(95)=24.79s      
    http_req_failed................: 0.00%  0 out of 1812
    http_reqs......................: 1812   7.689228/s

    EXECUTION
    iteration_duration.............: avg=33.35s       min=1.06s    med=33.09s      max=48.22s     p(90)=45.83s      p(95)=46.38s      
    iterations.....................: 885    3.7555/s
    vus............................: 17     min=0         max=200
    vus_max........................: 200    min=200       max=200

    NETWORK
    data_received..................: 1.7 MB 7.1 kB/s
    data_sent......................: 634 kB 2.7 kB/s
running (3m55.7s), 000/200 VUs, 885 complete and 38 interrupted iterations
stress ✓ [======================================] 000/200 VUs  3m30s


***Sesudah:***
TOTAL RESULTS 

    checks_total.......: 3696    16.137036/s
    checks_succeeded...: 100.00% 3696 out of 3696
    checks_failed......: 0.00%   0 out of 3696

    ✓ login status is 200
    ✓ login has token
    ✓ statement status 200

    CUSTOM
    statement_duration.............: avg=11858.538717 min=668.4234 med=12440.62095 max=20817.9561 p(90)=18301.75644 p(95)=19198.18449

    HTTP
    http_req_duration..............: avg=11.94s       min=605.96ms med=12.57s      max=20.99s     p(90)=18.3s       p(95)=19.27s     
      { expected_response:true }...: avg=11.94s       min=605.96ms med=12.57s      max=20.99s     p(90)=18.3s       p(95)=19.27s     
    http_req_failed................: 0.00%  0 out of 2459
    http_reqs......................: 2459   10.736193/s

    EXECUTION
    iteration_duration.............: avg=24.34s       min=2.01s    med=25.91s      max=37.76s     p(90)=35.59s      p(95)=36.12s     
    iterations.....................: 1222   5.335351/s
    vus............................: 7      min=0         max=200
    vus_max........................: 200    min=200       max=200

    NETWORK
    data_received..................: 2.3 MB 9.9 kB/s
    data_sent......................: 849 kB 3.7 kB/s
running (3m49.0s), 000/200 VUs, 1222 complete and 14 interrupted iterations
stress ✓ [======================================] 000/200 VUs  3m30s

Hasilnya terlihat ada peningkatan rata rata dan throughput
{ expected_response:true }...: avg=16.55s
{ expected_response:true }...: avg=11.94s

    http_reqs......................: 1812   7.689228/s
    http_reqs......................: 2459   10.736193/s


***Hasil dengan VUs mencapai 1500***
TOTAL RESULTS 
    checks_total.......: 23774  28.427224/s
    checks_succeeded...: 11.28% 2684 out of 23774
    checks_failed......: 88.71% 21090 out of 23774

    ✗ login status is 200
      ↳  8% — ✓ 975 / ✗ 10425
    ✗ login has token
      ↳  8% — ✓ 975 / ✗ 10425
    ✗ statement status 200
      ↳  75% — ✓ 734 / ✗ 240

    CUSTOM
    errors.........................: 100.00% 62 out of 62
    statement_duration.............: avg=38782.311605 min=97.6839 med=40576.50645 max=60137.561 p(90)=60000.87914 p(95)=60002.269485

    HTTP
    http_req_duration..............: avg=55.91s       min=97.68ms med=59.99s      max=1m0s      p(90)=1m0s        p(95)=1m0s        
      { expected_response:true }...: avg=31.38s       min=97.68ms med=30.71s      max=59.99s    p(90)=52.84s      p(95)=55.63s      
    http_req_failed................: 86.18%  10665 out of 12374
    http_reqs......................: 12374   14.795931/s

    EXECUTION
    iteration_duration.............: avg=1m0s         min=1.07s   med=1m0s        max=2m0s      p(90)=1m0s        p(95)=1m2s        
    iterations.....................: 11399   13.630097/s
    vus............................: 6       min=0              max=1500
    vus_max........................: 1500    min=1373           max=1500

    NETWORK
    data_received..................: 1.6 MB  1.9 kB/s
    data_sent......................: 3.1 MB  3.7 kB/s
running (13m56.3s), 0000/1500 VUs, 11399 complete and 766 interrupted iterations
stress ✓ [======================================] 0000/1500 VUs  13m0s

Hasil pengujian menunjukkan bahwa sistem masih kesulitan/terkendala ketika digunakan oleh 1.500 pengguna dalam waktu yang bersamaan.


*Kesimpulan*
Modul 3 ini berhasil dikembangkan dengan fitur yang lengkap sesuai intruksi (Autentikasi, Riwayat Poin, dan Masa Berlaku Poin) dan 0 % error rate pada pengujian (stress test). Index yang dilakukan pada database terbukti meningkatkan performa 10-28%. Namun untuk skala entrprise, modul 3 masih perlu dilakukan optimasi lanjutan terutama pada endpoint yang performanya masih buruk.