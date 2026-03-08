<?php

namespace Nomanur\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeChunk extends Model
{
    protected $fillable = ['content', 'embedding', 'source', 'document_id', 'metadata'];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Perform a similarity search based on cosine similarity.
     */
    public static function similaritySearch(array $queryEmbedding, int $limit = 5, ?string $documentId = null)
    {
        return self::query()
            ->when($documentId, fn ($query) => $query->where('document_id', $documentId))
            ->get()
            ->map(function ($chunk) use ($queryEmbedding) {
                $chunk->similarity = self::cosineSimilarity($chunk->embedding, $queryEmbedding);

                return $chunk;
            })
            ->sortByDesc('similarity')
            ->take($limit);
    }

    private static function cosineSimilarity(array $vec1, array $vec2): float
    {
        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;
        
        $count1 = count($vec1);
        $count2 = count($vec2);

        if ($count1 !== $count2) {
            // Log warning or handle mismatch if needed, but for RAG we want consistency
        }
        
        $count = min($count1, $count2);
        
        for ($i = 0; $i < $count; $i++) {
            $dotProduct += ($vec1[$i] ?? 0) * ($vec2[$i] ?? 0);
            $norm1 += ($vec1[$i] ?? 0) ** 2;
            $norm2 += ($vec2[$i] ?? 0) ** 2;
        }

        if ($norm1 === 0 || $norm2 === 0) {
            return 0;
        }

        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }
}
