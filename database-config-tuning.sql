-- =============================================
-- DATABASE CONFIGURATION TUNING
-- =============================================

-- UNTUK MYSQL/MARIADB

-- 1. MAX CONNECTIONS (SESUAIKAN KEBUTUHAN)
SET GLOBAL max_connections = 500;

-- 2. BUFFER POOL SIZE (UNTUK INNODB)
SET GLOBAL innodb_buffer_pool_size = 1073741824; -- 1GB

-- 3. QUERY CACHE (JIKA MENGGUNAKAN MYSQL < 8.0)
SET GLOBAL query_cache_size = 67108864; -- 64MB
SET GLOBAL query_cache_type = 1;

-- 4. TIMEOUT SETTINGS
SET GLOBAL wait_timeout = 28800;
SET GLOBAL interactive_timeout = 28800;

-- 5. CONNECTION POOL
SET GLOBAL thread_cache_size = 100;

-- CEK PERUBAHAN
SHOW VARIABLES LIKE 'max_connections';
SHOW VARIABLES LIKE 'innodb_buffer_pool_size';