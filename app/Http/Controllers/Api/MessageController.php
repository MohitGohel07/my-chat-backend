<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);
        $request->user()->forceFill(['last_seen_at' => now()])->save();

        $duration = $conversation->disappearing_duration ?? 'off';
        if ($duration && $duration !== 'off') {
            $cutoff = match ($duration) {
                '1_hour' => now()->subHour(),
                '8_hours' => now()->subHours(8),
                '1_week' => now()->subWeek(),
                '1_month' => now()->subMonth(),
                default => null,
            };

            if ($cutoff) {
                $conversation->messages()
                    ->where(function ($query) use ($cutoff) {
                        $query->where(function ($q) use ($cutoff) {
                            $q->whereNotNull('read_at')->where('read_at', '<=', $cutoff);
                        })->orWhere('created_at', '<=', $cutoff);
                    })
                    ->delete();
            }
        }

        $conversation->messages()->whereNull('read_at')->where('user_id', '!=', $request->user()->id)->update(['read_at' => now()]);

        return response()->json($conversation->messages()->with('user:id,name,username,avatar,phone,last_seen_at')->latest()->cursorPaginate(30));
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'type' => ['sometimes', 'string', 'in:text,image,video,contact'],
            'attachment_url' => ['nullable', 'string', 'max:500000'],
            'metadata' => ['nullable', 'array'],
        ]);

        if (empty($data['body']) && empty($data['attachment_url']) && empty($data['metadata'])) {
            return response()->json(['message' => 'Message body or attachment is required.'], 422);
        }

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'] ?? '',
            'type' => $data['type'] ?? 'text',
            'attachment_url' => $data['attachment_url'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        return response()->json($message->load('user:id,name,username,avatar,phone,last_seen_at'), 201);
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);
        $conversation->messages()->delete();

        return response()->json(['message' => 'Chat cleared.']);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->participants()->whereKey($request->user()->id)->exists(), 403);
    }
}
