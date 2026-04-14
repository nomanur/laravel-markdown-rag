# Laravel Markdown RAG

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nomanur/laravel-markdown-rag.svg?style=flat-square)](https://packagist.org/packages/nomanur/laravel-markdown-rag)
[![Total Downloads](https://img.shields.io/packagist/dt/nomanur/laravel-markdown-rag.svg?style=flat-square)](https://packagist.org/packages/nomanur/laravel-markdown-rag)

Laravel Markdown RAG is a package that allows you to build a Retrieval-Augmented Generation (RAG) system using Markdown files as your knowledge base, powered by Gemini AI.

## Installation

You can install the package via composer:

```bash
composer require nomanur/laravel-markdown-rag
```

Publish the package assets and configuration:

```bash
php artisan vendor:publish --provider="Nomanur\LaravelMarkdownRAGServiceProvider"
php artisan vendor:publish --provider="Laravel\Ai\AiServiceProvider"
```

Run the migrations:

```bash
php artisan migrate
```

## Configuration

Add the following environment variables to your `.env` file:

```env
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash
AI_EMBEDDING_PROVIDER=gemini
AI_DEFAULT_PROVIDER=gemini
MARKDOWN_INFO_PATH=knowledge-base
MARKDOWN_EMBEDDING_BATCH_SIZE=50
MARKDOWN_AI_RETRY_MAX_ATTEMPTS=3
MARKDOWN_QUERY_REWRITE=false
MARKDOWN_RERANKING=false
```

- `MARKDOWN_CHAT_RATE_LIMIT`: 5 (optional) if you want to rate the limit for the chat.
- `MARKDOWN_INFO_PATH`: (optional) the path to the markdown files in the public directory.
- `MARKDOWN_EMBEDDING_BATCH_SIZE`: (optional) the number of chunks to process in a single embedding request (default: 50).
- `MARKDOWN_AI_RETRY_MAX_ATTEMPTS`: (optional) the maximum number of retry attempts for AI requests (default: 3).
- `MARKDOWN_QUERY_REWRITE`: (optional) whether to enable query rewriting for better retrieval (default: false).
- `MARKDOWN_RERANKING`: (optional) whether to enable reranking of search results (default: false).

To use the configuration, update the code in `config/ai.php` with:

```php
'default' => env('AI_DEFAULT_PROVIDER', 'ollama'),
'default_for_images' => env('AI_IMAGE_PROVIDER', 'gemini'),
'default_for_audio' => env('AI_AUDIO_PROVIDER', 'openai'),
'default_for_transcription' => env('AI_TRANSCRIPTION_PROVIDER', 'openai'),
'default_for_embeddings' => env('AI_EMBEDDING_PROVIDER', 'openai'),
'default_for_reranking' => env('AI_RERANKING_PROVIDER', 'cohere'),

'providers' => [
    'gemini' => [
        'driver' => 'gemini',
        'key' => env('GEMINI_API_KEY'),
        'models' => [
            'text' => [
                'default' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
                'cheapest' => env('GEMINI_MODEL_CHEAPEST', 'gemini-1.5-flash'),
                'smartest' => env('GEMINI_MODEL_SMARTEST', 'gemini-2.0-pro-exp-02-05'),
            ],
        ],
    ],
],
```

## Usage

### 1. Register Routes
Run the following command to register the necessary routes:

```bash
php artisan markdownrag:route
```

### 2. Setup Knowledge Base
Create a folder for your markdown files within the `public` directory. By default, this is `public/knowledge-base`, but you can change it using the `MARKDOWN_INFO_PATH` environment variable. Add your `.md` files there.

Example:
```
public/
└── knowledge-base/
    ├── company/
    │   ├── file1.md
    │   ├── file2.md
    │   └── file3.md
    └── product/
        ├── file1.md
        ├── file2.md
        └── file3.md
```

### 3. Indexing
Index your markdown files to make them searchable:

```bash
php artisan markdownrag:index
```

### 4. Start Queue Worker
Start the queue worker to process background jobs:

```bash
php artisan queue:work
```

### 5. Accessing the Interface
You can access the chat interface at the following URL:
`your-domain.com/markdownrag`

### 6. Customizing Message History
By default, `KnowledgeAgent` retrieves messages from the `History` model. You can customize how messages are resolved using one of the following options:

#### Option 1: Global override in `AppServiceProvider`
Use the `resolveMessagesUsing` static method to customize message resolution globally:

```php
use Nomanur\Ai\Agents\KnowledgeAgent;
use Nomanur\Models\History;
use Laravel\Ai\Messages\Message;

KnowledgeAgent::resolveMessagesUsing(function ($agent) {
    return History::where('user_id', $agent->user->id)
        ->where('agent', 'knowledge')
        ->latest()
        ->skip(1) // Your custom skip logic
        ->when(config('laravel-markdown-rag.markdown_chat_rate_limit'), fn($query, $limit) => $query->limit($limit))
        ->get()
        ->reverse()
        ->map(fn($message) => new Message($message->role, $message->content))
        ->all();
});
```

#### Option 2: Inheritance in another project
If you are extending the agent in another project, you can override the `messages()` method directly:

```php
use Nomanur\Ai\Agents\KnowledgeAgent;
use Nomanur\Models\History;
use Laravel\Ai\Messages\Message;

class ExtendedKnowledgeAgent extends KnowledgeAgent
{
    public function messages(): iterable
    {
        // Custom implementation with skip(1)
        return History::where('user_id', $this->user->id)
            ->where('agent', 'knowledge')
            ->latest()
            ->skip(1)
            ->when(config('laravel-markdown-rag.markdown_chat_rate_limit'), fn($query, $limit) => $query->limit($limit))
            ->get()
            ->reverse()
            ->map(fn($message) => new Message($message->role, $message->content))
            ->all();
    }
}
```

### 7. Customizing Instructions
By default, `KnowledgeAgent` retrieves its instructions from the associated document's `system_prompt` or falls back to a default prompt. You can customize the agent's instructions using one of the following options:

#### Option 1: Global override in `AppServiceProvider`
Use the `resolveInstructionsUsing` static method to customize instructions globally:

```php
use Nomanur\Ai\Agents\KnowledgeAgent;

KnowledgeAgent::resolveInstructionsUsing(function (KnowledgeAgent $agent) {
    if ($agent->document) {
        return "You are an expert on {$agent->document->name}. Answer questions strictly based on it.";
    }
    
    return "You are a helpful AI assistant.";
});
```

#### Option 2: Inheritance in another project
You can also override the `instructions()` method directly by extending the agent:

```php
use Nomanur\Ai\Agents\KnowledgeAgent;
use Stringable;

class ExtendedKnowledgeAgent extends KnowledgeAgent
{
    public function instructions(): Stringable|string
    {
        return "You are a highly capable and friendly assistant.";
    }
}
```

### 8. Customizing Tool Descriptions
You can also override the description of the `KnowledgeSearchTool` globally. This is useful if you want to give the AI more specific instructions about when to use the search tool:

```php
use Nomanur\Ai\Tools\KnowledgeSearchTool;

KnowledgeSearchTool::resolveDescriptionUsing(function (KnowledgeSearchTool $tool) {
    return "Search the internal knowledge base for company policies and HR documents only.";
});
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email nomanurrahman@gmail.com instead of using the issue tracker.

## Credits

- [nomanur rahman](https://github.com/nomanur)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
