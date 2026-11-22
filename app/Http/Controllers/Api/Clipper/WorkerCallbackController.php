<?php

namespace App\Http\Controllers\Api\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper\ClipperClip;
use App\Models\Clipper\ClipperHighlight;
use App\Models\Clipper\ClipperLog;
use App\Models\Clipper\ClipperSession;
use App\Models\Clipper\ClipperSetting;
use App\Models\Clipper\ClipperStream;
use App\Models\Clipper\ClipperUpload;
use App\Models\Clipper\ClipperYoutubeAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerCallbackController extends Controller
{
    /**
     * Receive worker heartbeat ping and update health status.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'worker_id' => ['nullable', 'string'],
            'endpoint' => ['nullable', 'string'],
            'cpu_usage' => ['nullable', 'string'],
            'memory_usage' => ['nullable', 'string'],
            'disk_free' => ['nullable', 'string'],
            'active_tasks' => ['nullable', 'integer'],
        ]);

        ClipperSetting::set('worker_status', 'online');
        ClipperSetting::set('worker_last_seen', now()->toIso8601String());
        ClipperSetting::set('worker_endpoint', $payload['endpoint'] ?? config('clipper.worker_url'));
        ClipperSetting::set('worker_telemetry', $payload);

        return response()->json([
            'status' => 'acknowledged',
            'server_time' => now()->toIso8601String(),
            'active_streams_count' => ClipperStream::where('status', 'monitoring')->count(),
        ]);
    }

    /**
     * Provide list of streams that are actively set to 'monitoring'.
     */
    public function activeStreams(Request $request): JsonResponse
    {
        try {
            $streams = ClipperStream::all()->filter(function ($stream) {
                $triggerKey = 'trigger_capture_' . $stream->id;
                $isTriggered = (bool) ClipperSetting::get($triggerKey, false);
                return in_array($stream->status, ['monitoring', 'active']) || $isTriggered;
            })->values()->map(function ($stream) {
                $triggerKey = 'trigger_capture_' . $stream->id;
                $isTriggered = (bool) ClipperSetting::get($triggerKey, false);
                if ($isTriggered) {
                    ClipperSetting::forget($triggerKey);
                }
                $stream->trigger_instant_capture = $isTriggered;
                return $stream;
            });

            return response()->json([
                'streams' => $streams,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('activeStreams API error: ' . $e->getMessage());
            return response()->json([
                'streams' => ClipperStream::whereIn('status', ['monitoring', 'active'])->get(),
            ]);
        }
    }

    /**
     * Receive a newly rendered highlight clip from the worker service.
     */
    public function clipCreated(Request $request): JsonResponse
    {
        // Normalize tags if passed as JSON string or comma-separated string in multipart/form-data
        if ($request->has('tags')) {
            $tagsInput = $request->input('tags');
            if (is_string($tagsInput)) {
                $decoded = json_decode($tagsInput, true);
                $tagsInput = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $tagsInput)));
            }
            $request->merge(['tags' => (array) $tagsInput]);
        }

        // Normalize signals if passed as JSON string or string in multipart/form-data
        if ($request->has('signals')) {
            $signalsInput = $request->input('signals');
            if (is_string($signalsInput)) {
                $decoded = json_decode($signalsInput, true);
                $signalsInput = is_array($decoded) ? $decoded : ['energy_spike' => 0.95];
            }
            $request->merge(['signals' => (array) $signalsInput]);
        }

        $data = $request->validate([
            'stream_id' => ['required', 'exists:clipper_streams,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable'],
            'duration' => ['required', 'numeric'],
            'aspect_ratio' => ['nullable', 'string'],
            'score' => ['required', 'numeric'],
            'video_path' => ['nullable', 'string'],
            'thumbnail_path' => ['nullable', 'string'],
            'captions_path' => ['nullable', 'string'],
            'signals' => ['nullable'],
            'start_seconds' => ['nullable', 'numeric'],
            'end_seconds' => ['nullable', 'numeric'],
            'file_size' => ['nullable', 'integer'],
        ]);

        $stream = ClipperStream::findOrFail($data['stream_id']);

        // Handle physical video file uploaded by Worker
        $videoPath = $data['video_path'] ?? null;
        if ($request->hasFile('video')) {
            $uploaded = $request->file('video');
            $filename = 'clip_' . $stream->id . '_' . time() . '.mp4';
            $clipsDir = public_path('clips');
            if (! is_dir($clipsDir)) {
                @mkdir($clipsDir, 0755, true);
            }
            $uploaded->move($clipsDir, $filename);
            $videoPath = 'clips/' . $filename;
        }

        $tags = is_array($data['tags'] ?? null) ? $data['tags'] : (json_decode($data['tags'] ?? '[]', true) ?: ['GTAVI', 'Shorts', 'Gaming']);
        $signals = is_array($data['signals'] ?? null) ? $data['signals'] : (json_decode($data['signals'] ?? '[]', true) ?: ['energy_spike' => 0.95]);

        // 1. Create or link to session
        $session = ClipperSession::create([
            'stream_id' => $stream->id,
            'worker_id' => $request->input('worker_id', 'python-worker-01'),
            'status' => 'completed',
            'started_at' => now()->subSeconds((int) $data['duration'] + 60),
            'ended_at' => now(),
            'bytes_captured' => $data['file_size'] ?? 15000000,
            'highlights_count' => 1,
            'clips_count' => 1,
        ]);

        // 2. Create highlight record
        $highlight = ClipperHighlight::create([
            'stream_id' => $stream->id,
            'session_id' => $session->id,
            'start_seconds' => $data['start_seconds'] ?? 0,
            'end_seconds' => $data['end_seconds'] ?? $data['duration'],
            'score' => $data['score'],
            'signals' => $signals,
            'status' => 'clipped',
        ]);

        // 3. Create clip record with actual dynamic video path
        $clip = ClipperClip::create([
            'stream_id' => $stream->id,
            'session_id' => $session->id,
            'highlight_id' => $highlight->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? "Highlights from {$stream->name}! #GTAVI #Shorts #Gaming",
            'tags' => $tags,
            'duration' => $data['duration'],
            'aspect_ratio' => $data['aspect_ratio'] ?? '9:16',
            'video_path' => $videoPath,
            'thumbnail_path' => $data['thumbnail_path'] ?? null,
            'captions_path' => $data['captions_path'] ?? null,
            'storage_disk' => config('clipper.storage_disk', 'local'),
            'score' => $data['score'],
            'status' => 'ready',
            'approval_status' => $stream->require_approval ? 'pending' : 'approved',
            'file_size' => $data['file_size'] ?? ($videoPath && file_exists(public_path($videoPath)) ? filesize(public_path($videoPath)) : null),
        ]);

        ClipperLog::record(
            'success',
            "Real clip cut from livestream: \"{$clip->title}\" (Duration: {$clip->duration}s, Path: {$clip->video_path})",
            $stream->id
        );

        // 4. Auto-enqueue for YouTube upload if eligible
        $youtube = ClipperYoutubeAccount::where('is_active', true)->first();
        if ($youtube && $clip->approval_status === 'approved') {
            $defaultPrivacy = ClipperSetting::get('default_privacy', $stream->upload_privacy ?? 'public');
            $defaultUploadType = ClipperSetting::get('default_upload_type', 'short');
            $upload = ClipperUpload::create([
                'clip_id' => $clip->id,
                'status' => 'queued',
                'privacy' => $defaultPrivacy,
                'upload_type' => $defaultUploadType,
                'attempts' => 0,
            ]);

            ClipperLog::record(
                'info',
                "Dispatched clip [{$clip->id}] to YouTube Upload Queue ({$defaultUploadType}) for channel [{$youtube->channel_title}].",
                $stream->id
            );
        }

        return response()->json([
            'status' => 'success',
            'clip_id' => $clip->id,
            'approval_status' => $clip->approval_status,
        ], 201);
    }
}

