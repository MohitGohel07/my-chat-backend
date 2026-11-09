<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $calls = CallLog::where('caller_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with([
                'caller:id,name,username,avatar,phone,last_seen_at',
                'receiver:id,name,username,avatar,phone,last_seen_at',
            ])
            ->latest()
            ->paginate(30);

        return response()->json($calls);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['sometimes', 'string', 'in:voice,video'],
            'status' => ['sometimes', 'string', 'in:outgoing,incoming,missed,ended'],
            'duration_seconds' => ['sometimes', 'integer', 'min:0'],
        ]);

        $call = CallLog::create([
            'caller_id' => $request->user()->id,
            'receiver_id' => $data['receiver_id'],
            'type' => $data['type'] ?? 'voice',
            'status' => $data['status'] ?? 'outgoing',
            'duration_seconds' => $data['duration_seconds'] ?? 0,
        ]);

        return response()->json(
            $call->load([
                'caller:id,name,username,avatar,phone,last_seen_at',
                'receiver:id,name,username,avatar,phone,last_seen_at',
            ]),
            201
        );
    }
}

