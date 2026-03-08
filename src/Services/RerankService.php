<?php

namespace Nomanur\Services;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Illuminate\Support\Collection;

class RerankService implements Agent
{
    use Promptable;

    public function instructions(): \Stringable|string
    {
        return 'You are a document re-ranker.';
    }

    /**
     * Rerank a list of chunks based on a question using an LLM.
     *
     * @param string $question
     * @param Collection $chunks
     * @return Collection
     */
    public function rerank(string $question, Collection $chunks): Collection
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
                    \Illuminate\Support\Facades\Log::error('RerankService: Error prompting AI', ['error' => $e->getMessage()]);
                    return $chunks;
                }

                $delay = pow(2, $attempt);
                \Illuminate\Support\Facades\Log::warning("RerankService: AI provider rate limited. Retrying in {$delay} seconds... (Attempt {$attempt}/{$maxAttempts})");
                sleep($delay);
                $attempt++;
            }
        }

        \Illuminate\Support\Facades\Log::info('RerankService: Raw LLM response: ' . $response);

        // Parse the response for IDs. Expecting something like "2, 1, 3" or similar list of IDs.
        preg_match_all('/\d+/', $response, $matches);
        $order = $matches[0];

        \Illuminate\Support\Facades\Log::info('RerankService: Parsed order: ' . implode(', ', $order));

        if (empty($order)) {
            \Illuminate\Support\Facades\Log::warning('RerankService: No IDs parsed from LLM response.');
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
