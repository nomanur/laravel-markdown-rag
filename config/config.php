<?php

/*
 * You can place your custom package configuration in here.
 */
return [
    'openai_api_key' => env('OPENAI_API_KEY'),
    'markdown_chat_rate_limit' => env('MARKDOWN_CHAT_RATE_LIMIT'),

    'default' => env('AI_DEFAULT_PROVIDER', 'ollama'),
    'default_for_images' => env('AI_IMAGE_PROVIDER', 'gemini'),
    'default_for_audio' => env('AI_AUDIO_PROVIDER', 'openai'),
    'default_for_transcription' => env('AI_TRANSCRIPTION_PROVIDER', 'openai'),
    'default_for_embeddings' => env('AI_EMBEDDING_PROVIDER', 'openai'),
    'default_for_reranking' => 'cohere',
];