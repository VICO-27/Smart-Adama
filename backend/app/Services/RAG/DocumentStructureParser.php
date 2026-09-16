<?php

namespace App\Services\RAG;

use App\Services\RAG\DTO\StructuredChapter;
use App\Services\RAG\DTO\StructuredDocument;
use App\Services\RAG\DTO\StructuredSection;
use Illuminate\Support\Collection;

class DocumentStructureParser
{
    /**
     * Parse a collection of BookPage models into a StructuredDocument hierarchy.
     */
    public function parse(Collection $pages): StructuredDocument
    {
        $document = new StructuredDocument;

        if ($pages->isEmpty()) {
            return $document;
        }

        // 1. Detect TOC and build canonical chapter names
        $tocInfo = $this->extractToc($pages);
        $chapterMatches = $tocInfo['matches'];
        $lastTocPage = $tocInfo['last_toc_page'];

        $normalizedTitles = [];
        foreach ($chapterMatches as $num => $title) {
            $normalizedTitles[$num] = preg_replace('/\s+/', ' ', strtolower($title));
        }

        // 2. State machine variables
        $currentChapter = null;
        $currentSection = null;
        $sectionOrder = 1;

        foreach ($pages as $page) {
            $pageLines = explode("\n", $page->clean_text);
            $pageNumber = $page->page_number;

            foreach ($pageLines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    if ($currentSection) {
                        $currentSection->appendContent('', $pageNumber);
                    }

                    continue;
                }

                // Skip chapter detection if we are still on a TOC page
                if ($pageNumber <= $lastTocPage) {
                    continue;
                }

                // Check for Chapter header using TOC validation if TOC found chapters,
                // OR fallback to dynamic if no TOC chapters found.
                if (preg_match('/^(?:Chapter\s+)?(\d{1,2})[\s\.\:]+(.+?)\s*$/i', $line, $m)) {
                    $chapterNum = (int) $m[1];
                    $detectedTitle = trim($m[2]);

                    $isChapter = false;
                    if (! empty($chapterMatches) && isset($chapterMatches[$chapterNum])) {
                        $normalizedDetected = preg_replace('/\s+/', ' ', strtolower($detectedTitle));
                        $normalizedExpected = $normalizedTitles[$chapterNum];
                        if (strpos($normalizedDetected, $normalizedExpected) === 0 || strpos($normalizedExpected, $normalizedDetected) === 0) {
                            $isChapter = true;
                            $detectedTitle = $chapterMatches[$chapterNum]; // Use canonical
                        }
                    } elseif (empty($chapterMatches)) {
                        // Dynamic fallback if no TOC
                        if (strlen($detectedTitle) > 3 && strlen($detectedTitle) < 100) {
                            $isChapter = true;
                        }
                    }

                    if ($isChapter) {
                        // Finalize previous chapter's end page
                        if ($currentChapter) {
                            $currentChapter->endPage = $pageNumber;
                            if ($currentSection) {
                                $currentSection->endPage = $pageNumber;
                            }
                        }

                        $currentChapter = new StructuredChapter($chapterNum, $detectedTitle, $pageNumber);
                        $document->addChapter($currentChapter);

                        $sectionOrder = 1;
                        $currentSection = null;

                        continue;
                    }
                }

                // Check for Section header
                if ($currentChapter && preg_match('/^\d+(\.\d+)+\s+([A-Z].*?)\s*$/', $line, $m)) {
                    if ($currentSection) {
                        $currentSection->endPage = $pageNumber;
                    }

                    $currentSection = new StructuredSection($sectionOrder++, trim($m[2]), $pageNumber, $pageNumber);
                    $currentChapter->addSection($currentSection);

                    continue;
                }

                // Accumulate content
                if (! $currentChapter) {
                    $currentChapter = new StructuredChapter(0, 'Preface', $pageNumber);
                    $document->addChapter($currentChapter);
                }

                if (! $currentSection) {
                    $currentSection = new StructuredSection($sectionOrder++, 'Introduction', $pageNumber, $pageNumber);
                    $currentChapter->addSection($currentSection);
                }

                $currentSection->appendContent($line, $pageNumber);
            }
        }

        // Finalize end pages for the last elements
        if ($currentChapter) {
            $lastPageNum = $pages->last()->page_number;
            $currentChapter->endPage = $lastPageNum;
            if ($currentSection) {
                $currentSection->endPage = $lastPageNum;
            }
        }

        return $document;
    }

    /**
     * Extracts TOC from the first few pages of the document.
     * Returns an array mapping chapter numbers to their expected titles,
     * and the last page number that is part of the TOC.
     */
    private function extractToc(Collection $pages): array
    {
        $chapterMatches = [];
        $lastTocPage = 0;

        // Scan the first 30 pages for TOC
        $headPages = $pages->take(30);

        $inToc = false;

        foreach ($headPages as $page) {
            $pageLines = explode("\n", $page->clean_text);

            foreach ($pageLines as $line) {
                $line = trim($line);

                if (! $inToc && stripos($line, 'contents') !== false) {
                    $inToc = true;
                    $lastTocPage = $page->page_number;

                    continue;
                }

                if ($inToc) {
                    // Update last TOC page as long as we are extracting entries
                    $lastTocPage = max($lastTocPage, $page->page_number);

                    // End TOC parsing when reaching lists of figures/tables
                    if (preg_match('/^List of (Figures|Tables|Acronyms)/i', $line)) {
                        $inToc = false;
                        break 2; // break both loops
                    }

                    // Match chapter entries like "1 Introduction"
                    if (preg_match('/^(?:Chapter\s+)?(\d{1,2})[\s\.\:]+(.+?)\s*$/i', $line, $m)) {
                        $chapterNum = (int) $m[1];
                        $chapterMatches[$chapterNum] = trim($m[2]);

                        continue;
                    }

                    // Match section entries like "1.1 Background"
                    if (preg_match('/^\d+(\.\d+)+\s+([A-Z].*?)\s*$/', $line)) {
                        continue;
                    }

                    // Ignore continued 'Contents' headers
                    if (stripos($line, 'contents') !== false) {
                        continue;
                    }

                    // If we encounter a line that is not a heading, it means body text has started!
                    // Break out of TOC mode.
                    if (strlen($line) > 10) { // arbitrary threshold to ignore short artifacts
                        $inToc = false;
                        // Since this page has body text, the TOC actually ended on the previous page
                        // unless the TOC and body are on the exact same page.
                        // We will keep $lastTocPage as is, or we can decrement if we wanted to be strict,
                        // but it's safer to just break and let the parser handle it.
                        $lastTocPage = max(0, $page->page_number - 1);
                        break 2;
                    }
                }
            }
        }

        return [
            'matches' => $chapterMatches,
            'last_toc_page' => $lastTocPage,
        ];
    }
}
