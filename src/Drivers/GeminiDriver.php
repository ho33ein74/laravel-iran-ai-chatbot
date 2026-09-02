<?php
namespace Unixscript\IranAiChatbot\Drivers;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Illuminate\Support\Facades\Http;
class GeminiDriver implements AiDriverInterface {
    protected $config;
    public function __construct(array $config) { $this->config = $config; }
    public function ask(string $prompt, array $history = []): string {
        $url = $this->config['endpoint'] . '?key=' . $this->config['api_key'];
        $contents = [];
        foreach($history as $msg) { $contents[] = ['role' => $msg['role'] == 'user' ? 'user' : 'model', 'parts' => [['text' => $msg['content']]]]; }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $prompt]]];
        $response = Http::timeout(60)->post($url, ['contents' => $contents]);
        if ($response->failed()) throw new \Exception("Gemini API Error");
        return $response->json('candidates.0.content.parts.0.text') ?? 'خطا.';
    }
}