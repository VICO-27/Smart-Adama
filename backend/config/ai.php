<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LLM Provider
    |--------------------------------------------------------------------------
    | Default: "gemini".
    */
    'llm_provider' => env('AI_LLM_PROVIDER', 'gemini'),

    'ollama' => [
        'llm_base_url' => env('AI_LLM_BASE_URL', 'http://127.0.0.1:11434'),
        'llm_model' => env('AI_LLM_MODEL', 'qwen2.5:0.5b'),
        'max_tokens' => (int) env('AI_LLM_MAX_TOKENS', 2048),

        'embedding_base_url' => env('AI_EMBEDDING_BASE_URL', 'http://127.0.0.1:11434'),
        'embedding_model' => env('AI_EMBEDDING_MODEL', 'qwen3-embedding:0.6b'),
        'embedding_dimension' => (int) env('AI_EMBEDDING_DIMENSIONS', 1024),

        'timeout' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Timeouts (Seconds)
    |--------------------------------------------------------------------------
    | Default connect timeout: 5s. Default read/first token timeout: 8s.
    | Prevents long hangs during provider degradation or rate limiting.
    */
    'llm_timeout' => (int) env('AI_LLM_TIMEOUT', 20),
    'llm_connect_timeout' => (int) env('AI_LLM_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Embedding Timeouts (Seconds)
    |--------------------------------------------------------------------------
    */
    'embedding_timeout' => (int) env('AI_EMBEDDING_TIMEOUT', 20),
    'embedding_connect_timeout' => (int) env('AI_EMBEDDING_CONNECT_TIMEOUT', 10),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY', ''),
        'llm_model' => env('GEMINI_LLM_MODEL', 'gemini-3.5-flash-lite'),
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'embedding_dimension' => (int) env('GEMINI_EMBEDDING_DIMENSION', 1024),
        'timeout' => (int) env('GEMINI_TIMEOUT', 20),
        'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 10),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY', ''),
        'llm_model' => env('GROQ_LLM_MODEL', 'qwen/qwen3.8-27b'),
        'timeout' => (int) env('GROQ_TIMEOUT', 20),
        'connect_timeout' => (int) env('GROQ_CONNECT_TIMEOUT', 10),
    ],

    'voyage' => [
        'api_key' => env('VOYAGE_API_KEY', ''),
        'embedding_model' => env('VOYAGE_MODEL', 'voyage-large-2-instruct'),
        'embedding_dimension' => (int) env('VOYAGE_EMBEDDING_DIMENSION', 1024),
        'timeout' => (int) env('VOYAGE_TIMEOUT', 6),
        'connect_timeout' => (int) env('VOYAGE_CONNECT_TIMEOUT', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Embedding Provider
    |--------------------------------------------------------------------------
    | Default: "voyage".
    */
    'embedding_provider' => env('AI_EMBEDDING_PROVIDER', 'voyage'),

    /*
    |--------------------------------------------------------------------------
    | RAG Settings
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'top_k' => (int) env('RAG_TOP_K', 5),
        'similarity_threshold' => (float) env('RAG_SIMILARITY_THRESHOLD', 0.001),
        'chunk_target_tokens' => (int) env('RAG_CHUNK_TARGET_TOKENS', 700),
        'chunk_overlap_ratio' => (float) env('RAG_CHUNK_OVERLAP_RATIO', 0.15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'times' => 3,
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
