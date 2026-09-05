<?php
namespace Unixscript\IranAiChatbot\Drivers;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;

class GeminiDriver implements AiDriverInterface {
    protected $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function ask(string $prompt, array $history = []): string {
        // ۱. خواندن دستورالعمل رفتار سیستم
        $systemPrompt = config('iran-ai-chatbot.system.prompt', env('IRAN_AI_SYSTEM_PROMPT', 'شما یک دستیار هوشمند، مودب و پاسخگو هستید.'));

        $url = $this->config['endpoint'] . '?key=' . $this->config['api_key'];
        $contents = [];

        // ۲. تبدیل تاریخچه مکالمات به فرمت استاندارد جمینای (نقش‌ها فقط user یا model)
        foreach($history as $msg) {
            $role = ($msg['role'] == 'user') ? 'user' : 'model';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]]
            ];
        }

        // ۳. اضافه کردن پیام جدید کاربر
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ];

        // ۴. ساختن بدنه درخواست (Payload)
        $payload = ['contents' => $contents];

        // ۵. تزریق System Prompt به روش استاندارد API جمینای (در مدل‌های 1.5 به بالا)
        if (!empty($systemPrompt)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemPrompt]]
            ];
        }

        // ۶. ارسال درخواست
        $response = Http::timeout(60)
            ->withoutVerifying()
            ->post($url, $payload);

        if ($response->failed()) {
            throw new \Exception("Gemini API Error: " . $response->body());
        }

        return $response->json('candidates.0.content.parts.0.text') ?? 'خطا در دریافت پاسخ.';
    }
}