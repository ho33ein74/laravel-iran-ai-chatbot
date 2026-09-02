<?php
namespace Unixscript\IranAiChatbot;
use Illuminate\Support\ServiceProvider;
use Unixscript\IranAiChatbot\Services\AiManager;
use Unixscript\IranAiChatbot\Http\Middleware\VerifyAiQuotaMiddleware;

class IranAiServiceProvider extends ServiceProvider {
    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../config/iran-ai-chatbot.php', 'iran-ai-chatbot');
        $this->app->singleton(AiManager::class, function ($app) { return new AiManager($app); });
    }
    public function boot() {
        $this->publishes([
            __DIR__.'/../config/iran-ai-chatbot.php' => config_path('iran-ai-chatbot.php'), 
            __DIR__.'/../resources/js/components' => resource_path('js/vendor/iran-ai-chatbot')
        ], 'iran-ai-chatbot-assets');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->app['router']->aliasMiddleware('ai.quota', VerifyAiQuotaMiddleware::class);
    }
}