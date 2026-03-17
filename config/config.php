<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'openai_api_key' => env('OPENAI_API_KEY'),
    'markdown_chat_rate_limit' => env('MARKDOWN_CHAT_RATE_LIMIT'),
    'markdown_reranking' => env('MARKDOWN_RERANKING', false),
    'markdown_query_rewrite' => env('MARKDOWN_QUERY_REWRITE', false),
    'ai_prompt_rewriting' => env('AI_PROMPT_REWRITING', false),
    'markdown_info_path' => env('MARKDOWN_INFO_PATH', 'knowledge-base'),
    'markdown_embedding_batch_size' => env('MARKDOWN_EMBEDDING_BATCH_SIZE', 50),
    'markdown_ai_retry_max_attempts' => env('MARKDOWN_AI_RETRY_MAX_ATTEMPTS', 3),
    'markdown_default_agent_prompt' => env('MARKDOWN_DEFAULT_AGENT_PROMPT', "You are a helpful assistant searching through the knowledge base..."),
];