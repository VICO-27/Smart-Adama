<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('source_type')->default('pdf')->after('status');
            $table->integer('version')->default(1)->after('source_type');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->foreignUuid('parent_id')->nullable()->after('chapter_id')->constrained('sections')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'version']);
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['sections_parent_id_foreign']);
            $table->dropColumn('parent_id');
        });
    }
};
