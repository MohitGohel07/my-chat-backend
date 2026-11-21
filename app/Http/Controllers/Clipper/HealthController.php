<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
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

        $health = [
            'worker' => [
                'status' => 'not_reported',
                'endpoint' => config('clipper.worker_url'),
                'last_heartbeat' => null,
                'cpu_usage' => 'Not reported',
                'memory_usage' => 'Not reported',
                'disk_free' => 'Not reported',
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
}

