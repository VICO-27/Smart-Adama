<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookPage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'book_id',
        'page_number',
        'raw_text',
        'clean_text',
        'is_suspicious',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_suspicious' => 'boolean',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
