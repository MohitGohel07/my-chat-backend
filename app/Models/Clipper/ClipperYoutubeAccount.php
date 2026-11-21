<?php

namespace App\Models\Clipper;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClipperYoutubeAccount extends Model
{
    use HasFactory;

    protected $table = 'clipper_youtube_accounts';

    protected $fillable = [
        'channel_id',
        'channel_title',
        'channel_avatar',
        'channel_url',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_at',
        'scope',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at ? $this->expires_at->isPast() : true;
    }
}

