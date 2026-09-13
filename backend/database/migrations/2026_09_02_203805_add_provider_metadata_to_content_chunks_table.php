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
        Schema::table('content_chunks', function (Blueprint $table) {
            $table->string('embedding_provider')->nullable()->default('voyage')->after('embedding_status');
            $table->string('embedding_model')->nullable()->default('voyage-large-2-instruct')->after('embedding_provider');
        });
    }

    public function down(): void
    {
        Schema::table('content_chunks', function (Blueprint $table) {
            $table->dropColumn(['embedding_provider', 'embedding_model']);
        });
    }
};
