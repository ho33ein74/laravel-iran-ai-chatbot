<?php
namespace Unixscript\IranAiChatbot\Services;
use Illuminate\Foundation\Application;
use Unixscript\IranAiChatbot\Contracts\AiDriverInterface;
use Unixscript\IranAiChatbot\Models\AiSetting;

class AiManager {
    protected $app;
    public function __construct(Application $app) { $this->app = $app; }
    
    public function driver(?string $name = null): AiDriverInterface {
        $name = $name ?: AiSetting::val('default_driver', config('iran-ai-chatbot.default_driver'));
        
        $config = [
            'api_key' => AiSetting::val("drivers.{$name}.api_key", config("iran-ai-chatbot.drivers.{$name}.api_key")),
            'endpoint' => AiSetting::val("drivers.{$name}.endpoint", config("iran-ai-chatbot.drivers.{$name}.endpoint")),
            'model' => AiSetting::val("drivers.{$name}.model", config("iran-ai-chatbot.drivers.{$name}.model")),
        ];

        $driverClass = "\\Unixscript\\IranAiChatbot\\Drivers\\" . str_replace(' ', '', ucwords(str_replace('_', ' ', $name))) . "Driver";
        
        // Handle specific naming
        if ($name === 'local_offline') $driverClass = "\\Unixscript\\IranAiChatbot\\Drivers\\LocalAiDriver";
        if ($name === 'gapgpt') $driverClass = "\\Unixscript\\IranAiChatbot\\Drivers\\GapGptDriver";
        if ($name === 'avalai') $driverClass = "\\Unixscript\\IranAiChatbot\\Drivers\\AvalAiDriver";
        if ($name === 'openai') $driverClass = "\\Unixscript\\IranAiChatbot\\Drivers\\OpenAiDriver";
        if ($name === 'gemini') $driverClass = "\\Unixscript\\IranAiChatbot\\Drivers\\GeminiDriver";

        if (class_exists($driverClass)) {
            return new $driverClass($config);
        }
        throw new \InvalidArgumentException("Driver [{$name}] not supported.");
    }
}