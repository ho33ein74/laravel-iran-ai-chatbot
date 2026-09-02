<?php
namespace Unixscript\IranAiChatbot\Contracts;
interface AiDriverInterface { 
    public function ask(string $prompt, array $history = []): string; 
}