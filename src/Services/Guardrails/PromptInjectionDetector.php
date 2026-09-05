<?php
namespace Unixscript\IranAiChatbot\Services\Guardrails;

class PromptInjectionDetector {
    public function isSafe(string $prompt): bool {
        if (!config('iran-ai-chatbot.features.prompt_injection_protection', true)) return true;
        $blocked = ['نادیده بگیر', 'ignore previous', 'system prompt', 'دستورات قبلی'];
        foreach ($blocked as $word) { if (stripos($prompt, $word) !== false) return false; }
        return true;
    }
}