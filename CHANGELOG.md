# Changelog

All notable changes to `laravel-markdown-rag` will be documented in this file

## 1.1.0 - 2026-03-08

- Added support for batch embedding generation to reduce API calls.
- Implemented exponential backoff retry logic for AI provider rate limits.
- Added new configuration options: `MARKDOWN_EMBEDDING_BATCH_SIZE` and `MARKDOWN_AI_RETRY_MAX_ATTEMPTS`.

## 1.2.0 - 2026-03-09

- Added dynamic system prompt and tool description based on selected knowledge document.
- Implemented caching for system prompt and tool description to reduce LLM token usage.
- Added `tool_description` and `system_prompt` fields to `KnowledgeDocument` model.
- Added cache invalidation on document update.


## 1.3.0 - 2026-03-10

- Added support for overwriting the knowledge system prompt and tool description.
- Added `tool_description` and `system_prompt` fields to `KnowledgeDocument` model.
- Added cache invalidation on document update.

