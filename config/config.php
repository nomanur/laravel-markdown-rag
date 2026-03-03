<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'openai_api_key' => env('OPENAI_API_KEY'),
    'markdown_chat_rate_limit' => env('MARKDOWN_CHAT_RATE_LIMIT'),
    'markdown_reranking' => env('MARKDOWN_RERANKING', false),
];