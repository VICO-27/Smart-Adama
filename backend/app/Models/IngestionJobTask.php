<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngestionJobTask extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'ingestion_job_id',
        'task_type',
        'status',
        'target_id',
        'payload',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(IngestionJob::class, 'ingestion_job_id');
    }
}
