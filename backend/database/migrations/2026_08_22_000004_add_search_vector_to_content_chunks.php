<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add a generated tsvector column for fast full-text search
        DB::statement("
            ALTER TABLE content_chunks 
            ADD COLUMN search_vector tsvector 
            GENERATED ALWAYS AS (to_tsvector('english', coalesce(chunk_text, ''))) STORED
        ");

        // Add a GIN index on the new column
        DB::statement("
            CREATE INDEX content_chunks_search_vector_gin_idx 
            ON content_chunks 
            USING GIN (search_vector)
        ");
    }

    public function down(): void
    {
        Schema::table('content_chunks', function (Blueprint $table) {
            $table->dropIndex('content_chunks_search_vector_gin_idx');
            $table->dropColumn('search_vector');
        });
    }
};
