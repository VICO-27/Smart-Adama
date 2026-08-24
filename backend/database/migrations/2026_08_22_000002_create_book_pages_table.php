<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_pages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('book_id')->constrained()->cascadeOnDelete();
            $table->integer('page_number');
            $table->text('raw_text')->nullable();
            $table->text('clean_text')->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->string('status')->default('ready'); // ready, ocr_required, blank
            $table->timestamps();

            $table->unique(['book_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_pages');
    }
};
