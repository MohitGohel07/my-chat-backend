<?php

return [
    'auth_mode' => env('AUTH_MODE', 'email'),
    'otp_return_code' => (bool) env('CHAT_OTP_RETURN_CODE', false),
    'encryption_enabled' => (bool) env('CHAT_ENCRYPTION_ENABLED', false),
    'realtime_enabled' => (bool) env('CHAT_REALTIME_ENABLED', false),
    'push_enabled' => (bool) env('CHAT_PUSH_ENABLED', false),
    'otp_provider' => env('OTP_PROVIDER', 'log'),
    'max_upload_mb' => (int) env('CHAT_MAX_UPLOAD_MB', 25),
    'encryption_mode' => env('CHAT_ENCRYPTION_MODE', 'client-side'),
    'encryption_key_backup' => (bool) env('CHAT_ENCRYPTION_KEY_BACKUP', false),
];
