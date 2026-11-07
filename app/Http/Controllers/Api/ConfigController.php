<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'auth_mode' => config('chat.auth_mode'),
            'features' => [
                'realtime' => config('chat.realtime_enabled'),
                'push_notifications' => config('chat.push_enabled'),
                'end_to_end_encryption' => config('chat.encryption_enabled'),
            ],
        ]);
    }
}
