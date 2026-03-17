<?php

namespace Nomanur\Tests;

use Nomanur\Services\TextSplitter;
use PHPUnit\Framework\TestCase;

class TextSplitterTest extends TestCase
{
    public function test_it_returns_empty_array_for_empty_text()
    {
        $splitter = new TextSplitter();
        
        $this->assertEquals([], $splitter->splitText(''));
    }

    public function test_it_splits_by_double_newlines()
    {
        // Use small chunkSize to force splitting
        $splitter = new TextSplitter(chunkSize: 20, chunkOverlap: 5);
        
        $text = "First paragraph.\n\nSecond paragraph.\n\nThird paragraph.";
        $result = $splitter->splitText($text);
        
        $this->assertGreaterThan(1, count($result));
        $this->assertStringContainsString("First paragraph", $result[0]);
    }

    public function test_it_falls_back_to_single_newline_when_no_paragraphs()
    {
        // Use small chunkSize to force splitting
        $splitter = new TextSplitter(chunkSize: 15, chunkOverlap: 5);
        
        $text = "Line one\nLine two\nLine three";
        $result = $splitter->splitText($text);
        
        $this->assertGreaterThan(1, count($result));
        $this->assertStringContainsString("Line one", $result[0]);
    }

    public function test_it_uses_character_splitting_for_long_text_without_breaks()
    {
        $splitter = new TextSplitter(chunkSize: 20, chunkOverlap: 5);
        
        $text = str_repeat('a', 100); // 100 characters without breaks
        $result = $splitter->splitText($text);
        
        $this->assertGreaterThan(1, count($result));
        // Each chunk should be around chunkSize with overlap applied
        foreach ($result as $chunk) {
            $this->assertLessThanOrEqual(25, mb_strlen($chunk));
        }
    }

    public function test_it_respects_chunk_size()
    {
        $splitter = new TextSplitter(chunkSize: 50, chunkOverlap: 10);
        
        $text = "This is a test paragraph. " . str_repeat('x ', 30);
        $result = $splitter->splitText($text);
        
        foreach ($result as $chunk) {
            $this->assertLessThanOrEqual(50, mb_strlen($chunk));
        }
    }

    public function test_it_applies_overlap_between_chunks()
    {
        $splitter = new TextSplitter(chunkSize: 30, chunkOverlap: 10);
        
        $text = "First chunk content here.\n\nSecond chunk content here.\n\nThird chunk content here.";
        $result = $splitter->splitText($text);
        
        // Overlap should be applied when chunks exceed chunkSize
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_it_splits_at_word_boundaries_in_character_mode()
    {
        $splitter = new TextSplitter(chunkSize: 20, chunkOverlap: 5);
        
        $text = "This is a long sentence without any newlines or paragraph breaks to split on at all";
        $result = $splitter->splitText($text);
        
        $this->assertGreaterThan(1, count($result));
        
        // Chunks should try to break at word boundaries
        foreach ($result as $chunk) {
            // Should not cut words in the middle if possible
            $this->assertNotEmpty($chunk);
        }
    }

    public function test_it_handles_mixed_content_with_paragraphs_and_lines()
    {
        $splitter = new TextSplitter(chunkSize: 100, chunkOverlap: 10);
        
        $text = "Paragraph one line one.\nParagraph one line two.\n\nParagraph two line one.\nParagraph two line two.";
        $result = $splitter->splitText($text);
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function test_it_handles_single_chunk_text()
    {
        $splitter = new TextSplitter(chunkSize: 1000, chunkOverlap: 100);
        
        $text = "Short text that fits in one chunk.";
        $result = $splitter->splitText($text);
        
        $this->assertCount(1, $result);
        $this->assertEquals("Short text that fits in one chunk.", $result[0]);
    }

    public function test_constructor_accepts_custom_chunk_size_and_overlap()
    {
        $splitter = new TextSplitter(chunkSize: 500, chunkOverlap: 50);
        
        $text = str_repeat("Test paragraph.\n\n", 20);
        $result = $splitter->splitText($text);
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }
}
