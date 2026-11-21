<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HealthController extends Controller
{
    /**
     * Display overall system health, worker heartbeats, and queue statistics.
     */
    public function index(): View
    {
        // Safe check on jobs table
        $queueDepth = 0;
        $failedJobsCount = 0;
        try {
            $queueDepth = DB::table('jobs')->count();
            $failedJobsCount = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            // If table doesn't exist yet, keep 0
        }

        $workerStatus = \App\Models\Clipper\ClipperSetting::get('worker_status', 'offline');
        $workerLastSeen = \App\Models\Clipper\ClipperSetting::get('worker_last_seen');
        $telemetry = \App\Models\Clipper\ClipperSetting::get('worker_telemetry', []);

        $lastHeartbeatHuman = null;
        if ($workerLastSeen) {
            $lastSeenCarbon = \Carbon\Carbon::parse($workerLastSeen);
            if ($lastSeenCarbon->diffInMinutes(now()) > 3) {
                $workerStatus = 'offline';
            }
            $lastHeartbeatHuman = $lastSeenCarbon->diffForHumans();
        } else {
            $workerStatus = 'offline';
        }

        $health = [
            'worker' => [
                'status' => $workerStatus,
                'endpoint' => \App\Models\Clipper\ClipperSetting::get('worker_endpoint', config('clipper.worker_url', 'Not configured')),
                'last_heartbeat' => $lastHeartbeatHuman,
                'cpu_usage' => $telemetry['cpu_usage'] ?? 'Not reported',
                'memory_usage' => $telemetry['memory_usage'] ?? 'Not reported',
                'disk_free' => $telemetry['disk_free'] ?? 'Not reported',
            ],
            'scheduler' => [
                'status' => 'active',
                'last_run' => 'Checking...',
            ],
            'queue' => [
                'driver' => config('queue.default'),
                'pending_jobs' => $queueDepth,
                'failed_jobs' => $failedJobsCount,
            ],
            'storage' => [
                'disk' => config('clipper.storage_disk'),
                'status' => 'configured',
            ],
        ];

        return view('clipper.health.index', compact('health'));
    }

    /**
     * Simulate a worker heartbeat for testing and verification.
     */
    public function testPing(): RedirectResponse
    {
        \App\Models\Clipper\ClipperSetting::set('worker_status', 'online');
        \App\Models\Clipper\ClipperSetting::set('worker_last_seen', now()->toIso8601String());
        \App\Models\Clipper\ClipperSetting::set('worker_endpoint', 'cloud-worker-python-01');
        \App\Models\Clipper\ClipperSetting::set('worker_telemetry', [
            'worker_id' => 'cloud-worker-python-01',
            'endpoint' => 'cloud.worker',
            'cpu_usage' => '14.2%',
            'memory_usage' => '32.1% (320MB / 1024MB)',
            'disk_free' => '38.4 GB free',
            'active_tasks' => 0,
        ]);

        \App\Models\Clipper\ClipperLog::record('info', 'Worker heartbeat simulated successfully.');

        return back()->with('status', '✅ Worker heartbeat simulated! Status is now Online.');
    }
}

