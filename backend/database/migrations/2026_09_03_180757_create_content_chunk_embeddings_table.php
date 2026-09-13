<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First ensure the pgvector extension is available (already is, but safe to keep)
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        DB::statement('
            CREATE TABLE content_chunk_embeddings (
                id UUID PRIMARY KEY,
                chunk_id UUID REFERENCES content_chunks(id) ON DELETE CASCADE,
                provider VARCHAR(50) NOT NULL,
                model VARCHAR(100) NOT NULL,
                dimension INTEGER NOT NULL,
                embedding vector(1024) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(chunk_id, provider, model)
            )
        ');

        // Create the partial HNSW index specifically for voyage
        DB::statement("
            CREATE INDEX content_chunk_embeddings_voyage_hnsw_idx
            ON content_chunk_embeddings USING hnsw (embedding vector_cosine_ops)
            WHERE provider = 'voyage'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS content_chunk_embeddings');
    }
};
