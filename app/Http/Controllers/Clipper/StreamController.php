<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
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
        $streams = []; // Scaffolding empty list for Phase 1

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
            'vertical_enabled' => ['boolean'],
            'captions_enabled' => ['boolean'],
            'auto_upload_enabled' => ['boolean'],
            'require_approval' => ['boolean'],
            'upload_privacy' => ['required', 'in:private,unlisted,public'],
        ]);

        return redirect()->route('clipper.streams.index')
            ->with('status', 'Stream configuration saved successfully.');
    }

    /**
     * Display the specified stream detail and monitoring controls.
     */
    public function show(string $id): View
    {
        $stream = [
            'id' => $id,
            'name' => 'GTA VI Official Launch Showcase',
            'source_url' => 'https://www.youtube.com/watch?v=mock_gtavi',
            'source_platform' => 'youtube',
            'status' => 'idle',
            'clip_min_seconds' => 20,
            'clip_max_seconds' => 60,
            'pre_roll_seconds' => 10,
            'post_roll_seconds' => 15,
            'highlight_sensitivity' => 'medium',
            'vertical_enabled' => true,
            'captions_enabled' => true,
            'auto_upload_enabled' => false,
            'require_approval' => true,
            'upload_privacy' => 'private',
            'max_clips_per_day' => 5,
            'created_at' => now()->toFormattedDateString(),
        ];

        return view('clipper.streams.show', compact('stream'));
    }

    /**
     * Start or stop monitoring for a stream.
     */
    public function toggle(Request $request, string $id): RedirectResponse
    {
        $action = $request->input('action', 'start');

        return back()->with('status', "Stream monitoring has been set to {$action}.");
    }
}

