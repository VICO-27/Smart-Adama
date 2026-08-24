<?php

namespace Tests\Unit\AI;

use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\RAG\RetrievalService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Mockery;

class RetrievalServiceTest extends TestCase
{
    public function test_it_returns_empty_collection_on_exception()
    {
        // Mock embedder
        $embedder = Mockery::mock(EmbeddingProviderInterface::class);
        $embedder->shouldReceive('embed')->with('test query')->andReturn(array_fill(0, 1024, 0.1));

        // Mock DB to throw exception
        DB::shouldReceive('select')->andThrow(new \Exception('DB Error'));

        $service = new RetrievalService($embedder);
        
        $results = $service->search('test query', 'test query');
        
        $this->assertCount(0, $results);
    }

    public function test_it_formats_rrf_results_correctly()
    {
        $embedder = Mockery::mock(EmbeddingProviderInterface::class);
        $embedder->shouldReceive('embed')->with('test query')->andReturn(array_fill(0, 1024, 0.1));

        // Mock DB raw response
        $mockRow = (object)[
            'id' => 'uuid-123',
            'chunk_text' => 'This is a test chunk',
            'rrf_score' => 0.033,
            'vector_score' => 0.89,
            'keyword_score' => 0.95,
            'vector_rank' => 1,
            'keyword_rank' => 2,
            'book_id' => 'book-uuid',
            'page_number' => 10,
            'structural_context' => '{"heading": "Test"}',
        ];

        DB::shouldReceive('select')->once()->andReturn([$mockRow]);

        $service = new RetrievalService($embedder);
        
        $results = $service->search('test query', 'test query');
        
        $this->assertCount(1, $results);
        
        $first = $results->first();
        $this->assertEquals('uuid-123', $first['id']);
        $this->assertEquals('This is a test chunk', $first['chunk_text']);
        $this->assertEquals(0.033, $first['rrf_score']);
        $this->assertEquals(['heading' => 'Test'], $first['structural_context']);
    }
}
