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
     * Uses simple keyword extraction for speed, with optional AI enhancement.
     */
    public function rewrite(string $query, User $user): string
    {
        // Check if AI prompt rewriting is enabled via config
        if (!config('laravel-markdown-rag.ai_prompt_rewriting', false)) {
            return $this->rewriteUsingKeywords($query);
        }

        return $this->rewriteUsingAi($query, $user);
    }

    /**
     * Rewrite query using simple keyword extraction (fast, no API call).
     */
    private function rewriteUsingKeywords(string $query): string
    {
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'shall', 'can', 'need', 'dare', 'ought', 'used', 'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 'from', 'as', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'between', 'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'just', 'and', 'but', 'if', 'or', 'because', 'until', 'while', 'although', 'though', 'after', 'before', 'what', 'which', 'who', 'whom', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them', 'my', 'your', 'his', 'its', 'our', 'their', 'mine', 'yours', 'hers', 'ours', 'theirs', 'myself', 'yourself', 'himself', 'herself', 'itself', 'ourselves', 'themselves'];
        
        // Remove stop words and get key terms
        $words = preg_split('/\s+/', strtolower($query));
        $keyTerms = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 2;
        });
        
        if (empty($keyTerms)) {
            return $query;
        }
        
        $rewrittenQuery = implode(' ', $keyTerms);
        
        Log::info('QueryRewriteService: Query rewritten (keyword extraction)', [
            'original' => $query,
            'rewritten' => $rewrittenQuery
        ]);

        return $rewrittenQuery;
    }

    /**
     * Rewrite query using AI (requires API call, provides better context awareness).
     */
    private function rewriteUsingAi(string $query, User $user): string
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

        $maxAttempts = config('laravel-markdown-rag.markdown_ai_retry_max_attempts', 3);
        $attempt = 1;

        while ($attempt <= $maxAttempts) {
            try {
                $rewrittenQuery = (string) Ai::prompt($prompt);
                
                Log::info('QueryRewriteService: Rewriting query', [
                    'original' => $query,
                    'rewritten' => $rewrittenQuery
                ]);

                return trim($rewrittenQuery);
            } catch (\Exception $e) {
                if ($attempt === $maxAttempts || !str_contains(strtolower($e->getMessage()), 'rate limit')) {
                    Log::error('QueryRewriteService: Error rewriting query', ['error' => $e->getMessage()]);
                    return $query;
                }

                $delay = pow(2, $attempt);
                Log::warning("QueryRewriteService: AI provider rate limited. Retrying in {$delay} seconds... (Attempt {$attempt}/{$maxAttempts})");
                sleep($delay);
                $attempt++;
            }
        }

        return $query;
    }
}
