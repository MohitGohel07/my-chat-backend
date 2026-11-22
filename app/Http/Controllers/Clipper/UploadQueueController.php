<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper\ClipperSetting;
use App\Models\Clipper\ClipperUpload;
use App\Services\Clipper\YouTubePublisher;
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
    public function retry(Request $request, string $id): RedirectResponse
    {
        $upload = ClipperUpload::findOrFail($id);
        $upload->status = 'queued';
        $upload->last_error = null;
        if ($request->filled('upload_type')) {
            $upload->upload_type = $request->input('upload_type');
        }
        $upload->save();

        return back()->with('status', 'Upload task requeued for publishing.');
    }

    /**
     * Instantly trigger upload processing for an item.
     */
    public function publishNow(Request $request, string $id, YouTubePublisher $publisher): RedirectResponse
    {
        $upload = ClipperUpload::findOrFail($id);

        if ($request->filled('upload_type')) {
            $upload->upload_type = $request->input('upload_type');
            $upload->save();
        }

        $success = $publisher->publish($upload);

        if ($success) {
            $typeLabel = ($upload->upload_type === 'standard') ? 'Standard Video' : 'YouTube Short';
            return back()->with('status', "🎉 Successfully published to YouTube as {$typeLabel}!");
        }

        return back()->with('error', 'Upload failed: ' . ($upload->last_error ?? 'Check System Logs for details.'));
    }
}
