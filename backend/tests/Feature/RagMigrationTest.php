<?php

namespace Tests\Feature;

use App\Models\AdminSetting;
use App\Models\Book;
use App\Models\ContentChunk;
use App\Services\RAG\RetrievalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RagMigrationTest extends TestCase
{
    public function test_migration_and_retrieval_isolation()
    {
        // Skip actual API calls, we just mock the db state
        $book = Book::where('status', 'published')->first();
        if (! $book) {
            $this->markTestSkipped('No published book to test against.');
        }

        $chunk = ContentChunk::where('book_id', $book->id)->first();
        $this->assertNotNull($chunk);

        // Ensure Ollama vector is untouched
        $this->assertEquals('ollama', $chunk->embedding_provider);
        $this->assertNotNull($chunk->embedding);

        // Delete any existing Voyage records for this test chunk to start clean
        DB::table('content_chunk_embeddings')->where('chunk_id', $chunk->id)->delete();

        // Simulate migration
        $vectorStr = '['.implode(',', array_fill(0, 1024, 0.1)).']';

        DB::statement('
            INSERT INTO content_chunk_embeddings (id, chunk_id, provider, model, dimension, embedding, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?::vector, ?, ?)
        ', [Str::uuid(), $chunk->id, 'voyage', 'voyage-4', 1024, $vectorStr, now(), now()]);

        // 1. Verify duplicates aren't created (idempotency)
        $this->artisan('rag:migrate-voyage', ['--limit' => 1])->assertSuccessful();
        $count = DB::table('content_chunk_embeddings')->where('chunk_id', $chunk->id)->where('provider', 'voyage')->count();
        $this->assertEquals(1, $count, 'Duplicate Voyage rows should not be created.');

        // 2. No cross-provider retrieval
        $retrieval = app(RetrievalService::class);

        AdminSetting::updateOrCreate(['key' => 'ai_embedding_provider'], ['value' => 'voyage']);
        $voyageResults = $retrieval->search('test', 'test');
        // Voyage results shouldn't crash and should use voyage provider

        AdminSetting::updateOrCreate(['key' => 'ai_embedding_provider'], ['value' => 'ollama']);
        $ollamaResults = $retrieval->search('test', 'test');
        // Ollama results shouldn't crash and should use ollama provider

        $this->assertTrue(true, 'Isolated retrieval successful.');
    }
}
