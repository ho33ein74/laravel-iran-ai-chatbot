<?php
namespace Unixscript\IranAiChatbot\Drivers;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;

class AvalAiDriver implements AiDriverInterface {
    protected $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function ask(string $prompt, array $history = []): string {
        // ۱. خواندن دستورالعمل رفتار سیستم (نقش و تخصص ربات)
        $systemPrompt = config('iran-ai-chatbot.system.prompt', env('IRAN_AI_SYSTEM_PROMPT', 'شما یک دستیار هوشمند، مودب و پاسخگو هستید.'));

        $messages = [];

        // ۲. اضافه کردن دستورالعمل به عنوان اولین پیام
        if (!empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }

        // ۳. ترکیب پیام سیستم، تاریخچه مکالمات قبلی و پیام جدید کاربر
        $messages = array_merge($messages, $history, [
            ['role' => 'user', 'content' => $prompt]
        ]);

        // ۴. ارسال درخواست به API
        $response = Http::withToken($this->config['api_key'])
            ->timeout(60)
            ->withoutVerifying()
            ->post($this->config['endpoint'], [
                'model' => $this->config['model'] ?? 'avalai-turtle',
                'messages' => $messages
            ]);

        if ($response->failed()) {
            // اضافه کردن بادی ارور کمک می‌کنه اگر توکنت مشکل داشت زودتر بفهمی
            throw new \Exception("AvalAI API Error: " . $response->body());
        }

        return $response->json('choices.0.message.content') ?? 'خطا در دریافت پاسخ.';
    }
}