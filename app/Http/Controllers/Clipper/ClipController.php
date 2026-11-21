<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper\ClipperClip;
use App\Models\Clipper\ClipperSetting;
use App\Models\Clipper\ClipperUpload;
use App\Models\Clipper\ClipperYoutubeAccount;
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

        $query = ClipperClip::with(['stream', 'upload'])->latest();

        if ($status === 'pending') {
            $query->where('approval_status', 'pending');
        } elseif ($status === 'ready') {
            $query->where('status', 'ready')->where('approval_status', 'approved');
        } elseif ($status === 'uploaded') {
            $query->whereHas('upload', fn ($q) => $q->where('status', 'published'));
        } elseif ($status === 'failed') {
            $query->where('status', 'failed');
        }

        $clips = $query->get();

        return view('clipper.clips.index', compact('clips', 'status'));
    }

    /**
     * Display a single clip preview and metadata editor.
     */
    public function show(string $id): View
    {
        $clip = ClipperClip::with(['stream', 'upload', 'highlight'])->findOrFail($id);

        return view('clipper.clips.show', compact('clip'));
    }

    /**
     * Approve or reject a clip.
     */
    public function updateApproval(Request $request, string $id): RedirectResponse
    {
        $clip = ClipperClip::findOrFail($id);
        $action = $request->input('action', 'approve');

        if ($action === 'approve') {
            $clip->approval_status = 'approved';
            $clip->save();

            // Auto enqueue to upload queue if not already enqueued
            $defaultPrivacy = ClipperSetting::get('default_privacy', 'public');
            ClipperUpload::firstOrCreate(
                ['clip_id' => $clip->id],
                [
                    'status' => 'queued',
                    'privacy' => $defaultPrivacy,
                    'attempts' => 0,
                ]
            );

            return back()->with('status', "Clip \"{$clip->title}\" approved and added to YouTube upload queue!");
        }

        $clip->approval_status = 'rejected';
        $clip->save();

        return back()->with('status', "Clip archived/rejected.");
    }

    /**
     * Update clip metadata (title and description).
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $clip = ClipperClip::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $clip->update($validated);

        return back()->with('status', 'Clip title & description updated.');
    }

    /**
     * Delete clip.
     */
    public function destroy(string $id): RedirectResponse
    {
        $clip = ClipperClip::findOrFail($id);
        $title = $clip->title;
        $clip->delete();

        return redirect()->route('clipper.clips.index')->with('status', "Clip \"{$title}\" removed.");
    }

    /**
     * Remove all static placeholder clips.
     */
    public function clearStatic(): RedirectResponse
    {
        $deleted = ClipperClip::where('video_path', 'like', '%sample%')
            ->orWhereNull('video_path')
            ->delete();

        return redirect()->route('clipper.clips.index')
            ->with('status', "🧹 Removed {$deleted} static placeholder clips. Only real livestream cuts will appear!");
    }
}
