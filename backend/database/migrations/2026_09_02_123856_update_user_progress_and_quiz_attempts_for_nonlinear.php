<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_progress', function (Blueprint $table) {
            $table->string('status')->default('NOT_STARTED')->after('is_completed');
            $table->integer('reading_progress')->default(0)->after('status');
            $table->string('last_page')->nullable()->after('reading_progress');
            $table->timestamp('started_at')->nullable()->after('last_page');
        });

        // Set existing records to COMPLETED if they were completed, else IN_PROGRESS
        DB::table('user_progress')
            ->where('is_completed', true)
            ->update(['status' => 'COMPLETED', 'reading_progress' => 100]);

        DB::table('user_progress')
            ->where('is_completed', false)
            ->update(['status' => 'IN_PROGRESS']);

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->integer('attempt_number')->default(1)->after('passed');
        });
    }

    public function down(): void
    {
        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropColumn(['status', 'reading_progress', 'last_page', 'started_at']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['attempt_number']);
        });
    }
};
