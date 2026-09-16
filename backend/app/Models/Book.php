<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'title',
        'status',
        'source_type',
        'version',
        'source_file_path',
        'source_file_type',
        'processing_metadata',
    ];

    protected function casts(): array
    {
        return [
            'processing_metadata' => 'array',
        ];
    }

    // ── Scopes & Helpers ─────────────────────────────────────────────────────

    /**
     * Append a log entry to processing_metadata.
     */
    public function logProgress(string $message, string $level = 'info'): void
    {
        $metadata = $this->processing_metadata ?? [];
        $metadata['logs'][] = [
            'timestamp' => now()->toIso8601String(),
            'level' => $level,
            'message' => $message,
        ];

        $this->update(['processing_metadata' => $metadata]);
    }

    /**
     * Get the canonical Smart Adama book for learner progress tracking.
     */
    public static function canonical(): ?self
    {
        return self::where('title', 'Smart Adama: Complete Guide & Ecosystem')
            ->orWhere('title', 'Smart Adama: A Conceptual Framework')
            ->first() ?? self::first();
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class)->orderBy('order');
    }

    public function ingestionJobs(): HasMany
    {
        return $this->hasMany(IngestionJob::class)->orderByDesc('created_at');
    }
}
