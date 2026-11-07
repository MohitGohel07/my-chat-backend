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
        $conversation->messages()->whereNull('read_at')->where('user_id', '!=', $request->user()->id)->update(['read_at' => now()]);

        return response()->json($conversation->messages()->with('user:id,name,last_seen_at')->latest()->cursorPaginate(30));
    }

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($request, $conversation);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $message = $conversation->messages()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        return response()->json($message->load('user:id,name,last_seen_at'), 201);
    }

    private function authorizeParticipant(Request $request, Conversation $conversation): void
    {
        abort_unless($conversation->participants()->whereKey($request->user()->id)->exists(), 403);
    }
}
