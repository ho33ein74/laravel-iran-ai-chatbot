<?php
namespace Unixscript\IranAiChatbot\Services;
class ModerationService {
    public function isClean(string $text): bool {
        if (!config('iran-ai-chatbot.features.moderation', true)) return true;
        $badWords = ['توهین۱'];
        foreach ($badWords as $word) { if (stripos($text, $word) !== false) return false; }
        return true;
    }
}