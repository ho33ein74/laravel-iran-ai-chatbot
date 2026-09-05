<?php
namespace Unixscript\IranAiChatbot\Drivers;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;

class LocalAiDriver implements AiDriverInterface {
    protected $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function ask(string $prompt, array $history = []): string {
        $systemPrompt = config('iran-ai-chatbot.system.prompt', env('IRAN_AI_SYSTEM_PROMPT', 'شما یک دستیار هوشمند، مودب و پاسخگو هستید.'));
        $messages = [];

        if (!empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }

        $messages = array_merge($messages, $history, [
            ['role' => 'user', 'content' => $prompt]
        ]);

        // درایورهای لوکال معمولاً نیازی به Bearer Token ندارند
        $response = Http::timeout(120) // تایم‌اوت بیشتر برای پردازش لوکال
        ->withoutVerifying()
            ->post($this->config['endpoint'], [
                'model' => $this->config['model'] ?? 'llama3',
                'messages' => $messages,
                'stream' => false // برای اینکه پاسخ به صورت یکجا دریافت شود
            ]);

        if ($response->failed()) {
            throw new \Exception("Local AI Error: " . $response->body());
        }

        // پاسخ Ollama و برخی مدل‌های لوکال در کلید message.content قرار دارد
        return $response->json('message.content') ?? $response->json('choices.0.message.content') ?? 'خطا در دریافت پاسخ.';
    }
}