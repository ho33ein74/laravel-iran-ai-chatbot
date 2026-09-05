<?php
use Illuminate\Support\Facades\Route;
use Unixscript\IranAiChatbot\Http\Controllers\ChatbotController;

Route::prefix('api/ai-chatbot')
    ->middleware(['web', \Illuminate\Routing\Middleware\SubstituteBindings::class])
    ->group(function () {
        Route::post('/chat', [ChatbotController::class, 'sendMessage'])->middleware('ai.quota');
        Route::post('/clear', [ChatbotController::class, 'clearChat']);
});