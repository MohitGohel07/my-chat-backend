<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $realtime = config('chat.realtime_enabled');
        $pushNotifications = config('chat.push_enabled');
        $endToEndEncryption = config('chat.encryption_enabled');

        return response()->json([
            'auth_mode' => config('chat.auth_mode'),
            'realtime' => $realtime,
            'push_notifications' => $pushNotifications,
            'end_to_end_encryption' => $endToEndEncryption,
            'features' => [
                'realtime' => $realtime,
                'push_notifications' => $pushNotifications,
                'end_to_end_encryption' => $endToEndEncryption,
            ],
        ]);
    }
}
