<?php
namespace Unixscript\IranAiChatbot\Drivers;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;
class AvalAiDriver implements AiDriverInterface {
    protected $config;
    public function __construct(array $config) { $this->config = $config; }
    public function ask(string $prompt, array $history = []): string {
        $response = Http::withToken($this->config['api_key'])->post($this->config['endpoint'], ['model' => $this->config['model'], 'messages' => array_merge($history, [['role' => 'user', 'content' => $prompt]])]);
        if ($response->failed()) throw new \Exception("AvalAI API Error");
        return $response->json('choices.0.message.content') ?? 'خطا.';
    }
}