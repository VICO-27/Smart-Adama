<?php

namespace Tests\Feature\RAG;

use App\Services\RAG\GroundingDecisionService;
use App\Services\RAG\QueryUnderstandingService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RAGPipelineTest extends TestCase
{
    public function test_query_understanding_handles_short_names_as_entity_lookups()
    {
        $service = new QueryUnderstandingService();
        
        $result = $service->analyze('Ashenafi Deresa');
        
        $this->assertFalse($result['is_conversational']);
        $this->assertTrue($result['is_entity_lookup']);
    }

    public function test_query_understanding_handles_greetings_as_conversational()
    {
        $service = new QueryUnderstandingService();
        
        $result = $service->analyze('hi there');
        
        $this->assertTrue($result['is_conversational']);
        $this->assertFalse($result['is_entity_lookup']);
    }

    public function test_query_understanding_normalizes_abbreviations_conservatively()
    {
        $service = new QueryUnderstandingService();
        
        $result = $service->analyze('tell me abt the bg of adma');
        
        $this->assertEquals('tell me abt the bg of adma', $result['original_query']);
        $this->assertEquals('tell me about the background of Adama', $result['normalized_query']);
    }

    public function test_grounding_decision_rejects_weak_evidence()
    {
        $service = new GroundingDecisionService();
        $understanding = [
            'original_query' => 'mayor',
            'normalized_query' => 'mayor',
            'is_conversational' => false,
            'is_entity_lookup' => true,
        ];
        
        $chunks = new Collection([
            [
                'id' => 1,
                'chunk_text' => 'The city is beautiful.',
                'vector_score' => 0.2, // Weak
                'keyword_score' => 0.0,
                'rrf_score' => 0.001,
            ]
        ]);
        
        $result = $service->evaluate($chunks, $understanding);
        
        $this->assertFalse($result['isGrounded']);
        $this->assertEquals('NONE', $result['confidence']);
        $this->assertEmpty($result['selectedSources']);
    }

    public function test_grounding_decision_accepts_strong_lexical_entity_match()
    {
        $service = new GroundingDecisionService();
        $understanding = [
            'original_query' => 'Ashenafi Deresa',
            'normalized_query' => 'Ashenafi Deresa',
            'is_conversational' => false,
            'is_entity_lookup' => true,
        ];
        
        $chunks = new Collection([
            [
                'id' => 1,
                'chunk_text' => 'Ashenafi Deresa is the project manager.',
                'vector_score' => 0.3, // Weak semantic
                'keyword_score' => 0.5, // Strong lexical
                'rrf_score' => 0.005,
            ]
        ]);
        
        // Ensure config defaults for tests don't break
        config(['ai.rag.lexical_threshold' => 0.1]);
        
        $result = $service->evaluate($chunks, $understanding);
        
        $this->assertTrue($result['isGrounded']);
        $this->assertEquals('STRONG', $result['confidence']);
        $this->assertCount(1, $result['selectedSources']);
    }
}
