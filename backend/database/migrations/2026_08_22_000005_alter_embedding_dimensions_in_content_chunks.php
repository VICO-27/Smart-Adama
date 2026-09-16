<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the HNSW index that depends on the vector column
        DB::statement('DROP INDEX IF EXISTS content_chunks_embedding_hnsw_idx');

        // Since we are changing dimensions from 512 to 1024, existing 512-dimensional
        // vectors cannot be automatically cast. We must clear them first.
        // The automated Phase 3 pipeline will regenerate them anyway.
        DB::statement('UPDATE content_chunks SET embedding = NULL');

        // Alter the embedding column to strictly enforce 1024 dimensions
        DB::statement('ALTER TABLE content_chunks ALTER COLUMN embedding TYPE vector(1024)');

        // Recreate the HNSW index for high-performance cosine similarity search
        DB::statement(
            'CREATE INDEX content_chunks_embedding_hnsw_idx
             ON content_chunks
             USING hnsw (embedding vector_cosine_ops)
             WITH (m = 16, ef_construction = 64)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS content_chunks_embedding_hnsw_idx');
        DB::statement('ALTER TABLE content_chunks ALTER COLUMN embedding TYPE vector(512)');
        DB::statement(
            'CREATE INDEX content_chunks_embedding_hnsw_idx
             ON content_chunks
             USING hnsw (embedding vector_cosine_ops)
             WITH (m = 16, ef_construction = 64)'
        );
    }
};
