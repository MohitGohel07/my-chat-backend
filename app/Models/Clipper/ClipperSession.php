<?php

namespace App\Models\Clipper;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClipperSession extends Model
{
    use HasFactory;

    protected $table = 'clipper_sessions';

    protected $fillable = [
        'stream_id',
        'worker_id',
        'status',
        'started_at',
        'ended_at',
        'bytes_captured',
        'highlights_count',
        'clips_count',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'bytes_captured' => 'integer',
            'highlights_count' => 'integer',
            'clips_count' => 'integer',
        ];
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(ClipperStream::class, 'stream_id');
    }

    public function clips(): HasMany
    {
        return $this->hasMany(ClipperClip::class, 'session_id');
    }
}

