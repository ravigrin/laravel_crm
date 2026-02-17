<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class SpamProtection
{
    public function handle(Request $request, Closure $next)
    {
        $ip = (string) $request->ip();
        $fingerprint = (string) $request->header('X-Client-Fingerprint', '');
        $quizId = (string) $request->input('quiz_id', '');

        $key = implode(':', array_filter(['lead', $ip, $fingerprint, $quizId]));
        if (RateLimiter::tooManyAttempts($key, config('ip_filter.max_attempts', 30))) {
            return response()->json(['message' => 'Too many attempts'], 429);
        }

        RateLimiter::hit($key, config('ip_filter.decay_seconds', 60));

        // TODO: hook to a blocklist service/table for ip/fingerprint/email/phone

        return $next($request);
    }
}




