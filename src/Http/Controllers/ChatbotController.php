<?php
namespace Unixscript\IranAiChatbot\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Unixscript\IranAiChatbot\Services\AiManager;
use Unixscript\IranAiChatbot\Services\HumanEscalationService;
use Unixscript\IranAiChatbot\Services\Guardrails\PiiMaskerService;
use Unixscript\IranAiChatbot\Services\Guardrails\PromptInjectionDetector;
use Unixscript\IranAiChatbot\Services\ModerationService;
use Unixscript\IranAiChatbot\Services\ModelSearchService;
use Unixscript\IranAiChatbot\Models\AiChatHistory;
use Unixscript\IranAiChatbot\Models\AiSetting;
use Illuminate\Support\Str;

class ChatbotController extends Controller {
    
    /**
     * بررسی هوشمند و داینامیک وضعیت کاربری
     */
    private function resolveUser(Request $request) {
        if ($request->user()) return $request->user();

        $guards = ['sanctum', 'api', 'web'];
        foreach ($guards as $guard) {
            // فقط گاردهایی که در لاراول دیفاین شده‌اند را چک کن
            if (config()->has("auth.guards.{$guard}") && Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }
        return null;
    }

    public function getSettings(Request $request) {
        $user = $this->resolveUser($request);

        return response()->json([
            'bot_name' => AiSetting::val('ui.bot_name', config('iran-ai-chatbot.ui.bot_name')),
            'primary_color' => AiSetting::val('ui.primary_color', config('iran-ai-chatbot.ui.primary_color')),
            'auth_required' => AiSetting::val('features.auth_required', config('iran-ai-chatbot.features.auth_required')),
            'is_logged_in' => !is_null($user),
        ]);
    }

    public function sendMessage(Request $request, AiManager $aiManager, HumanEscalationService $escalation, PiiMaskerService $pii, PromptInjectionDetector $injection, ModerationService $mod, ModelSearchService $dbSearch) {
        $request->validate(['message' => 'required|string']);
        $sessionId = $request->cookie('chat_session_id') ?? (string) Str::uuid();
        $message = $request->message;

        $user = $this->resolveUser($request);
        $isLoggedIn = !is_null($user);
        
        $authRequired = AiSetting::val('features.auth_required', config('iran-ai-chatbot.features.auth_required'));

        if ($authRequired && !$isLoggedIn) {
            return response()->json(['reply' => 'برای استفاده از گفتگو باید وارد حساب کاربری خود شوید.', 'needs_login' => true], 401);
        }

        if (!$injection->isSafe($message)) return response()->json(['reply' => 'درخواست شما مغایر با قوانین سیستم است.']);
        if (!$mod->isClean($message)) return response()->json(['reply' => 'لطفاً از کلمات مناسب استفاده کنید.']);
        
        $message = $pii->mask($message);

        if ($this->wantsHuman($message)) {
            $escalation->assignToAdmin($user, $sessionId, $message);
            return response()->json(['reply' => 'درخواست شما ثبت شد. به زودی پشتیبان پاسخ می‌دهد.'])->cookie('chat_session_id', $sessionId, 60*24*30);
        }

        if (AiSetting::val('features.direct_db_suggest', true)) {
            $dbResults = $dbSearch->searchStructured($message);
            if (!empty($dbResults)) {
                AiChatHistory::create(['user_id' => $user?->id, 'session_id' => $sessionId, 'user_message' => $message, 'bot_reply' => 'پیشنهاد کالا/مقاله (DB)']);
                return response()->json(['reply' => 'من این موارد را در سایت پیدا کردم:', 'suggestions' => $dbResults])->cookie('chat_session_id', $sessionId, 60*24*30);
            }
        }

        $reply = $aiManager->driver()->ask($message);
        AiChatHistory::create(['user_id' => $user?->id, 'session_id' => $sessionId, 'user_message' => $message, 'bot_reply' => $reply]);

        return response()->json(['reply' => $reply])->cookie('chat_session_id', $sessionId, 60*24*30);
    }
    
    public function clearChat(Request $request) {
        $sessionId = $request->cookie('chat_session_id');
        if($sessionId) AiChatHistory::where('session_id', $sessionId)->delete();
        return response()->json(['status' => 'cleared']);
    }

    private function wantsHuman(string $message): bool {
        $keywords = ['اپراتور', 'پشتیبان', 'ادمین', 'انسانی'];
        foreach ($keywords as $k) { if (str_contains($message, $k)) return true; } return false;
    }
}