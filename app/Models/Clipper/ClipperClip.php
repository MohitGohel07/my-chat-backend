<?php

namespace App\Models\Clipper;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClipperClip extends Model
{
    use HasFactory;

    protected $table = 'clipper_clips';

    protected $fillable = [
        'stream_id',
        'session_id',
        'highlight_id',
        'title',
        'description',
        'tags',
        'duration',
        'aspect_ratio',
        'video_path',
        'thumbnail_path',
        'captions_path',
        'storage_disk',
        'score',
        'status',
        'approval_status',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'duration' => 'float',
            'score' => 'float',
            'file_size' => 'integer',
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

    public function highlight(): BelongsTo
    {
        return $this->belongsTo(ClipperHighlight::class, 'highlight_id');
    }

    public function upload(): HasOne
    {
        return $this->hasOne(ClipperUpload::class, 'clip_id');
    }
}

