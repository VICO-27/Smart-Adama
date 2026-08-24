<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapter_summaries', function (Blueprint $table) {
            $table->id();
            $table->integer('chapter_number')->index();
            $table->string('chapter_title');
            $table->text('summary_text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_summaries');
    }
};
