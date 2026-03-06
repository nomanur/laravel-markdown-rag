<?php

namespace Nomanur\Services;

use Nomanur\Models\History;
use App\Models\User;
use Laravel\Ai\Ai;
use Illuminate\Support\Facades\Log;

class QueryRewriteService
{
    /**
     * Rewrite the user's query to be a standalone search query.
     */
    public function rewrite(string $query, User $user): string
    {
        $history = History::where('user_id', $user->id)
            ->where('agent', 'knowledge')
            ->latest()
            ->limit(5)
            ->get()
            ->reverse()
            ->map(fn($message) => "{$message->role}: {$message->content}")
            ->implode("\n");

        if (empty($history)) {
            return $query;
        }

        $prompt = <<<EOT
Given the following conversation history and a follow-up question, rephrase the follow-up question to be a standalone search query that can be used to search a vector database.
If the question is already standalone, return it as is.
Do not include any preamble, just the rephrased query.

History:
{$history}

Follow-up: {$query}
Standalone Query:
EOT;

        try {
            $rewrittenQuery = (string) Ai::prompt($prompt);
            
            Log::info('QueryRewriteService: Rewriting query', [
                'original' => $query,
                'rewritten' => $rewrittenQuery
            ]);

            return trim($rewrittenQuery);
        } catch (\Exception $e) {
            Log::error('QueryRewriteService: Error rewriting query', ['error' => $e->getMessage()]);
            return $query;
        }
    }
}
