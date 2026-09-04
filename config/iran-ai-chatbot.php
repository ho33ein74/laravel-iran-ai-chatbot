<?php
return [
    'default_driver' => env('IRAN_AI_DRIVER', 'avalai'),
    
    'features' => [
        'rag_enabled' => true,
        'pii_masking' => true, 
        'prompt_injection_protection' => true, 
        'moderation' => true,
        'direct_db_suggest' => true,
        'auth_required' => env('IRAN_AI_AUTH_REQUIRED', false),
    ],

    'models_search' => [
        'max_results' => 4, 
        'searchable_models' => [
            // \App\Models\Product::class => ['columns' => ['title', 'description'], 'label' => 'محصول'],
        ]
    ],

    'quota' => [
        'enabled' => true,
        'max_questions_per_user' => 20,
    ],

    'drivers' => [
        'avalai' => ['api_key' => env('AVALAI_API_KEY'), 'endpoint' => 'https://api.avalai.ir/v1/chat/completions', 'model' => 'avalai-turtle'],
        'gapgpt' => ['api_key' => env('GAPGPT_API_KEY'), 'endpoint' => 'https://api.gapgpt.ir/v1/chat'],
        'openai' => ['api_key' => env('OPENAI_API_KEY'), 'endpoint' => 'https://api.openai.com/v1/chat/completions', 'model' => 'gpt-4o-mini'],
        'gemini' => ['api_key' => env('GEMINI_API_KEY'), 'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent'],
        'local_offline' => ['endpoint' => env('LOCAL_AI_ENDPOINT', 'http://localhost:11434/api/generate'), 'model' => 'llama3'],
    ],

    'ui' => [
        'primary_color' => '#3b82f6',
        'bot_name' => 'دستیار هوشمند یونیکس',
        'default_display_mode' => 'popup', 
        'default_layout' => 'bubble', 
    ]
];
