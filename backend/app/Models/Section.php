<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'chapter_id',
        'parent_id',
        'section_number',
        'title',
        'order',
        'raw_text',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Section::class, 'parent_id')->orderBy('order');
    }

    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(Chapter::class);
    }

    public function contentChunks(): HasMany
    {
        return $this->hasMany(ContentChunk::class)->orderBy('chunk_index');
    }
}
