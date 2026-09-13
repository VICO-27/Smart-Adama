<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngestionJob extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'book_id',
        'source_type',
        'status',
        'current_stage',
        'total_items',
        'completed_items',
        'failed_items',
        'started_at',
        'completed_at',
        'failed_at',
        'last_error',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(IngestionJobTask::class);
    }
}
