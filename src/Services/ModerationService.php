<?php
namespace Unixscript\IranAiChatbot\Services;
use Unixscript\IranAiChatbot\Models\AiSetting;
class ModerationService {
    public function isClean(string $text): bool {
        if (!AiSetting::val('features.moderation', true)) return true;
        $badWords = ['توهین۱'];
        foreach ($badWords as $word) { if (stripos($text, $word) !== false) return false; }
        return true;
    }
}