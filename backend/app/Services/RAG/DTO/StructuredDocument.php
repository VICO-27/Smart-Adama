<?php

namespace App\Services\RAG\DTO;

class StructuredDocument
{
    /** @var StructuredChapter[] */
    public array $chapters = [];

    public function addChapter(StructuredChapter $chapter): void
    {
        $this->chapters[] = $chapter;
    }
}
