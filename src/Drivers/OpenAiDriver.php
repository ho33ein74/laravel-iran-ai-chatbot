<?php
namespace Unixscript\IranAiChatbot\Drivers;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;

class OpenAiDriver implements AiDriverInterface {
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

        $response = Http::withToken($this->config['api_key'])
            ->timeout(60)
            ->withoutVerifying()
            ->post($this->config['endpoint'], [
                'model' => $this->config['model'] ?? 'gpt-4o-mini',
                'messages' => $messages
            ]);

        if ($response->failed()) {
            throw new \Exception("OpenAI API Error: " . $response->body());
        }

        return $response->json('choices.0.message.content') ?? 'خطا در دریافت پاسخ.';
    }
}