<?php

namespace Nomanurrahman\Services;

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

        $separator = "\n\n";
        $splits = explode($separator, $text);
        $docs = [];
        $currentDoc = [];
        $total = 0;

        foreach ($splits as $d) {
            $len = mb_strlen($d);
            
            if ($total + $len + (count($currentDoc) > 0 ? mb_strlen($separator) : 0) > $this->chunkSize) {
                if ($total > 0) {
                    $docs[] = implode($separator, $currentDoc);
                    
                    // Handle overlap
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
}
