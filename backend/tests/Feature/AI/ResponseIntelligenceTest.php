<?php

namespace Tests\Feature\AI;

use App\Services\RAG\QueryUnderstandingService;
use Tests\TestCase;

class ResponseIntelligenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_detects_response_modes_correctly()
    {
        $service = new QueryUnderstandingService;

        $this->assertEquals('SHORT', $service->analyze('what is adama?')['response_mode']);
        $this->assertEquals('SHORT', $service->analyze('who is hailu?')['response_mode']);

        $this->assertEquals('DETAILED', $service->analyze('explain in detail')['response_mode']);
        $this->assertEquals('DETAILED', $service->analyze('tell me everything about the history')['response_mode']);

        $this->assertEquals('DEEP', $service->analyze('teach me deeply about this')['response_mode']);

        $this->assertEquals('CHAPTER_SUMMARY', $service->analyze('summarize chapter 3')['response_mode']);
        $this->assertEquals('CHAPTER_SUMMARY', $service->analyze('summarize chapter three')['response_mode']);
        $this->assertEquals('CHAPTER_SUMMARY_UNKNOWN', $service->analyze('summarize the chapter')['response_mode']);

        $this->assertEquals('SIMPLE_EXPLANATION', $service->analyze('explain this simply')['response_mode']);
        $this->assertEquals('SIMPLE_EXPLANATION', $service->analyze('explain like im a beginner')['response_mode']);

        $this->assertEquals('STEP_BY_STEP', $service->analyze('how to do this step by step')['response_mode']);

        $this->assertEquals('COMPARISON', $service->analyze('compare A and B')['response_mode']);
        $this->assertEquals('COMPARISON', $service->analyze('difference between X and Y')['response_mode']);

        $this->assertEquals('NORMAL', $service->analyze('give me an overview of the economy')['response_mode']);
    }

    public function test_it_detects_follow_ups()
    {
        $service = new QueryUnderstandingService;

        $this->assertTrue($service->analyze('tell me more')['is_follow_up']);
        $this->assertTrue($service->analyze('explain that')['is_follow_up']);
        $this->assertTrue($service->analyze('why?')['is_follow_up']);
        $this->assertTrue($service->analyze('how?')['is_follow_up']);
        $this->assertTrue($service->analyze('and what about the rest?')['is_follow_up']);

        $this->assertFalse($service->analyze('what is the capital of ethiopia?')['is_follow_up']);
        $this->assertFalse($service->analyze('summarize chapter 3')['is_follow_up']);
    }
}
