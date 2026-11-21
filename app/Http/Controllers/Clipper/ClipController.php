<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClipController extends Controller
{
    /**
     * Display the mobile-first clip library.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $clips = []; // Scaffolding empty list for Phase 1

        return view('clipper.clips.index', compact('clips', 'status'));
    }

    /**
     * Display a single clip preview and metadata editor.
     */
    public function show(string $id): View
    {
        $clip = [
            'id' => $id,
            'title' => 'Crazy High-Speed Chase in Vice City Downtown',
            'description' => "Unbelievable moments captured live from GTA VI! #GTAVI #GTA6 #Gaming #Shorts",
            'duration' => 38.5,
            'aspect_ratio' => '9:16',
            'score' => 88.5,
            'status' => 'ready',
            'approval_status' => 'pending',
            'video_url' => null,
            'thumbnail_url' => null,
            'created_at' => now()->toFormattedDateString(),
        ];

        return view('clipper.clips.show', compact('clip'));
    }

    /**
     * Approve or reject a clip.
     */
    public function updateApproval(Request $request, string $id): RedirectResponse
    {
        $action = $request->input('action', 'approve');

        return back()->with('status', "Clip status updated: {$action}.");
    }
}

