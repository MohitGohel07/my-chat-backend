<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto Clipper Module Master Switch
    |--------------------------------------------------------------------------
    */
    'enabled' => env('AUTO_CLIPPER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Dedicated Worker Connection
    |--------------------------------------------------------------------------
    */
    'worker_url' => env('CLIPPER_WORKER_URL', 'http://127.0.0.1:8100'),
    'worker_token' => env('CLIPPER_WORKER_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Source Platform Restrictions & SSRF Guardrails
    |--------------------------------------------------------------------------
    */
    'allowed_hosts' => array_filter(array_map('trim', explode(',', env(
        'CLIPPER_SOURCE_ALLOWED_HOSTS',
        'youtube.com,www.youtube.com,youtu.be,twitch.tv,www.twitch.tv,kick.com,www.kick.com'
    )))),

    /*
    |--------------------------------------------------------------------------
    | Stream & Clipping Defaults
    |--------------------------------------------------------------------------
    */
    'max_active_streams' => (int) env('CLIPPER_MAX_ACTIVE_STREAMS', 1),
    'max_clips_per_day' => (int) env('CLIPPER_MAX_CLIPS_PER_DAY', 5),
    'default_min_seconds' => (int) env('CLIPPER_DEFAULT_MIN_SECONDS', 20),
    'default_max_seconds' => (int) env('CLIPPER_DEFAULT_MAX_SECONDS', 60),
    'pre_roll_seconds' => (int) env('CLIPPER_PRE_ROLL_SECONDS', 10),
    'post_roll_seconds' => (int) env('CLIPPER_POST_ROLL_SECONDS', 15),
    'highlight_sensitivity' => env('CLIPPER_HIGHLIGHT_SENSITIVITY', 'medium'),
    'captions_enabled' => (bool) env('CLIPPER_CAPTIONS_ENABLED', true),
    'vertical_enabled' => (bool) env('CLIPPER_VERTICAL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Upload & Publication Defaults
    |--------------------------------------------------------------------------
    */
    'require_approval' => (bool) env('CLIPPER_REQUIRE_APPROVAL', true),
    'upload_enabled' => (bool) env('CLIPPER_UPLOAD_ENABLED', false),
    'upload_privacy' => env('CLIPPER_UPLOAD_PRIVACY', 'private'),
    'upload_daily_limit' => (int) env('CLIPPER_UPLOAD_DAILY_LIMIT', 3),

    /*
    |--------------------------------------------------------------------------
    | Storage & Media Configuration
    |--------------------------------------------------------------------------
    */
    'storage_disk' => env('CLIPPER_STORAGE_DISK', env('FILESYSTEM_DISK', 'local')),

    /*
    |--------------------------------------------------------------------------
    | YouTube Data API v3 OAuth Settings
    |--------------------------------------------------------------------------
    */
    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID', ''),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET', ''),
        'redirect_uri' => env('YOUTUBE_REDIRECT_URI', ''),
    ],
];

