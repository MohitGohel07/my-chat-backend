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
        return response()->json($request->user()->conversations()->with('participants')->latest()->paginate(20));
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
            $conversation = Conversation::create(['title' => $data['title'], 'is_group' => true]);
            $conversation->participants()->attach($participantIds);

            return response()->json($conversation->load('participants'), 201);
        }

        abort_unless(isset($data['user_id']), 422, 'A user is required for a personal conversation.');
        abort_if($user->id === (int) $data['user_id'], 422, 'You cannot start a conversation with yourself.');

        $existing = $user->conversations()
            ->where('is_group', false)
            ->whereHas('participants', fn ($query) => $query->whereKey($data['user_id']))
            ->first();

        if ($existing) {
            return response()->json($existing->load('participants'));
        }

        $conversation = Conversation::create(['is_group' => false]);
        $conversation->participants()->attach([$user->id, $data['user_id']]);

        return response()->json($conversation->load('participants'), 201);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->participants()->whereKey($request->user()->id)->exists(), 403);
        return response()->json($conversation->load('participants'));
    }
}
