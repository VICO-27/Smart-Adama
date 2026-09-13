<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Chapter;
use App\Models\Section;
use App\Models\BookPage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ManualAuthoringController extends Controller
{
    // --- Book Level ---
    public function createBook(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'version' => 'nullable|integer',
        ]);

        $book = Book::create([
            'title' => $validated['title'],
            'source_type' => 'manual',
            'version' => $validated['version'] ?? 1,
            'status' => 'draft',
        ]);

        return response()->json(['book' => $book], 201);
    }

    public function getTree(Book $book): JsonResponse
    {
        $chapters = $book->chapters()->with(['sections' => function($q) {
            $q->whereNull('parent_id')->with('descendants'); // recursive load for structured mode
        }])->orderBy('order')->get();

        $pages = $book->pages ?? BookPage::where('book_id', $book->id)->orderBy('page_number')->get();

        return response()->json([
            'book' => $book,
            'chapters' => $chapters,
            'pages' => $pages,
        ]);
    }

    // --- Chapters ---
    public function createChapter(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer',
        ]);

        $chapter = $book->chapters()->create([
            'title' => $validated['title'],
            'order' => $validated['order'],
            'ingestion_status' => 'draft',
        ]);

        return response()->json(['chapter' => $chapter], 201);
    }

    public function updateChapter(Request $request, Chapter $chapter): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'order' => 'sometimes|integer',
            'content' => 'nullable|string',
        ]);

        $chapter->update($validated);
        return response()->json(['chapter' => $chapter]);
    }

    public function deleteChapter(Chapter $chapter): JsonResponse
    {
        $chapter->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    // --- Sections ---
    public function createSection(Request $request, Chapter $chapter): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order' => 'required|integer',
            'parent_id' => 'nullable|uuid|exists:sections,id',
            'raw_text' => 'nullable|string',
        ]);

        $parentId = $validated['parent_id'] ?? null;
        $sectionNumber = $chapter->order . '.' . $validated['order'];
        // Ensure uniqueness if a subsection is added
        if ($parentId) {
            $parent = Section::find($parentId);
            if ($parent) {
                $sectionNumber = $parent->section_number . '.' . $validated['order'];
            }
        }

        $section = $chapter->sections()->create([
            'title' => $validated['title'],
            'order' => $validated['order'],
            'section_number' => $sectionNumber,
            'parent_id' => $parentId,
            'raw_text' => $validated['raw_text'] ?? '',
        ]);

        return response()->json(['section' => $section], 201);
    }

    public function updateSection(Request $request, Section $section): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'order' => 'sometimes|integer',
            'raw_text' => 'nullable|string',
        ]);

        if (array_key_exists('raw_text', $validated) && $validated['raw_text'] === null) {
            $validated['raw_text'] = '';
        }

        $section->update($validated);
        return response()->json(['section' => $section]);
    }

    public function deleteSection(Section $section): JsonResponse
    {
        $section->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }

    // --- Pages ---
    public function createPage(Request $request, Book $book): JsonResponse
    {
        $validated = $request->validate([
            'page_number' => 'required|integer',
            'raw_text' => 'nullable|string',
        ]);

        $page = BookPage::create([
            'book_id' => $book->id,
            'page_number' => $validated['page_number'],
            'raw_text' => $validated['raw_text'] ?? '',
            'clean_text' => $validated['raw_text'] ?? '',
            'status' => 'draft',
        ]);

        return response()->json(['page' => $page], 201);
    }

    public function updatePage(Request $request, BookPage $page): JsonResponse
    {
        $validated = $request->validate([
            'page_number' => 'sometimes|integer',
            'raw_text' => 'sometimes|string',
        ]);

        if (isset($validated['raw_text'])) {
            $validated['clean_text'] = $validated['raw_text'];
        }

        $page->update($validated);
        return response()->json(['page' => $page]);
    }

    public function deletePage(BookPage $page): JsonResponse
    {
        $page->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
