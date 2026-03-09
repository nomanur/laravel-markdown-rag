<?php

namespace Nomanur\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Nomanur\Services\VectorService;

use Illuminate\Contracts\Auth\Authenticatable;

class KnowledgeSearchTool implements Tool
{
    public function __construct(
        protected ?Authenticatable $user = null,
        protected ?string $documentId = null,
        protected ?string $customDescription = null
    ) {}

    public function description(): Stringable|string
    {
        return $this->customDescription 
            ?? 'Search the internal knowledge base for information about the company, products, employees, and contracts.';
    }

    public function name(): string
    {
        return 'search_knowledge_base';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = $request['query'] ?? '';
        $documentId = $request['document_id'] ?? $this->documentId;
        
        if (empty($query)) {
            return 'Please provide a search query.';
        }

        $queryRewriteEnabled = config('laravel-markdown-rag.markdown_query_rewrite', false);
        if ($queryRewriteEnabled && $this->user) {
            $rewriteService = app(\Nomanur\Services\QueryRewriteService::class);
            $newQuery = $rewriteService->rewrite($query, $this->user);
            Log::alert($newQuery);
            \Illuminate\Support\Facades\Log::info('KnowledgeSearchTool: Query rewritten', [
                'from' => $query,
                'to' => $newQuery
            ]);
            $query = $newQuery;
        }

        $rerankingEnabled = config('laravel-markdown-rag.markdown_reranking', false);
        $limit = $rerankingEnabled ? 10 : 3;

        $vectorService = app(VectorService::class);
        $results = $vectorService->search($query, $limit, $documentId);

        if ($results->isEmpty()) {
            return 'No relevant information found in the knowledge base.';
        }

        if ($rerankingEnabled) {
            \Illuminate\Support\Facades\Log::info('KnowledgeSearchTool: Reranking is ENABLED. Fetching ' . $results->count() . ' results for reranking.');
            $rerankService = app(\Nomanur\Services\RerankService::class);
            $results = $rerankService->rerank($query, $results)->take(3);
            \Illuminate\Support\Facades\Log::info('KnowledgeSearchTool: Reranking complete. Top 3 results selected.');
        } else {
            \Illuminate\Support\Facades\Log::info('KnowledgeSearchTool: Reranking is DISABLED.');
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
            'document_id' => $schema->string()->description('Optional ID of a specific document to search within. If provided, search will be restricted to this document.'),
        ];
    }
}
