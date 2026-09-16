<?php

namespace Tests\Unit\AI;

use App\Exceptions\RAGRetrievalException;
use App\Services\AI\Contracts\EmbeddingProviderInterface;
use App\Services\RAG\RetrievalService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class RetrievalServiceTest extends TestCase
{
    public function test_it_throws_rag_retrieval_exception_on_db_failure()
    {
        $embedder = Mockery::mock(EmbeddingProviderInterface::class);
        $embedder->shouldReceive('embed')->with('test query', 'query')->andReturn(array_fill(0, 1024, 0.1));

        DB::shouldReceive('select')->andThrow(new \Exception('DB Error'));

        $this->expectException(RAGRetrievalException::class);

        $service = new RetrievalService($embedder);
        $service->search('test query', 'test query');
    }

    public function test_it_formats_rrf_results_correctly()
    {
        $embedder = Mockery::mock(EmbeddingProviderInterface::class);
        $embedder->shouldReceive('embed')->with('test query', 'query')->andReturn(array_fill(0, 1024, 0.1));

        $mockRow = (object) [
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
            'chapter_title' => 'Ch-11: Smart People',
            'section_title' => '11.1 Introduction',
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

    public function test_it_enforces_vector_space_isolation_with_provider_model_and_dimension()
    {
        $embedder = Mockery::mock(EmbeddingProviderInterface::class);
        $embedder->shouldReceive('embed')->with('test query', 'query')->andReturn(array_fill(0, 1024, 0.1));

        DB::shouldReceive('select')->once()->withArgs(function ($sql, $bindings) {
            // Verify semantic search SQL joins content_chunk_embeddings for Voyage and filters by provider, model, and dimension
            $hasIsolation = str_contains($sql, 'ce.provider = ? AND ce.model = ? AND ce.dimension = ?');
            // Verify semantic bindings: [vectorStr, vectorStr, provider, model, dimension]
            $providerMatches = ($bindings[2] === 'voyage');
            $dimMatches = ($bindings[4] === 1024);

            return $hasIsolation && $providerMatches && $dimMatches;
        })->andReturn([]);

        $service = new RetrievalService($embedder);
        $results = $service->search('test query', 'test query');

        $this->assertCount(0, $results);
    }
}
