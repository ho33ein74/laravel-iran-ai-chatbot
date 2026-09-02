<?php
namespace Unixscript\IranAiChatbot\Drivers;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;
class GapGptDriver implements AiDriverInterface {
    protected $config;
    public function __construct(array $config) { $this->config = $config; }
    public function ask(string $prompt, array $history = []): string {
        $response = Http::withToken($this->config['api_key'])->post($this->config['endpoint'], ['messages' => array_merge($history, [['role' => 'user', 'content' => $prompt]])]);
        if ($response->failed()) throw new \Exception("GapGPT API Error");
        return $response->json('message.content') ?? 'خطا.';
    }
}