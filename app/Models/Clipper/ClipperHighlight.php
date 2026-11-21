<?php

namespace App\Models\Clipper;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClipperHighlight extends Model
{
    use HasFactory;

    protected $table = 'clipper_highlights';

    protected $fillable = [
        'stream_id',
        'session_id',
        'start_seconds',
        'end_seconds',
        'score',
        'signals',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_seconds' => 'float',
            'end_seconds' => 'float',
            'score' => 'float',
            'signals' => 'array',
        ];
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(ClipperStream::class, 'stream_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClipperSession::class, 'session_id');
    }
}

