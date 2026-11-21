<?php

namespace App\Models\Clipper;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClipperStream extends Model
{
    use HasFactory;

    protected $table = 'clipper_streams';

    protected $fillable = [
        'name',
        'source_url',
        'source_platform',
        'status',
        'enabled',
        'clip_min_seconds',
        'clip_max_seconds',
        'pre_roll_seconds',
        'post_roll_seconds',
        'highlight_sensitivity',
        'vertical_enabled',
        'captions_enabled',
        'caption_language',
        'auto_upload_enabled',
        'require_approval',
        'upload_privacy',
        'max_clips_per_day',
        'monitoring_started_at',
        'monitoring_stopped_at',
        'last_checked_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'vertical_enabled' => 'boolean',
            'captions_enabled' => 'boolean',
            'auto_upload_enabled' => 'boolean',
            'require_approval' => 'boolean',
            'clip_min_seconds' => 'integer',
            'clip_max_seconds' => 'integer',
            'pre_roll_seconds' => 'integer',
            'post_roll_seconds' => 'integer',
            'max_clips_per_day' => 'integer',
            'monitoring_started_at' => 'datetime',
            'monitoring_stopped_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClipperSession::class, 'stream_id');
    }

    public function clips(): HasMany
    {
        return $this->hasMany(ClipperClip::class, 'stream_id');
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(ClipperHighlight::class, 'stream_id');
    }
}

