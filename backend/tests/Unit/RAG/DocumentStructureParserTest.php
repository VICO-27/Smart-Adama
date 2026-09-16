<?php

namespace Tests\Unit\RAG;

use App\Models\BookPage;
use App\Services\RAG\DocumentStructureParser;
use App\Services\RAG\DTO\StructuredDocument;
use PHPUnit\Framework\TestCase;

class DocumentStructureParserTest extends TestCase
{
    public function test_it_parses_chapters_and_sections_correctly()
    {
        $parser = new DocumentStructureParser;

        $pages = collect([
            new BookPage([
                'page_number' => 1,
                'clean_text' => "Table of Contents\n1 Introduction\n2 Core Features",
            ]),
            new BookPage([
                'page_number' => 2,
                'clean_text' => "1 Introduction\nThis is the introduction.\n1.1 Background\nSome background here.",
            ]),
            new BookPage([
                'page_number' => 3,
                'clean_text' => "2 Core Features\n2.1 Feature A\nFeature A details.\n2.2 Feature B\nFeature B details.",
            ]),
        ]);

        $structuredDoc = $parser->parse($pages);

        $this->assertInstanceOf(StructuredDocument::class, $structuredDoc);
        $this->assertCount(2, $structuredDoc->chapters);

        $chapter1 = $structuredDoc->chapters[0];
        $this->assertEquals('Introduction', $chapter1->title);
        $this->assertEquals(1, $chapter1->order);
        $this->assertCount(2, $chapter1->sections);
        $this->assertEquals('Introduction', $chapter1->sections[0]->title);
        $this->assertEquals('Background', $chapter1->sections[1]->title);

        $chapter2 = $structuredDoc->chapters[1];
        $this->assertEquals('Core Features', $chapter2->title);
        $this->assertEquals(2, $chapter2->order);
        $this->assertCount(2, $chapter2->sections);
        $this->assertEquals('Feature A', $chapter2->sections[0]->title);
        $this->assertEquals('Feature B', $chapter2->sections[1]->title);
    }
}
