<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()
                ->conversations()
                ->with('participants:id,name,username,email,phone,avatar,last_seen_at')
                ->latest()
                ->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_group' => ['sometimes', 'boolean'],
            'title' => ['required_if:is_group,true', 'nullable', 'string', 'max:120'],
            'user_ids' => ['required_if:is_group,true', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
        $user = $request->user();

        if ($data['is_group'] ?? false) {
            $participantIds = collect($data['user_ids'])->push($user->id)->unique()->values()->all();
            $conversation = Conversation::create(['title' => $data['title'], 'is_group' => true, 'disappearing_duration' => 'off']);
            $conversation->participants()->attach($participantIds);

            return response()->json($conversation->load('participants:id,name,username,email,phone,avatar,last_seen_at'), 201);
        }

        abort_unless(isset($data['user_id']), 422, 'A user is required for a personal conversation.');
        abort_if($user->id === (int) $data['user_id'], 422, 'You cannot start a conversation with yourself.');

        $existing = $user->conversations()
            ->where('is_group', false)
            ->whereHas('participants', fn ($query) => $query->whereKey($data['user_id']))
            ->first();

        if ($existing) {
            return response()->json($existing->load('participants:id,name,username,email,phone,avatar,last_seen_at'));
        }

        $conversation = Conversation::create(['is_group' => false, 'disappearing_duration' => 'off']);
        $conversation->participants()->attach([$user->id, $data['user_id']]);

        return response()->json($conversation->load('participants:id,name,username,email,phone,avatar,last_seen_at'), 201);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->participants()->whereKey($request->user()->id)->exists(), 403);
        return response()->json($conversation->load('participants:id,name,username,email,phone,avatar,last_seen_at'));
    }

    public function updateDisappearing(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->participants()->whereKey($request->user()->id)->exists(), 403);

        $data = $request->validate([
            'disappearing_duration' => ['required', 'string', 'in:off,1_hour,8_hours,1_week,1_month'],
        ]);

        $conversation->update(['disappearing_duration' => $data['disappearing_duration']]);

        return response()->json([
            'message' => 'Disappearing messages setting updated.',
            'conversation' => $conversation->fresh()->load('participants:id,name,username,email,phone,avatar,last_seen_at'),
        ]);
    }

    public function lock(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->participants()->whereKey($request->user()->id)->exists(), 403);

        $data = $request->validate([
            'is_locked' => ['required', 'boolean'],
            'lock_pin' => ['nullable', 'string', 'max:10'],
        ]);

        $conversation->update([
            'is_locked' => $data['is_locked'],
            'lock_pin' => $data['is_locked'] ? ($data['lock_pin'] ?? null) : null,
        ]);

        return response()->json([
            'message' => 'Chat lock status updated.',
            'conversation' => $conversation->fresh()->load('participants:id,name,username,email,phone,avatar,last_seen_at'),
        ]);
    }
}
