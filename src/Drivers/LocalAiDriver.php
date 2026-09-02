<?php
namespace Unixscript\IranAiChatbot\Drivers;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;
class LocalAiDriver implements AiDriverInterface {
    protected $config;
    public function __construct(array $config) { $this->config = $config; }
    public function ask(string $prompt, array $history = []): string {
        $response = Http::timeout(60)->post($this->config['endpoint'], ['model' => $this->config['model'], 'messages' => array_merge($history, [['role' => 'user', 'content' => $prompt]]), 'stream' => false]);
        if ($response->failed()) return "خطا در ارتباط با مدل لوکال.";
        return $response->json('message.content') ?? 'خطا.';
    }
}