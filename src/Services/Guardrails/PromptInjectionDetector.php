<?php
namespace Unixscript\IranAiChatbot\Services\Guardrails;
use Unixscript\IranAiChatbot\Models\AiSetting;
class PromptInjectionDetector {
    public function isSafe(string $prompt): bool {
        if (!AiSetting::val('features.prompt_injection_protection', true)) return true;
        $blocked = ['نادیده بگیر', 'ignore previous', 'system prompt', 'دستورات قبلی'];
        foreach ($blocked as $word) { if (stripos($prompt, $word) !== false) return false; }
        return true;
    }
}