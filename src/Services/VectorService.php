<?php

namespace Nomanur\Services;

use Nomanur\Models\KnowledgeChunk;
use Laravel\Ai\Embeddings;

class VectorService
{
    /**
     * Get embeddings for the given texts.
     */
    public function getEmbeddings(array $texts): array
    {
        return Embeddings::for($texts)->generate()->embeddings;
    }

    /**
     * Store a chunk and its embedding in the database.
     */
    public function storeChunk(string $content, array $embedding, string $source, array $metadata = []): KnowledgeChunk
    {
        return KnowledgeChunk::create([
            'content' => $content,
            'embedding' => $embedding,
            'source' => $source,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Search for similar knowledge chunks.
     */
    public function search(string $query, int $limit = 5)
    {
        $queryEmbedding = $this->getEmbeddings([$query])[0];
        
        return KnowledgeChunk::similaritySearch($queryEmbedding, $limit);
    }

    public function reduceDimensions(array $embeddings): array
    {
        $tsne = new TSNE([
            'perplexity' => min(30.0, count($embeddings) - 1),
            'eta' => 100.0
        ]);
        
        return $tsne->run($embeddings, 150); // 150 iterations for speed
    }

    public function chunkDocuments(array $documents): array
    {
        $splitter = new TextSplitter(1000, 100);
        $chunks = [];

        foreach ($documents as $doc) {
            $path = $doc['path'];
            $content = file_get_contents($path);
            $splits = $splitter->splitText($content);

            foreach ($splits as $split) {
                $chunks[] = [
                    'text' => $split,
                    'source' => basename($path),
                ];
            }
        }

        return $chunks;
    }

    public function countCharacters(array $documents): int
    {
        $total = 0;
        foreach ($documents as $doc) {
            $total += mb_strlen(file_get_contents($doc['path']));
        }
        return $total;
    }

    public function countTokens(array $documents): int
    {
        // Rough estimate for tokens (chars / 4)
        return (int) ($this->countCharacters($documents) / 4);
    }
}
