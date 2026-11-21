<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper\ClipperClip;
use App\Models\Clipper\ClipperSetting;
use App\Models\Clipper\ClipperStream;
use App\Models\Clipper\ClipperUpload;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Render the Auto Clipper admin dashboard summary with live database metrics.
     */
    public function index(Request $request): View
    {
        $workerStatus = ClipperSetting::get('worker_status', 'offline');
        $workerLastSeen = ClipperSetting::get('worker_last_seen');
        $lastSeenHuman = null;

        if ($workerLastSeen) {
            $lastSeenCarbon = Carbon::parse($workerLastSeen);
            if ($lastSeenCarbon->diffInMinutes(now()) > 3) {
                $workerStatus = 'offline';
            }
            $lastSeenHuman = $lastSeenCarbon->diffForHumans();
        } else {
            $workerStatus = 'offline';
        }

        $metrics = [
            'active_monitoring' => ClipperStream::where('status', 'monitoring')->count(),
            'queued' => ClipperStream::where('status', 'idle')->count(),
            'capturing' => ClipperStream::where('status', 'capturing')->count(),
            'processing' => ClipperClip::whereIn('status', ['pending_render', 'rendering'])->count(),
            'clips_today' => ClipperClip::whereDate('created_at', today())->count(),
            'uploads_pending' => ClipperClip::where('approval_status', 'pending')->count(),
            'uploads_completed' => ClipperUpload::where('status', 'published')->count(),
            'failed_tasks' => ClipperClip::where('status', 'failed')->count() + ClipperUpload::where('status', 'failed')->count(),
            'worker_heartbeat' => [
                'status' => $workerStatus,
                'last_seen' => $lastSeenHuman,
                'endpoint' => ClipperSetting::get('worker_endpoint', config('clipper.worker_url', 'Not configured')),
            ],
        ];

        $recentStreams = ClipperStream::latest()->take(5)->get();
        $recentClips = ClipperClip::latest()->take(6)->get();

        return view('clipper.dashboard', compact('metrics', 'recentStreams', 'recentClips'));
    }
}
