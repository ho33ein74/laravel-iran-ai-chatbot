<?php
use Illuminate\Support\Facades\Route;
use Unixscript\IranAiChatbot\Http\Controllers\ChatbotController;

Route::prefix('api/ai-chatbot')
    ->middleware(['api', \Illuminate\Routing\Middleware\SubstituteBindings::class])
    ->group(function () {
        Route::get('/settings', [ChatbotController::class, 'getSettings']);
        Route::post('/chat', [ChatbotController::class, 'sendMessage'])->middleware('ai.quota');
        Route::post('/clear', [ChatbotController::class, 'clearChat']);
});