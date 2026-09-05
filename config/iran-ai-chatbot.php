<?php
return [
    'default_driver' => env('IRAN_AI_DRIVER', 'gapgpt'),

    'features' => [
        'rag_enabled' => env('IRAN_AI_RAG_ENABLED', true),
        'pii_masking' => env('IRAN_AI_PII_MASKING', true),
        'prompt_injection_protection' => env('IRAN_AI_PROMPT_INJECTION', true),
        'moderation' => env('IRAN_AI_MODERATION', true),
        'direct_db_suggest' => env('IRAN_AI_MODELS_SEARCH', true),
        'auth_required' => env('IRAN_AI_AUTH_REQUIRED', false),
    ],

    'models_search' => [
        'max_results' => env('IRAN_AI_SEARCH_LIMIT', 4),
        'searchable_models' => [
            //\App\Models\Product::class => [
           //    'columns' => ['title', 'description'],
           //    'label' => 'محصول',
           //    'url_template' => '/products/{id}' // <--- این خط اضافه شد
           //],
            // مثلا برای مقالات:
            // \App\Models\Article::class => [
            //     'columns' => ['title', 'body'],
            //     'label' => 'مقاله',
            //     'url_template' => '/blog/post/{id}'
            // ],
        ]
    ],

    'quota' => [
        'enabled' => env('IRAN_AI_QUOTA_ENABLED', true),
        'max_questions_per_user' => env('IRAN_AI_MAX_QUESTIONS', 20),
    ],

    'drivers' => [
        'avalai' => ['api_key' => env('AVALAI_API_KEY'), 'endpoint' => 'https://api.avalai.ir/v1/chat/completions', 'model' => 'avalai-turtle'],
        'gapgpt' => ['api_key' => env('GAPGPT_API_KEY'), 'endpoint' => 'https://api.gapgpt.app/v1/chat/completions', 'model' => 'glm-4-flash'],
        'openai' => ['api_key' => env('OPENAI_API_KEY'), 'endpoint' => 'https://api.openai.com/v1/chat/completions', 'model' => 'gpt-4o-mini'],
        'gemini' => ['api_key' => env('GEMINI_API_KEY'), 'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent'],
        'local_offline' => ['endpoint' => env('LOCAL_AI_ENDPOINT', 'http://localhost:11434/api/generate'), 'model' => 'llama3'],
    ],

    'system' => [
        'prompt' => env('IRAN_AI_SYSTEM_PROMPT', 'شما یک دستیار هوشمند و مودب هستید. به سوالات کاربر به زبان فارسی و با احترام پاسخ دهید.'),
    ],
];
