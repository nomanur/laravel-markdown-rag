<?php

namespace Nomanur\Services;

class TextSplitter
{
    protected int $chunkSize;
    protected int $chunkOverlap;

    public function __construct(int $chunkSize = 1000, int $chunkOverlap = 100)
    {
        $this->chunkSize = $chunkSize;
        $this->chunkOverlap = $chunkOverlap;
    }

    public function splitText(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        // Try splitting by double newlines first (paragraphs)
        $splits = $this->splitBySeparator($text, "\n\n");
        $separator = "\n\n";
        
        // If no splits from double newlines, try single newlines
        if (count($splits) === 1) {
            $splits = $this->splitBySeparator($text, "\n");
            $separator = "\n";
        }
        
        // If still no splits, use character-based splitting
        if (count($splits) === 1 && mb_strlen($text) > $this->chunkSize) {
            return $this->splitByCharacters($text);
        }

        $docs = [];
        $currentDoc = [];
        $total = 0;

        foreach ($splits as $d) {
            $len = mb_strlen($d);

            if ($total + $len + (count($currentDoc) > 0 ? mb_strlen($separator) : 0) > $this->chunkSize) {
                if ($total > 0) {
                    $docs[] = implode($separator, $currentDoc);

                    // Handle overlap - keep recent content up to chunkOverlap size
                    while ($total > $this->chunkOverlap || ($total + $len + mb_strlen($separator) > $this->chunkSize && $total > 0)) {
                        $popped = array_shift($currentDoc);
                        $total -= mb_strlen($popped) + (count($currentDoc) > 0 ? mb_strlen($separator) : 0);
                    }
                }
            }

            $currentDoc[] = $d;
            $total += $len + (count($currentDoc) > 1 ? mb_strlen($separator) : 0);
        }

        if ($currentDoc) {
            $docs[] = implode($separator, $currentDoc);
        }

        return $docs;
    }

    /**
     * Split text by a given separator.
     */
    protected function splitBySeparator(string $text, string $separator): array
    {
        return explode($separator, $text);
    }

    /**
     * Split text by characters when no natural separators exist.
     */
    protected function splitByCharacters(string $text): array
    {
        $chunks = [];
        $start = 0;
        $length = mb_strlen($text);

        while ($start < $length) {
            $end = min($start + $this->chunkSize, $length);
            $chunk = mb_substr($text, $start, $end - $start);
            
            // Try to break at a word boundary
            if ($end < $length) {
                $lastSpace = mb_strrpos($chunk, ' ');
                if ($lastSpace !== false && $lastSpace > $this->chunkSize * 0.5) {
                    $chunk = mb_substr($chunk, 0, $lastSpace);
                    $end = $start + $lastSpace;
                }
            }
            
            $chunks[] = $chunk;
            $start = $end;
            
            // Apply overlap for next chunk
            if ($start < $length && $this->chunkOverlap > 0) {
                $start = max($start - $this->chunkOverlap, 0);
            }
        }

        return $chunks;
    }
}
