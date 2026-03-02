```markdown
# Laravel Markdown RAG

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nomanurrahman/laravel-markdown-rag.svg?style=flat-square)](https://packagist.org/packages/nomanurrahman/laravel-markdown-rag)
[![Total Downloads](https://img.shields.io/packagist/dt/nomanurrahman/laravel-markdown-rag.svg?style=flat-square)](https://packagist.org/packages/nomanurrahman/laravel-markdown-rag)
![GitHub Actions](https://github.com/nomanurrahman/laravel-markdown-rag/actions/workflows/main.yml/badge.svg)

Laravel Markdown RAG is a package that allows you to build a Retrieval-Augmented Generation (RAG) system using Markdown files as your knowledge base, powered by Gemini AI.

## Installation

You can install the package via composer:

```bash
composer require nomanurrahman/laravel-markdown-rag
```

Publish the package assets and configuration:

```bash
php artisan vendor:publish --provider="Nomanurrahman\LaravelMarkdownRag\LaravelMarkdownRagServiceProvider"
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
MARKDOWN_CHAT_RATE_LIMIT=2 (optional) //if u want to rate the limit for the chat
```

## Usage

### 1. Register Routes
Run the following command to register the necessary routes:

```bash
php artisan markdownrag:route
```

### 2. Setup Knowledge Base
Create a folder for your markdown files within the `public` directory (e.g., `public/knowledge-base`) and add your `.md` files there.

### 3. Indexing
Index your markdown files to make them searchable:

```bash
php artisan markdownrag:index
```

### 4. Accessing the Interface
You can access the chat interface at the following URL:
`your-domain.com/markdownrag`

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

- [nomanur rahman](https://github.com/nomanurrahman)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
```
