<?php

namespace App\Models\Clipper;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClipperUpload extends Model
{
    use HasFactory;

    protected $table = 'clipper_uploads';

    protected $fillable = [
        'clip_id',
        'youtube_video_id',
        'youtube_url',
        'status',
        'privacy',
        'attempts',
        'last_error',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function clip(): BelongsTo
    {
        return $this->belongsTo(ClipperClip::class, 'clip_id');
    }
}

