<?php

namespace Nomanur\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Nomanur\LaravelMarkdownRAGServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelMarkdownRAGServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('laravel-markdown-rag.markdown_info_path', 'knowledge-base');
        
        // Load manual mocks if package is missing
        if (!interface_exists(\Laravel\Ai\Contracts\Tool::class)) {
            require_once __DIR__ . '/Mocks/LaravelAiMocks.php';
        }

        // Setup dummy user for testing
        if (!class_exists(\App\Models\User::class)) {
            class_alias(\Nomanur\Tests\DummyUser::class, \App\Models\User::class);
        }
    }
}

class DummyUser extends \Illuminate\Foundation\Auth\User {
    protected $guarded = [];
}
