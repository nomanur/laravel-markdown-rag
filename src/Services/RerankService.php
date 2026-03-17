<?php

namespace Nomanur\Services;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RerankService implements Agent
{
    use Promptable;

    public function instructions(): \Stringable|string
    {
        return 'You are a document re-ranker.';
    }

    /**
     * Rerank a list of chunks based on a question.
     * Uses keyword matching for speed, with optional AI enhancement.
     *
     * @param string $question
     * @param Collection $chunks
     * @return Collection
     */
    public function rerank(string $question, Collection $chunks): Collection
    {
        // Check if AI reranking is enabled via config
        if (!config('laravel-markdown-rag.ai_prompt_rewriting', false)) {
            return $this->rerankUsingKeywords($question, $chunks);
        }

        return $this->rerankUsingAi($question, $chunks);
    }

    /**
     * Rerank chunks using keyword matching (fast, no AI calls).
     *
     * @param string $question
     * @param Collection $chunks
     * @return Collection
     */
    private function rerankUsingKeywords(string $question, Collection $chunks): Collection
    {
        if ($chunks->isEmpty()) {
            return $chunks;
        }

        // Extract key terms from question
        $stopWords = ['the', 'a', 'an', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'shall', 'can', 'need', 'dare', 'ought', 'used', 'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 'from', 'as', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'between', 'under', 'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why', 'how', 'all', 'each', 'few', 'more', 'most', 'other', 'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 'too', 'very', 'just', 'and', 'but', 'if', 'or', 'because', 'until', 'while', 'although', 'though', 'after', 'before', 'what', 'which', 'who', 'whom', 'this', 'that', 'these', 'those', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'her', 'us', 'them', 'my', 'your', 'his', 'its', 'our', 'their', 'mine', 'yours', 'hers', 'ours', 'theirs', 'myself', 'yourself', 'himself', 'herself', 'itself', 'ourselves', 'themselves'];
        
        $questionWords = preg_split('/\s+/', strtolower($question));
        $keyTerms = array_filter($questionWords, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 2;
        });
        
        if (empty($keyTerms)) {
            return $chunks;
        }

        // Score each chunk based on keyword matches
        $scoredChunks = $chunks->map(function($chunk) use ($keyTerms) {
            $content = strtolower($chunk->content);
            $score = 0;
            
            foreach ($keyTerms as $term) {
                // Count occurrences of each key term
                $count = substr_count($content, $term);
                $score += $count * strlen($term); // Weight by term length
            }
            
            $chunk->rerank_score = $score;
            return $chunk;
        });

        // Sort by score descending
        $reranked = $scoredChunks->sortByDesc('rerank_score')->values();

        Log::info('RerankService: Keyword-based reranking complete', [
            'chunks_count' => $chunks->count(),
            'key_terms' => count($keyTerms)
        ]);

        return $reranked;
    }

    /**
     * Rerank chunks using AI (requires API call, provides better relevance).
     *
     * @param string $question
     * @param Collection $chunks
     * @return Collection
     */
    private function rerankUsingAi(string $question, Collection $chunks): Collection
    {
        if ($chunks->isEmpty()) {
            return $chunks;
        }

        $systemPrompt = <<<EOT
You are a document re-ranker.
You are provided with a question and a list of relevant chunks of text from a query of a knowledge base.
The chunks are provided in the order they were retrieved; this should be approximately ordered by relevance, but you may be able to improve on that.
You must rank order the provided chunks by relevance to the question, with the most relevant chunk first.
Reply only with the list of ranked chunk ids, nothing else. Include all the chunk ids you are provided with, reranked.
EOT;

        $userPrompt = "The user has asked the following question:\n\n{$question}\n\n";
        $userPrompt .= "Order all the chunks of text by relevance to the question, from most relevant to least relevant. Include all the chunk ids you are provided with, reranked.\n\n";
        $userPrompt .= "Here are the chunks:\n\n";

        foreach ($chunks as $index => $chunk) {
            $id = $index + 1;
            $userPrompt .= "# CHUNK ID: {$id}:\n\n{$chunk->content}\n\n";
        }

        $userPrompt .= "Reply only with the list of ranked chunk ids, nothing else.";

        // Using Laravel\Ai\Promptable trait capabilities
        $prompt = $systemPrompt . "\n\n" . $userPrompt;

        $maxAttempts = config('laravel-markdown-rag.markdown_ai_retry_max_attempts', 3);
        $attempt = 1;
        $response = '';

        while ($attempt <= $maxAttempts) {
            try {
                $response = (string) $this->prompt($prompt);
                break;
            } catch (\Exception $e) {
                if ($attempt === $maxAttempts || !str_contains(strtolower($e->getMessage()), 'rate limit')) {
                    Log::error('RerankService: Error prompting AI', ['error' => $e->getMessage()]);
                    return $chunks;
                }

                $delay = pow(2, $attempt);
                Log::warning("RerankService: AI provider rate limited. Retrying in {$delay} seconds... (Attempt {$attempt}/{$maxAttempts})");
                sleep($delay);
                $attempt++;
            }
        }

        Log::info('RerankService: Raw LLM response: ' . $response);

        // Parse the response for IDs. Expecting something like "2, 1, 3" or similar list of IDs.
        preg_match_all('/\d+/', $response, $matches);
        $order = $matches[0];

        Log::info('RerankService: Parsed order: ' . implode(', ', $order));

        if (empty($order)) {
            Log::warning('RerankService: No IDs parsed from LLM response.');
            return $chunks;
        }

        $reranked = new Collection();
        foreach ($order as $id) {
            $index = (int)$id - 1;
            if ($chunks->has($index)) {
                $reranked->push($chunks->get($index));
            }
        }

        // Add any chunks that might have been missed by the LLM (safety)
        foreach ($chunks as $index => $chunk) {
            if (!$reranked->contains($chunk)) {
                $reranked->push($chunk);
            }
        }

        return $reranked;
    }
}
