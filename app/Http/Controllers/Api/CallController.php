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
            'status' => ['sometimes', 'string', 'in:outgoing,incoming,missed,ended,ringing,accepted,rejected'],
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

    /**
     * Start/initiate a live call. Sets status to 'ringing'.
     */
    public function initiate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'type' => ['sometimes', 'string', 'in:voice,video'],
        ]);

        $callerId = $request->user()->id;
        $receiverId = (int) $data['receiver_id'];

        if ($callerId === $receiverId) {
            return response()->json(['message' => 'You cannot call yourself.'], 422);
        }

        // Clean up old ringing calls for these users
        CallLog::where(function ($q) use ($callerId, $receiverId) {
            $q->where('caller_id', $callerId)
              ->orWhere('receiver_id', $callerId)
              ->orWhere('caller_id', $receiverId)
              ->orWhere('receiver_id', $receiverId);
        })
        ->where('status', 'ringing')
        ->where('created_at', '<', now()->subSeconds(45))
        ->update(['status' => 'missed']);

        $call = CallLog::create([
            'caller_id' => $callerId,
            'receiver_id' => $receiverId,
            'type' => $data['type'] ?? 'voice',
            'status' => 'ringing',
            'duration_seconds' => 0,
        ]);

        return response()->json([
            'message' => 'Call initiated',
            'call' => $call->load([
                'caller:id,name,username,avatar,phone,last_seen_at',
                'receiver:id,name,username,avatar,phone,last_seen_at',
            ]),
        ], 201);
    }

    /**
     * Check if there is an active incoming ringing call for the authenticated user.
     */
    public function incoming(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Auto expire stale ringing calls
        CallLog::where('receiver_id', $userId)
            ->where('status', 'ringing')
            ->where('created_at', '<', now()->subSeconds(45))
            ->update(['status' => 'missed']);

        $call = CallLog::where('receiver_id', $userId)
            ->where('status', 'ringing')
            ->where('created_at', '>=', now()->subSeconds(45))
            ->with([
                'caller:id,name,username,avatar,phone,last_seen_at',
                'receiver:id,name,username,avatar,phone,last_seen_at',
            ])
            ->latest()
            ->first();

        return response()->json([
            'call' => $call,
        ]);
    }

    /**
     * Poll the status of a specific call.
     */
    public function status(Request $request, CallLog $call): JsonResponse
    {
        $userId = $request->user()->id;

        if ($call->caller_id !== $userId && $call->receiver_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($call->status === 'ringing' && $call->created_at < now()->subSeconds(45)) {
            $call->status = 'missed';
            $call->save();
        }

        return response()->json([
            'call' => $call->load([
                'caller:id,name,username,avatar,phone,last_seen_at',
                'receiver:id,name,username,avatar,phone,last_seen_at',
            ]),
        ]);
    }

    /**
     * Receiver accepts the incoming call.
     */
    public function accept(Request $request, CallLog $call): JsonResponse
    {
        $userId = $request->user()->id;

        if ($call->receiver_id !== $userId) {
            return response()->json(['message' => 'Only the receiver can accept the call'], 403);
        }

        if ($call->status === 'ringing') {
            $call->status = 'accepted';
            $call->save();
        }

        return response()->json([
            'message' => 'Call accepted',
            'call' => $call->load([
                'caller:id,name,username,avatar,phone,last_seen_at',
                'receiver:id,name,username,avatar,phone,last_seen_at',
            ]),
        ]);
    }

    /**
     * Either user rejects / declines or cancels the ringing call.
     */
    public function reject(Request $request, CallLog $call): JsonResponse
    {
        $userId = $request->user()->id;

        if ($call->caller_id !== $userId && $call->receiver_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($call->status === 'ringing') {
            $call->status = ($userId === $call->caller_id) ? 'missed' : 'rejected';
            $call->save();
        }

        return response()->json([
            'message' => 'Call rejected',
            'call' => $call->load([
                'caller:id,name,username,avatar,phone,last_seen_at',
                'receiver:id,name,username,avatar,phone,last_seen_at',
            ]),
        ]);
    }

    /**
     * Either user ends the active call.
     */
    public function end(Request $request, CallLog $call): JsonResponse
    {
        $userId = $request->user()->id;

        if ($call->caller_id !== $userId && $call->receiver_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $duration = (int) $request->input('duration_seconds', $call->duration_seconds);

        $call->status = 'ended';
        if ($duration > 0) {
            $call->duration_seconds = $duration;
        }
        $call->save();

        return response()->json([
            'message' => 'Call ended',
            'call' => $call->load([
                'caller:id,name,username,avatar,phone,last_seen_at',
                'receiver:id,name,username,avatar,phone,last_seen_at',
            ]),
        ]);
    }
}
