<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UploadQueueController extends Controller
{
    /**
     * Display the YouTube upload queue and daily quota status.
     */
    public function index(Request $request): View
    {
        $queue = []; // Scaffolding for Phase 1
        $dailyQuota = [
            'used' => 0,
            'limit' => config('clipper.upload_daily_limit', 3),
            'reset_time' => 'Midnight UTC',
        ];

        return view('clipper.uploads.index', compact('queue', 'dailyQuota'));
    }

    /**
     * Retry a failed upload item.
     */
    public function retry(string $id): RedirectResponse
    {
        return back()->with('status', 'Upload task requeued.');
    }
}

