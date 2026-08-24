<?php

namespace Tests\Unit\AI;

use App\Services\RAG\DocumentChunkingService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DocumentChunkingServiceTest extends TestCase
{
    private DocumentChunkingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock config for consistent testing
        Config::set('ai.rag.chunk_target_tokens', 10); // 40 chars
        Config::set('ai.rag.chunk_overlap_ratio', 0.25); // 10 chars
        
        $this->service = new DocumentChunkingService();
    }

    public function test_it_returns_empty_array_for_empty_text()
    {
        $chunks = $this->service->chunk("   \n  ", ['book_id' => '123']);
        $this->assertEmpty($chunks);
    }

    public function test_it_chunks_by_paragraphs_when_they_exceed_limits()
    {
        // 40 chars target. 
        $para1 = str_repeat("A", 20);
        $para2 = str_repeat("B", 25);
        
        // Total is 45 + 2 for \n\n = 47. Target is 40. Should split.
        $text = "{$para1}\n\n{$para2}";
        
        $chunks = $this->service->chunk($text, ['book_id' => '123']);
        
        $this->assertCount(2, $chunks);
        $this->assertEquals($para1, $chunks[0]['chunk_text']);
        
        // Check that chunk 2 contains the second paragraph AND some overlap from the first
        $this->assertStringContainsString($para2, $chunks[1]['chunk_text']);
        // Verify overlap from previous chunk was prepended
        $this->assertStringContainsString(substr($para1, -10), $chunks[1]['chunk_text']);
        $this->assertEquals('123', $chunks[0]['book_id']);
    }

    public function test_it_splits_large_paragraphs_by_sentences_with_overlap()
    {
        // Target is 40 chars, overlap 10 chars.
        // Sentences:
        $s1 = "This is a sentence."; // 19 chars
        $s2 = "This is another one."; // 20 chars
        $s3 = "And the final sentence here."; // 28 chars
        
        // Total paragraph length = 19 + 1 + 20 + 1 + 28 = 69 chars.
        // Chunk 1: s1 + s2 = 40 chars. Should trigger split.
        // Next chunk should have overlap. Overlap is 10 chars, which might just grab the end of s2 or s2 entirely if logic falls back.
        
        $text = "{$s1} {$s2} {$s3}";
        
        $chunks = $this->service->chunk($text, []);
        
        $this->assertCount(2, $chunks);
        $this->assertStringContainsString("This is a sentence. This is another one.", $chunks[0]['chunk_text']);
        // Second chunk should contain overlap and the final sentence
        $this->assertStringContainsString("And the final sentence here.", $chunks[1]['chunk_text']);
    }

    public function test_it_preserves_rich_metadata_contract()
    {
        $metadata = [
            'book_id' => '01a02b21-5df6-7366-8752-3220c3f2527c',
            'chapter_id' => '01a02b21-5df6-7366-8752-3220c3f2527d',
            'section_id' => '01a02b21-5df6-7366-8752-3220c3f2527e',
            'chapter_title' => 'Introduction',
            'section_title' => 'Background',
            'start_page' => 5,
            'end_page' => 6,
            'source_order' => 1,
            'structural_context' => [
                'chapter' => 'Introduction',
                'section' => 'Background',
                'start_page' => 5,
                'end_page' => 6,
            ],
        ];

        $chunks = $this->service->chunk("Test chunk content", $metadata);

        $this->assertCount(1, $chunks);
        
        $chunk = $chunks[0];

        $this->assertEquals('Test chunk content', $chunk['chunk_text']);
        $this->assertEquals('01a02b21-5df6-7366-8752-3220c3f2527c', $chunk['book_id']);
        $this->assertEquals('01a02b21-5df6-7366-8752-3220c3f2527e', $chunk['section_id']);
        $this->assertEquals(5, $chunk['start_page']);
        $this->assertIsArray($chunk['structural_context']);
        $this->assertEquals('Background', $chunk['structural_context']['section']);
    }
}
