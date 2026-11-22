<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper\ClipperClip;
use App\Models\Clipper\ClipperHighlight;
use App\Models\Clipper\ClipperLog;
use App\Models\Clipper\ClipperSession;
use App\Models\Clipper\ClipperSetting;
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
        $stream->status = 'monitoring';
        $stream->monitoring_started_at = now();
        $stream->save();

        // Remove any old static sample clips
        ClipperClip::where('video_path', 'like', '%sample%')->orWhereNull('video_path')->delete();

        // Signal online Railway Worker to cut the real live stream
        ClipperSetting::set('trigger_capture_' . $stream->id, true);

        ClipperLog::record('info', "⚡ Instant capture command sent to Dedicated Video Worker for stream: {$stream->name}", $stream->id);

        return redirect()->route('clipper.clips.index')
            ->with('status', "⚡ Live capture command dispatched! The Dedicated Video Worker is cutting the genuine livestream into a 9:16 vertical Short...");
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
