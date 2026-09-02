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
    
    public function getSettings() {
        return response()->json([
            'bot_name' => AiSetting::val('ui.bot_name', 'دستیار هوشمند پرس یار'),
            'primary_color' => AiSetting::val('ui.primary_color', '#3b82f6'),
            'display_mode' => AiSetting::val('ui.default_display_mode', 'popup'), 
            'layout_mode' => AiSetting::val('ui.default_layout', 'bubble'),
            'auth_required' => AiSetting::val('features.auth_required', false),
            'is_logged_in' => Auth::guard(config('auth.defaults.guard', 'web'))->check() || Auth::guard('api')->check() || Auth::guard('sanctum')->check(),
        ]);
    }

    public function sendMessage(Request $request, AiManager $aiManager, HumanEscalationService $escalation, PiiMaskerService $pii, PromptInjectionDetector $injection, ModerationService $mod, ModelSearchService $dbSearch) {
        $request->validate(['message' => 'required|string']);
        $sessionId = $request->cookie('chat_session_id') ?? (string) Str::uuid();
        $message = $request->message;

        $isLoggedIn = Auth::guard(config('auth.defaults.guard', 'web'))->check() || Auth::guard('api')->check() || Auth::guard('sanctum')->check();
        
        $user = null;
        if ($isLoggedIn) {
             $user = Auth::guard('api')->user() ?? Auth::guard('sanctum')->user() ?? Auth::guard('web')->user() ?? $request->user();
        }
        
        $authRequired = AiSetting::val('features.auth_required', false);

        if ($authRequired && !$isLoggedIn) {
            return response()->json(['reply' => 'برای استفاده از گفتگو باید وارد حساب کاربری خود شوید.', 'needs_login' => true], 401);
        }

        if (!$injection->isSafe($message)) return response()->json(['reply' => 'درخواست شما مغایر با قوانین سیستم است.']);
        if (!$mod->isClean($message)) return response()->json(['reply' => 'لطفاً از کلمات مناسب استفاده کنید.']);
        
        $message = $pii->mask($message);

        if ($this->wantsHuman($message)) {
            $escalation->assignToAdmin($user, $sessionId, $message);
            return response()->json(['reply' => 'درخواست ثبت شد. به زودی پشتیبان پاسخ می‌دهد.'])->cookie('chat_session_id', $sessionId, 60*24*30);
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