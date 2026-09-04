<?php
namespace Unixscript\IranAiChatbot\Drivers;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;

class GapGptDriver implements AiDriverInterface {
    protected $config;
    public function __construct(array $config) { $this->config = $config; }

    public function ask(string $prompt, array $history = []): string {
        $response = Http::withToken($this->config['api_key'])
            ->timeout(60)
            ->post($this->config['endpoint'], [
                'model' => $this->config['model'] ?? 'gpt-4o-mini',
                'messages' => array_merge($history, [['role' => 'user', 'content' => $prompt]])
            ]);

        if ($response->failed()) {
            throw new \Exception("GapGPT API Error: " . $response->body());
        }

        return $response->json('choices.0.message.content') ?? $response->json('message.content') ?? 'خطا در دریافت پاسخ.';
    }
}