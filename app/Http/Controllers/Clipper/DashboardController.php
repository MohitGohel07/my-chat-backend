<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Render the Auto Clipper admin dashboard summary.
     */
    public function index(Request $request): View
    {
        // Metric counters (scaffolded with zero-state / live defaults for Phase 1)
        $metrics = [
            'active_monitoring' => 0,
            'queued' => 0,
            'capturing' => 0,
            'processing' => 0,
            'clips_today' => 0,
            'uploads_pending' => 0,
            'uploads_completed' => 0,
            'failed_tasks' => 0,
            'worker_heartbeat' => [
                'status' => 'offline',
                'last_seen' => null,
                'endpoint' => config('clipper.worker_url'),
            ],
        ];

        $recentStreams = [];
        $recentClips = [];

        return view('clipper.dashboard', compact('metrics', 'recentStreams', 'recentClips'));
    }
}

