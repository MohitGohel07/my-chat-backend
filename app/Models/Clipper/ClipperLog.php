<?php

namespace App\Models\Clipper;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClipperLog extends Model
{
    use HasFactory;

    protected $table = 'clipper_logs';

    protected $fillable = [
        'stream_id',
        'level',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(ClipperStream::class, 'stream_id');
    }

    public static function record(string $level, string $message, ?int $streamId = null, array $context = []): static
    {
        return static::create([
            'level' => $level,
            'message' => $message,
            'stream_id' => $streamId,
            'context' => $context,
        ]);
    }
}

