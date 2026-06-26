Stress Test Modul 3 - User & Statement

Endpoint yang diuji:
- POST /api/login
- GET /api/statement
- GET /api/statement/export-pdf
- GET /api/points/balance

Menjalankan test:

k6 run login-stress-test.js

k6 run statement-stress-test.js

k6 run statement-filter-stress-test.js

k6 run statement-pagination-stress-test.js

k6 run export-pdf-stress-test.js

k6 run points-balance-stress-test.js