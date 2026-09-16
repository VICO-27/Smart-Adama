<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanRagVectors extends Command
{
    protected $signature = 'rag:clean-vectors';

    protected $description = 'Safely removes old Voyage-generated vectors and resets ingestion status without destroying core application data.';

    public function handle()
    {
        $this->warn('This will delete ALL content_chunks (embeddings) from the database.');
        $this->warn('It will NOT delete Users, Books, Chapters, or Sections.');

        if (! $this->confirm('Do you wish to continue?', false)) {
            $this->info('Operation cancelled.');

            return;
        }

        DB::transaction(function () {
            // 1. Delete content_chunks (where vectors live)
            // We use delete() instead of truncate() to be safer with foreign keys and transactions
            $chunkCount = DB::table('content_chunks')->delete();
            $this->info("Deleted {$chunkCount} old RAG chunks/vectors.");

            // 2. Reset Chapter ingestion statuses
            $chapterCount = DB::table('chapters')->update([
                'ingestion_status' => 'pending',
                'ingested_at' => null,
            ]);
            $this->info("Reset ingestion status for {$chapterCount} chapters.");

            // 3. Reset Book statuses
            $bookCount = DB::table('books')->update([
                'status' => 'draft',
            ]);
            $this->info("Reset status for {$bookCount} books.");
        });

        $this->info('RAG data cleaned successfully. Ready for local Ollama ingestion.');
    }
}
