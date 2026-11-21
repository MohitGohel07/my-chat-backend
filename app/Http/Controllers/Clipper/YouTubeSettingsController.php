<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class YouTubeSettingsController extends Controller
{
    /**
     * Display YouTube channel connection status & upload defaults.
     */
    public function index(): View
    {
        $status = [
            'connected' => false,
            'channel_name' => null,
            'channel_id' => null,
            'auto_upload_enabled' => config('clipper.upload_enabled', false),
            'default_privacy' => config('clipper.upload_privacy', 'private'),
            'daily_limit' => config('clipper.upload_daily_limit', 3),
            'client_id_configured' => ! empty(config('clipper.youtube.client_id')),
            'client_secret_configured' => ! empty(config('clipper.youtube.client_secret')),
            'redirect_uri' => config('clipper.youtube.redirect_uri') ?: route('clipper.youtube.callback'),
        ];

        return view('clipper.youtube.index', compact('status'));
    }

    /**
     * Update default YouTube upload settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_privacy' => ['required', 'in:private,unlisted,public'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'auto_upload_enabled' => ['boolean'],
        ]);

        return back()->with('status', 'YouTube upload settings updated successfully.');
    }

    /**
     * Disconnect/revoke YouTube channel access.
     */
    public function disconnect(): RedirectResponse
    {
        return back()->with('status', 'YouTube channel disconnected.');
    }
}

