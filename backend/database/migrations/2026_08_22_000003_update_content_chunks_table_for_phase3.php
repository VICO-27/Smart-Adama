<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_chunks', function (Blueprint $table) {
            // Nullable section_id to support the automated PDF pipeline
            $table->uuid('section_id')->nullable()->change();

            // Rich metadata for Phase 3
            $table->foreignUuid('book_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('page_number')->nullable();
            $table->json('structural_context')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('content_chunks', function (Blueprint $table) {
            $table->dropForeign(['book_id']);
            $table->dropColumn(['book_id', 'page_number', 'structural_context']);

            // Reverting section_id to NOT NULL would require a raw statement or it might fail if there are nulls.
            $table->uuid('section_id')->nullable(false)->change();
        });
    }
};
