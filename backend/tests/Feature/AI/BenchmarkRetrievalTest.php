<?php

namespace Tests\Feature\AI;

use App\Models\ContentChunk;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\RAG\RetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class BenchmarkRetrievalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure tsvector column exists for testing since it's added via raw SQL migration
        DB::statement("
            ALTER TABLE content_chunks 
            ADD COLUMN IF NOT EXISTS search_vector tsvector 
            GENERATED ALWAYS AS (to_tsvector('english', coalesce(chunk_text, ''))) STORED
        ");
    }

    public function test_exact_keyword_query_retrieves_correct_chunk_and_metadata()
    {
        // Setup mock data
        $this->createTestChunks();

        $embedder = Mockery::mock(EmbeddingProviderInterface::class);
        // Mock returning a dummy vector
        $embedder->shouldReceive('embed')->andReturn(array_fill(0, 1024, 0.1));

        $service = new RetrievalService($embedder);

        // Lexical heavy query
        $results = $service->search('mayor', 'mayor');

        $this->assertNotEmpty($results);
        
        $topChunk = $results->first();
        
        // Assert it retrieved the chunk with the word 'mayor'
        $this->assertStringContainsString('mayor', strtolower($topChunk['chunk_text']));
        
        // Assert page number metadata is intact
        $this->assertNotNull($topChunk['page_number']);
        $this->assertEquals(10, $topChunk['page_number']);

        // Assert RRF score is calculated
        $this->assertGreaterThan(0, $topChunk['rrf_score']);
        
        // Because of the exact keyword match, keyword score should be > 0 and rank should be high (low number)
        $this->assertGreaterThan(0, $topChunk['keyword_score']);
    }

    public function test_semantic_question_query_executes_rrf_math()
    {
        $this->createTestChunks();

        $embedder = Mockery::mock(EmbeddingProviderInterface::class);
        $embedder->shouldReceive('embed')->andReturn(array_fill(0, 1024, 0.15));

        $service = new RetrievalService($embedder);

        // Semantic query
        $results = $service->search('How does the city administration handle public services?', 'how does the city administration handle public services?');

        $this->assertNotEmpty($results);
        $topChunk = $results->first();
        
        // Assert structural context is preserved
        $this->assertIsArray($topChunk['structural_context']);
        $this->assertEquals('City Governance', $topChunk['structural_context']['heading']);

        $this->assertGreaterThan(0, $topChunk['rrf_score']);
    }

    public function test_known_phrase_query_retrieves_exact_match()
    {
        $this->createTestChunks();

        $embedder = Mockery::mock(EmbeddingProviderInterface::class);
        $embedder->shouldReceive('embed')->andReturn(array_fill(0, 1024, 0.1));

        $service = new RetrievalService($embedder);

        $results = $service->search('economic development and urban planning', 'economic development and urban planning');

        $this->assertNotEmpty($results);
        $topChunk = $results->first();
        
        $this->assertStringContainsString('economic development and urban planning', $topChunk['chunk_text']);
        $this->assertEquals(22, $topChunk['page_number']);
    }

    private function createTestChunks()
    {
        // Note: For pgvector to work in tests, the DB must support it. 
        // Assuming PostgreSQL is the testing DB and pgvector is enabled.
        
        $dummyVector = '[' . implode(',', array_fill(0, 1024, 0.1)) . ']';
        
        $book = \App\Models\Book::factory()->published()->create();
        $bookId = $book->id;

        // Insert via raw SQL to handle vector casting
        DB::statement("
            INSERT INTO content_chunks (id, book_id, page_number, chunk_text, embedding_status, structural_context, embedding, created_at, updated_at) 
            VALUES 
            (gen_random_uuid(), '{$bookId}', 10, 'The mayor of the city is responsible for executive decisions.', 'ready', '{\"heading\": \"Executive Branch\"}', ?::vector, NOW(), NOW()),
            (gen_random_uuid(), '{$bookId}', 15, 'The city administration handles day to justify various public services and utilities.', 'ready', '{\"heading\": \"City Governance\"}', ?::vector, NOW(), NOW()),
            (gen_random_uuid(), '{$bookId}', 22, 'Focusing on economic development and urban planning is critical for the future.', 'ready', '{\"heading\": \"Future Outlook\"}', ?::vector, NOW(), NOW())
        ", [$dummyVector, $dummyVector, $dummyVector]);
    }
}
