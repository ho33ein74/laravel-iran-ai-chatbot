<?php
namespace Unixscript\IranAiChatbot\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class VerifyAiQuotaMiddleware {
    public function handle(Request $request, Closure $next) {
        if (!config('iran-ai-chatbot.quota.enabled', true)) return $next($request);
        $userId = $request->user() ? $request->user()->id : $request->ip();
        $cacheKey = "ai_quota_{$userId}_" . date('Y_m_d');
        $maxQuestions = config('iran-ai-chatbot.quota.max_questions_per_user', 20);
        $requestsCount = Cache::get($cacheKey, 0);
        
        if ($requestsCount >= $maxQuestions) {
            return response()->json(['reply' => 'سقف مجاز روزانه پرسش‌های شما پر شده است.'], 429);
        }
        Cache::put($cacheKey, $requestsCount + 1, now()->endOfDay());
        return $next($request);
    }
}