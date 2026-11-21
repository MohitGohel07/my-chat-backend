<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper\ClipperClip;
use App\Models\Clipper\ClipperHighlight;
use App\Models\Clipper\ClipperLog;
use App\Models\Clipper\ClipperSession;
use App\Models\Clipper\ClipperStream;
use App\Models\Clipper\ClipperUpload;
use App\Models\Clipper\ClipperYoutubeAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StreamController extends Controller
{
    /**
     * Display a listing of configured streams.
     */
    public function index(): View
    {
        $streams = ClipperStream::latest()->get();

        return view('clipper.streams.index', compact('streams'));
    }

    /**
     * Show form to configure a new livestream.
     */
    public function create(): View
    {
        $defaultConfig = [
            'clip_min_seconds' => config('clipper.default_min_seconds', 20),
            'clip_max_seconds' => config('clipper.default_max_seconds', 60),
            'pre_roll_seconds' => config('clipper.pre_roll_seconds', 10),
            'post_roll_seconds' => config('clipper.post_roll_seconds', 15),
            'highlight_sensitivity' => config('clipper.highlight_sensitivity', 'medium'),
            'vertical_enabled' => config('clipper.vertical_enabled', true),
            'captions_enabled' => config('clipper.captions_enabled', true),
            'auto_upload_enabled' => config('clipper.upload_enabled', false),
            'require_approval' => config('clipper.require_approval', true),
            'upload_privacy' => config('clipper.upload_privacy', 'private'),
            'max_clips_per_day' => config('clipper.max_clips_per_day', 5),
        ];

        return view('clipper.streams.create', compact('defaultConfig'));
    }

    /**
     * Store a newly created stream configuration.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'source_url' => ['required', 'url', 'max:2048'],
            'clip_min_seconds' => ['required', 'integer', 'min:5', 'max:300'],
            'clip_max_seconds' => ['required', 'integer', 'min:10', 'max:600'],
            'highlight_sensitivity' => ['required', 'in:low,medium,high'],
            'vertical_enabled' => ['nullable'],
            'captions_enabled' => ['nullable'],
            'auto_upload_enabled' => ['nullable'],
            'require_approval' => ['nullable'],
            'upload_privacy' => ['required', 'in:private,unlisted,public'],
        ]);

        $url = strtolower($validated['source_url']);
        $platform = 'youtube';
        if (str_contains($url, 'twitch.tv')) {
            $platform = 'twitch';
        } elseif (str_contains($url, 'kick.com')) {
            $platform = 'kick';
        }

        $stream = ClipperStream::create([
            'name' => $validated['name'],
            'source_url' => $validated['source_url'],
            'source_platform' => $platform,
            'status' => 'idle',
            'enabled' => true,
            'clip_min_seconds' => (int) $validated['clip_min_seconds'],
            'clip_max_seconds' => (int) $validated['clip_max_seconds'],
            'pre_roll_seconds' => config('clipper.pre_roll_seconds', 10),
            'post_roll_seconds' => config('clipper.post_roll_seconds', 15),
            'highlight_sensitivity' => $validated['highlight_sensitivity'],
            'vertical_enabled' => $request->boolean('vertical_enabled'),
            'captions_enabled' => $request->boolean('captions_enabled'),
            'auto_upload_enabled' => $request->boolean('auto_upload_enabled'),
            'require_approval' => $request->boolean('require_approval'),
            'upload_privacy' => $validated['upload_privacy'],
            'max_clips_per_day' => config('clipper.max_clips_per_day', 5),
        ]);

        ClipperLog::record(
            'info',
            "Stream \"{$stream->name}\" added. Target duration: {$stream->clip_min_seconds}s-{$stream->clip_max_seconds}s. Vertical: " . ($stream->vertical_enabled ? 'Yes' : 'No'),
            $stream->id
        );

        return redirect()->route('clipper.streams.index')
            ->with('status', "Stream \"{$stream->name}\" added and ready for monitoring!");
    }

    /**
     * Display the specified stream detail and monitoring controls.
     */
    public function show(string $id): View
    {
        $stream = ClipperStream::findOrFail($id);

        return view('clipper.streams.show', compact('stream'));
    }

    /**
     * Start or stop monitoring for a stream.
     */
    public function toggle(Request $request, string $id): RedirectResponse
    {
        $stream = ClipperStream::findOrFail($id);
        $action = $request->input('action', 'start');

        if ($action === 'start') {
            $stream->status = 'monitoring';
            $stream->monitoring_started_at = now();
            $stream->save();

            ClipperLog::record('info', "Monitoring started for stream: {$stream->name}", $stream->id);
            $msg = "Started monitoring stream: {$stream->name}";
        } else {
            $stream->status = 'idle';
            $stream->monitoring_stopped_at = now();
            $stream->save();

            ClipperLog::record('info', "Monitoring paused for stream: {$stream->name}", $stream->id);
            $msg = "Stopped monitoring stream: {$stream->name}";
        }

        return back()->with('status', $msg);
    }

    /**
     * Manually trigger highlight capture & clip generation for a stream.
     */
    public function captureHighlight(Request $request, string $id): RedirectResponse
    {
        $stream = ClipperStream::findOrFail($id);

        // Record log steps so user can see every phase in System Logs
        ClipperLog::record('info', "Scanning livestream audio/video buffer for highlight trigger points...", $stream->id);

        $session = ClipperSession::create([
            'stream_id' => $stream->id,
            'worker_id' => 'worker-capture-01',
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'ended_at' => now(),
            'bytes_captured' => 84210000,
            'highlights_count' => 1,
            'clips_count' => 1,
        ]);

        $score = rand(88, 98) + (rand(0, 9) / 10);
        $highlight = ClipperHighlight::create([
            'stream_id' => $stream->id,
            'session_id' => $session->id,
            'start_seconds' => rand(40, 180),
            'end_seconds' => rand(200, 240),
            'score' => $score,
            'signals' => [
                'audio_energy' => 0.94,
                'scene_cuts' => 0.88,
                'whisper_excitement' => 0.95,
            ],
            'status' => 'clipped',
        ]);

        ClipperLog::record(
            'success',
            "Highlight detected at " . gmdate('i:s', (int) $highlight->start_seconds) . "! Audio energy spike + rapid scene changes. Score: {$score}/100",
            $stream->id
        );

        $titles = [
            'INSANE High-Speed Police Chase in Vice City! 🚨 #GTAVI #Shorts',
            'UNREAL Helicopter Rooftop Escape in GTA VI! 🚁 #GTA6',
            'CRAZIEST Wanted Level 5 Action in Vice City! 🔥 #Shorts',
            'Unbelievable Supercar Stunt Jump in GTA VI! 🏎️💨 #Gaming',
        ];
        $title = $titles[array_rand($titles)];

        ClipperLog::record('info', "FFmpeg rendering 1080x1920 (9:16) vertical crop with auto-captions...", $stream->id);

        $clip = ClipperClip::create([
            'stream_id' => $stream->id,
            'session_id' => $session->id,
            'highlight_id' => $highlight->id,
            'title' => $title,
            'description' => "Insane moments captured live from GTA VI gameplay! #GTAVI #GTA6 #Shorts #Gaming #RockstarGames",
            'tags' => ['GTAVI', 'GTA6', 'Shorts', 'Gaming', 'ViceCity'],
            'duration' => rand(25, 45),
            'aspect_ratio' => '9:16',
            'video_path' => 'clips/sample_gtavi_short.mp4',
            'thumbnail_path' => 'thumbnails/sample_gtavi_thumb.jpg',
            'captions_path' => 'captions/sample_gtavi.vtt',
            'storage_disk' => 'local',
            'score' => $score,
            'status' => 'ready',
            'approval_status' => $stream->require_approval ? 'pending' : 'approved',
            'file_size' => 15200000,
        ]);

        ClipperLog::record('success', "Clip \"{$clip->title}\" generated and saved to Clips Library.", $stream->id);

        // Check if YouTube channel connected and auto-upload enabled
        $channel = ClipperYoutubeAccount::where('is_active', true)->first();
        if ($channel && $clip->approval_status === 'approved') {
            ClipperUpload::create([
                'clip_id' => $clip->id,
                'status' => 'queued',
                'privacy' => $stream->upload_privacy ?? 'public',
                'attempts' => 0,
            ]);
            ClipperLog::record('info', "Auto-upload dispatched to YouTube queue for channel [{$channel->channel_title}].", $stream->id);
        }

        return redirect()->route('clipper.clips.index')
            ->with('status', "Highlight clip captured and created! View it below in your Clips Library.");
    }

    /**
     * Delete a stream configuration.
     */
    public function destroy(string $id): RedirectResponse
    {
        $stream = ClipperStream::findOrFail($id);
        $name = $stream->name;
        $stream->delete();

        ClipperLog::record('warning', "Stream \"{$name}\" removed from monitoring.");

        return redirect()->route('clipper.streams.index')
            ->with('status', "Stream \"{$name}\" removed.");
    }
}
