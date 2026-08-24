<?php

namespace App\Services\RAG\DTO;

class StructuredSection
{
    public array $pageContents = []; // Array of ['page_number' => X, 'text' => Y]

    public function __construct(
        public int $order,
        public string $title,
        public int $startPage,
        public int $endPage
    ) {}

    public function appendContent(string $text, int $pageNumber): void
    {
        if (!isset($this->pageContents[$pageNumber])) {
            $this->pageContents[$pageNumber] = '';
        }
        $this->pageContents[$pageNumber] .= (empty($this->pageContents[$pageNumber]) ? '' : "\n") . $text;
        
        if ($pageNumber > $this->endPage) {
            $this->endPage = $pageNumber;
        }
    }

    public function getFullContent(): string
    {
        return implode("\n", $this->pageContents);
    }
}
