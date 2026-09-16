<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insert default values
        DB::table('admin_settings')->insert([
            ['key' => 'llm_provider', 'value' => 'groq', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'embedding_provider', 'value' => 'ollama', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'embedding_dimensions', 'value' => '768', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'rag_similarity_threshold', 'value' => '0.75', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'rag_top_k', 'value' => '4', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_settings');
    }
};
