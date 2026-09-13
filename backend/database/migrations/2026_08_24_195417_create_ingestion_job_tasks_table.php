<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingestion_job_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ingestion_job_id')->constrained()->cascadeOnDelete();
            $table->string('task_type');
            $table->string('status');
            $table->uuid('target_id')->nullable();
            $table->json('payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingestion_job_tasks');
    }
};
