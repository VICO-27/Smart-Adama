<?php

namespace App\Services\RAG\DTO;

class StructuredChapter
{
    /** @var StructuredSection[] */
    public array $sections = [];

    public function __construct(
        public int $order,
        public string $title,
        public int $startPage,
        public int $endPage = 0
    ) {}

    public function addSection(StructuredSection $section): void
    {
        $this->sections[] = $section;
    }
}
