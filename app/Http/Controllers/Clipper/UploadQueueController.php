<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper\ClipperSetting;
use App\Models\Clipper\ClipperUpload;
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
        $queue = ClipperUpload::with('clip')->latest()->get();

        $dailyLimit = (int) ClipperSetting::get('daily_limit', config('clipper.upload_daily_limit', 3));
        $usedToday = ClipperUpload::whereDate('created_at', today())
            ->where('status', 'published')
            ->count();

        $dailyQuota = [
            'used' => $usedToday,
            'limit' => $dailyLimit,
            'reset_time' => 'Midnight UTC',
        ];

        return view('clipper.uploads.index', compact('queue', 'dailyQuota'));
    }

    /**
     * Retry a failed upload item.
     */
    public function retry(string $id): RedirectResponse
    {
        $upload = ClipperUpload::findOrFail($id);
        $upload->status = 'queued';
        $upload->last_error = null;
        $upload->save();

        return back()->with('status', 'Upload task requeued for publishing.');
    }
}
