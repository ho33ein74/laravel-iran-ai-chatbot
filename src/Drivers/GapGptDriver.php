<?php
namespace Unixscript\IranAiChatbot\Drivers;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;

class GapGptDriver implements AiDriverInterface {
    protected $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function ask(string $prompt, array $history = []): string {
        // ۱. خواندن دستورالعمل رفتار سیستم (نقش و تخصص ربات)
        $systemPrompt = config('iran-ai-chatbot.system.prompt', env('IRAN_AI_SYSTEM_PROMPT', 'شما یک دستیار هوشمند، مودب و پاسخگو هستید.'));

        $messages = [];

        // ۲. اضافه کردن دستورالعمل به عنوان اولین پیام (با نقش system)
        if (!empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }

        // ۳. ترکیب پیام سیستم، تاریخچه مکالمات قبلی و در نهایت پیام جدید کاربر
        $messages = array_merge($messages, $history, [
            ['role' => 'user', 'content' => $prompt]
        ]);

        // ۴. ارسال درخواست نهایی به API
        $response = Http::withToken($this->config['api_key'])
            ->timeout(60)
            ->withoutVerifying()
            ->post($this->config['endpoint'], [
                'model' => $this->config['model'] ?? 'gpt-4o-mini',
                'messages' => $messages
            ]);

        if ($response->failed()) {
            throw new \Exception("GapGPT API Error: " . $response->body());
        }

        return $response->json('choices.0.message.content') ?? $response->json('message.content') ?? 'خطا در دریافت پاسخ.';
    }
}