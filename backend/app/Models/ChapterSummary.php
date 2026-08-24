<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChapterSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_number',
        'chapter_title',
        'summary_text',
    ];
}
