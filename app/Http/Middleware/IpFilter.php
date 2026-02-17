<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class IpFilter
{
    public function handle($request, Closure $next)
    {
        // Apply filter only to POST requests (store method)
        if ($request->method() !== 'POST') {
            return $next($request);
        }

        $ip = $request->ip();
        $key = "ip_filter:{$ip}";
        $maxAttempts = config('ip_filter.leads_qty_from_ip', 5);
        $periodSeconds = config('ip_filter.period_sec', 1200);

        try {
            // Используем Cache вместо прямого подключения к Redis
            $currentAttempts = Cache::get($key, 0);

            if ($currentAttempts >= $maxAttempts) {
                return response()->json([
                    'error' => 'Maximum requests reached. Try later'
                ], 429);
            }

            // Увеличиваем счетчик и устанавливаем TTL
            Cache::put($key, $currentAttempts + 1, $periodSeconds);

            return $next($request);
        } catch (\Exception $e) {
            // Если cache недоступен (например, Redis не работает), логируем и пропускаем запрос
            Log::warning('IP filter cache unavailable, skipping rate limit', [
                'ip' => $ip,
                'error' => $e->getMessage()
            ]);

            // В production можно вернуть ошибку, в development пропускаем
            if (app()->environment('production')) {
                return response()->json([
                    'error' => 'Service temporarily unavailable'
                ], 503);
            }

            // В development/test пропускаем без ограничений
            return $next($request);
        }
    }
}



