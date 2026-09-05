<?php
namespace Unixscript\IranAiChatbot\Services\Guardrails;
class PiiMaskerService {
    public function mask(string $text): string {
        if (!config('iran-ai-chatbot.pii_masking', true)) return $text;
        $text = preg_replace('/(09\d{9})/', '[شماره مخفی شده]', $text);
        $text = preg_replace('/\b(\d{10})\b/', '[کدملی مخفی شده]', $text);
        return $text;
    }
}