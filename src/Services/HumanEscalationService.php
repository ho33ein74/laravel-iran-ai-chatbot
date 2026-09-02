<?php
namespace Unixscript\IranAiChatbot\Services;
use Unixscript\IranAiChatbot\Models\AiChatHistory;
class HumanEscalationService {
    public function assignToAdmin($user, string $sessionId, string $lastMessage): void {
        AiChatHistory::create(['user_id' => $user?->id, 'session_id' => $sessionId, 'user_message' => $lastMessage, 'bot_reply' => 'درخواست ارجاع شد.', 'requires_admin' => true]);
    }
}