<?php

namespace Nomanur;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Nomanur\Console\Commands\KnowledgeIndexCommand;
use Nomanur\Console\Commands\MarkdownRouteCommand;
use Nomanur\Http\Livewire\RagChat;

class LaravelMarkdownRAGServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-markdown-rag');
        $this->loadMigrationsFrom(__DIR__.'/Database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        if (class_exists(Livewire::class)) {
            Livewire::component('rag-chat', RagChat::class);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-markdown-rag.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-markdown-rag'),
            ], 'views');

            $this->publishes([
                __DIR__.'/../resources/views/without-flux.blade.php' => resource_path('views/without-flux.blade.php'),
            ], 'views');


            $this->publishes([
                __DIR__.'/../resources/views/components/without-flux.blade.php' => resource_path('views/components/without-flux.blade.php'),
            ], 'views');

            $this->publishes([
                __DIR__.'/Models/History.php.stub' => app_path('Models/History.php'),
            ], 'models');
            $this->publishes([
                __DIR__.'/Models/KnowledgeChunk.php.stub' => app_path('Models/KnowledgeChunk.php'),
            ], 'models');

            $this->publishes([
                __DIR__.'/Database/migrations' => database_path('migrations'),
            ], 'migrations');

            $this->commands([
                KnowledgeIndexCommand::class,
                MarkdownRouteCommand::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'laravel-markdown-rag');

        // Register the main class to use with the facade
        $this->app->singleton('laravel-markdown-rag', function () {
            return new LaravelMarkdownRAG;
        });
    }
}
