<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LLM Provider
    |--------------------------------------------------------------------------
    | Default: "ollama".
    */
    'llm_provider' => env('AI_LLM_PROVIDER', 'ollama'),

    'ollama' => [
        'llm_base_url' => env('AI_LLM_BASE_URL', 'http://127.0.0.1:11434'),
        'llm_model'    => env('AI_LLM_MODEL', 'qwen2.5:0.5b'),
        'max_tokens'   => (int) env('AI_LLM_MAX_TOKENS', 2048),
        
        'embedding_base_url'  => env('AI_EMBEDDING_BASE_URL', 'http://127.0.0.1:11434'),
        'embedding_model'     => env('AI_EMBEDDING_MODEL', 'qwen3-embedding:0.6b'),
        'embedding_dimension' => (int) env('AI_EMBEDDING_DIMENSIONS', 1024),
        
        'timeout' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Provider
    |--------------------------------------------------------------------------
    | Default: "ollama".
    */
    'embedding_provider' => env('AI_EMBEDDING_PROVIDER', 'ollama'),

    /*
    |--------------------------------------------------------------------------
    | RAG Settings
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'top_k'               => (int) env('RAG_TOP_K', 5),
        'similarity_threshold' => (float) env('RAG_SIMILARITY_THRESHOLD', 0.001),
        'chunk_target_tokens'  => (int) env('RAG_CHUNK_TARGET_TOKENS', 700),
        'chunk_overlap_ratio'  => (float) env('RAG_CHUNK_OVERLAP_RATIO', 0.15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'times'   => 3,
        'backoff' => [500, 1000, 2000], // ms
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'chat_rate_limit' => [
        'max_attempts' => (int) env('CHAT_RATE_LIMIT_PER_5_MIN', 20),
        'decay_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ingestion Pipeline
    |--------------------------------------------------------------------------
    */
    'ingestion_sleep_seconds' => (int) env('INGESTION_SLEEP_SECONDS', 0),

    /*
    |--------------------------------------------------------------------------
    | Platform Knowledge (Global Assistant)
    |--------------------------------------------------------------------------
    */
    'platform' => [
        'developers' => [
            'Project Manager & Integration Lead: Ashenafi Deresa Feyisa',
            'Backend: Kidus Tilahun',
            'DevOps/QA: Nigusu Wario',
            'Frontend/UI: Getamesay Mekcha',
            'AI/RAG Lead: Abinet Tesfaye',
        ],
    ],

];
