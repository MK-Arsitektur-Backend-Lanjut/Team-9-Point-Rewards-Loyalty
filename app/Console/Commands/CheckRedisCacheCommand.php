<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CheckRedisCacheCommand extends Command
{
    protected $signature = 'system:check-redis';
    protected $description = 'Cek apakah koneksi Redis dan cache Redis berfungsi dengan benar.';

    public function handle(): int
    {
        $cacheDriver = config('cache.default');
        $redisHost = config('database.redis.default.host');
        $redisPort = config('database.redis.default.port');

        try {
            $store = Cache::store('redis');
            $store->put('system:redis_check', now()->toDateTimeString(), 10);
            $value = $store->get('system:redis_check');
            $ping = Redis::connection()->ping();

            $this->info("Cache default driver: {$cacheDriver}");
            $this->info("Redis host: {$redisHost}:{$redisPort}");
            $this->info("Redis ping: {$ping}");
            $this->info("Redis cache write/read OK: {$value}");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Redis check gagal: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
