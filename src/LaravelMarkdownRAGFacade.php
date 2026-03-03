<?php

namespace Nomanur;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nomanur\LaravelMarkdownRAG\LaravelMarkdownRAG
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
