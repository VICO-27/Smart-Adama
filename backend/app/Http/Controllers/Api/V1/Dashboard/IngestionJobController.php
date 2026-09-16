<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessManualIngestionJob;
use App\Models\Book;
use App\Models\IngestionJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IngestionJobController extends Controller
{
    /**
     * Start a new manual ingestion job
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'book_id' => 'required|uuid|exists:books,id',
            'mode' => 'required|in:structured,page',
        ]);

        Book::where('id', $validated['book_id'])->update(['status' => 'ingesting']);

        $job = IngestionJob::create([
            'book_id' => $validated['book_id'],
            'source_type' => 'manual',
            'status' => 'pending',
            'current_stage' => 'validation',
            'started_at' => now(),
            'created_by' => $request->user()?->id,
        ]);

        // Dispatch background job (we will create this job next)
        ProcessManualIngestionJob::dispatch($job->id, $validated['mode']);

        return response()->json(['job' => $job], 201);
    }

    public function getActiveJobs(Request $request): JsonResponse
    {
        $activeJobs = IngestionJob::whereIn('status', ['pending', 'processing'])->get();

        return response()->json(['jobs' => $activeJobs]);
    }

    public function getLogs(IngestionJob $job): JsonResponse
    {
        $path = storage_path("logs/jobs/{$job->id}.log");
        $logs = [];

        if (file_exists($path)) {
            // Read lines
            $content = file_get_contents($path);
            $logs = array_filter(explode(PHP_EOL, $content));
        }

        return response()->json(['logs' => array_values($logs)]);
    }

    /**
     * SSE stream for live progress
     */
    public function streamProgress(Request $request): StreamedResponse
    {
        return response()->stream(function () {
            // Send initial ping to keep connection alive
            echo "event: ping\n";
            echo "data: {}\n\n";
            ob_flush();
            flush();

            // Run for max 5 minutes (300 iterations * 1 sec)
            for ($i = 0; $i < 300; $i++) {
                if (connection_aborted()) {
                    break;
                }

                // Get active jobs
                $activeJobs = IngestionJob::whereIn('status', ['pending', 'processing'])->get();

                if ($activeJobs->isNotEmpty()) {
                    $payload = json_encode(['jobs' => $activeJobs]);
                    echo "data: {$payload}\n\n";
                    ob_flush();
                    flush();
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }

    public function pause(IngestionJob $job): JsonResponse
    {
        $job->update(['status' => 'paused']);

        return response()->json(['job' => $job]);
    }

    public function resume(IngestionJob $job): JsonResponse
    {
        $job->update(['status' => 'pending']);

        // Here we would re-dispatch or signal the worker to pick it up
        // ProcessManualIngestionJob::dispatch($job->id, 'resume');
        return response()->json(['job' => $job]);
    }

    public function cancel(IngestionJob $job): JsonResponse
    {
        $job->update(['status' => 'cancelled']);

        return response()->json(['job' => $job]);
    }

    public function retry(IngestionJob $job): JsonResponse
    {
        $job->update(['status' => 'pending']);

        // Determine mode based on book contents or a saved config
        // ProcessManualIngestionJob::dispatch($job->id, 'resume');
        return response()->json(['job' => $job]);
    }
}
