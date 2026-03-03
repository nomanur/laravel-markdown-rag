<?php

namespace Nomanur\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Nomanur\Services\VectorService;

class KnowledgeSearchTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Search the internal knowledge base for information about the company, products, employees, and contracts.';
    }

    public function name(): string
    {
        return 'search_knowledge_base';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = $request['query'] ?? '';
        
        if (empty($query)) {
            return 'Please provide a search query.';
        }

        $vectorService = app(VectorService::class);
        $results = $vectorService->search($query, 3);

        if ($results->isEmpty()) {
            return 'No relevant information found in the knowledge base.';
        }

        $formatted = $results->map(function ($result) {
            return "Source: {$result->source}\nContent: {$result->content}";
        })->implode("\n---\n");

        return "Search results from knowledge base:\n\n{$formatted}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('The search query to look up in the knowledge base.')->required(),
        ];
    }
}
