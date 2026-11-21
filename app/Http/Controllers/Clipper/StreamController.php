<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper\ClipperStream;
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
            $msg = "Started monitoring stream: {$stream->name}";
        } else {
            $stream->status = 'idle';
            $stream->monitoring_stopped_at = now();
            $stream->save();
            $msg = "Stopped monitoring stream: {$stream->name}";
        }

        return back()->with('status', $msg);
    }

    /**
     * Delete a stream configuration.
     */
    public function destroy(string $id): RedirectResponse
    {
        $stream = ClipperStream::findOrFail($id);
        $name = $stream->name;
        $stream->delete();

        return redirect()->route('clipper.streams.index')
            ->with('status', "Stream \"{$name}\" removed.");
    }
}
