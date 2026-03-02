<?php

namespace Nomanurrahman;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nomanurrahman\LaravelMarkdownRAG\LaravelMarkdownRAG
 */
class LaravelMarkdownRAGFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-markdown-rag';
    }
}
