<?php

namespace App\Http\Controllers\Clipper;

use App\Http\Controllers\Controller;
use App\Models\Clipper\ClipperSetting;
use App\Models\Clipper\ClipperYoutubeAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class YouTubeSettingsController extends Controller
{
    /**
     * Display YouTube channel connection status & upload defaults.
     */
    public function index(): View
    {
        $account = ClipperYoutubeAccount::where('is_active', true)->latest()->first();

        // Auto-fetch channel avatar and custom URL if missing
        if ($account && ! empty($account->access_token) && empty($account->channel_avatar)) {
            try {
                $channelRes = Http::withToken($account->access_token)
                    ->timeout(5)
                    ->get('https://www.googleapis.com/youtube/v3/channels', [
                        'part' => 'snippet',
                        'mine' => 'true',
                    ]);

                if ($channelRes->successful() && ! empty($channelRes->json('items'))) {
                    $item = $channelRes->json('items')[0];
                    $account->channel_id = $item['id'] ?? $account->channel_id;
                    $account->channel_title = $item['snippet']['title'] ?? $account->channel_title;
                    $account->channel_avatar = $item['snippet']['thumbnails']['high']['url']
                        ?? ($item['snippet']['thumbnails']['medium']['url']
                        ?? ($item['snippet']['thumbnails']['default']['url'] ?? null));

                    $customUrl = $item['snippet']['customUrl'] ?? null;
                    $account->channel_url = $customUrl
                        ? 'https://www.youtube.com/' . ltrim($customUrl, '/')
                        : 'https://www.youtube.com/channel/' . $account->channel_id;

                    $account->save();
                }
            } catch (\Throwable $e) {
                // Ignore transient API failure
            }
        }

        $channelUrl = $account?->channel_url;
        if (! $channelUrl && $account?->channel_id) {
            $channelUrl = 'https://www.youtube.com/channel/' . $account->channel_id;
        }

        $status = [
            'connected' => (bool) ($account && ! empty($account->access_token)),
            'channel_name' => $account?->channel_title,
            'channel_id' => $account?->channel_id,
            'channel_avatar' => $account?->channel_avatar,
            'channel_url' => $channelUrl,
            'auto_upload_enabled' => (bool) ClipperSetting::get('auto_upload_enabled', config('clipper.upload_enabled', false)),
            'default_privacy' => ClipperSetting::get('default_privacy', config('clipper.upload_privacy', 'private')),
            'default_upload_type' => ClipperSetting::get('default_upload_type', 'short'),
            'daily_limit' => (int) ClipperSetting::get('daily_limit', config('clipper.upload_daily_limit', 3)),
            'client_id_configured' => ! empty(config('clipper.youtube.client_id')),
            'client_secret_configured' => ! empty(config('clipper.youtube.client_secret')),
            'redirect_uri' => config('clipper.youtube.redirect_uri') ?: route('clipper.youtube.callback'),
        ];

        return view('clipper.youtube.index', compact('status'));
    }

    /**
     * Redirect to Google OAuth consent screen to connect channel.
     */
    public function connect(): RedirectResponse
    {
        $clientId = config('clipper.youtube.client_id');
        $redirectUri = config('clipper.youtube.redirect_uri') ?: route('clipper.youtube.callback');

        if (empty($clientId)) {
            return back()->with('error', 'YOUTUBE_CLIENT_ID is not configured in environment variables.');
        }

        $state = bin2hex(random_bytes(16));
        session(['youtube_oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/youtube.upload https://www.googleapis.com/auth/youtube.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent select_account',
            'state' => $state,
        ]);

        return redirect("https://accounts.google.com/o/oauth2/v2/auth?{$params}");
    }

    /**
     * Handle the OAuth redirect callback from Google.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('clipper.youtube.index')
                ->with('error', 'Google authorization was denied or cancelled: ' . $request->input('error'));
        }

        $code = $request->input('code');
        if (empty($code)) {
            return redirect()->route('clipper.youtube.index')
                ->with('error', 'Authorization code was not returned by Google.');
        }

        $clientId = config('clipper.youtube.client_id');
        $clientSecret = config('clipper.youtube.client_secret');
        $redirectUri = config('clipper.youtube.redirect_uri') ?: route('clipper.youtube.callback');

        // Exchange authorization code for tokens
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (! $response->successful()) {
            return redirect()->route('clipper.youtube.index')
                ->with('error', 'Could not obtain tokens from Google: ' . ($response->json('error_description') ?? $response->body()));
        }

        $data = $response->json();
        $accessToken = $data['access_token'] ?? null;
        $refreshToken = $data['refresh_token'] ?? null;
        $expiresIn = $data['expires_in'] ?? 3600;

        // Fetch YouTube channel details
        $channelTitle = 'Authorized YouTube Channel';
        $channelId = null;
        $channelAvatar = null;
        $channelUrl = null;

        try {
            $channelRes = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet',
                    'mine' => 'true',
                ]);

            if ($channelRes->successful() && ! empty($channelRes->json('items'))) {
                $item = $channelRes->json('items')[0];
                $channelId = $item['id'] ?? null;
                $channelTitle = $item['snippet']['title'] ?? $channelTitle;
                $channelAvatar = $item['snippet']['thumbnails']['high']['url']
                    ?? ($item['snippet']['thumbnails']['medium']['url']
                    ?? ($item['snippet']['thumbnails']['default']['url'] ?? null));

                $customUrl = $item['snippet']['customUrl'] ?? null;
                $channelUrl = $customUrl
                    ? 'https://www.youtube.com/' . ltrim($customUrl, '/')
                    : 'https://www.youtube.com/channel/' . $channelId;
            }
        } catch (\Throwable $e) {
            // Keep defaults if channel query fails
        }

        // Save account credentials
        $account = ClipperYoutubeAccount::firstOrNew(['is_active' => true]);
        $account->channel_id = $channelId;
        $account->channel_title = $channelTitle;
        $account->channel_avatar = $channelAvatar;
        $account->channel_url = $channelUrl ?? ($channelId ? 'https://www.youtube.com/channel/' . $channelId : null);
        $account->access_token = $accessToken;
        if ($refreshToken) {
            $account->refresh_token = $refreshToken;
        }
        $account->expires_at = now()->addSeconds((int) $expiresIn);
        $account->scope = $data['scope'] ?? null;
        $account->is_active = true;
        $account->save();

        return redirect()->route('clipper.youtube.index')
            ->with('status', "YouTube Channel \"{$channelTitle}\" successfully connected!");
    }

    /**
     * Update default YouTube upload settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_privacy' => ['required', 'in:private,unlisted,public'],
            'default_upload_type' => ['nullable', 'in:short,standard'],
            'daily_limit' => ['required', 'integer', 'min:1', 'max:20'],
            'auto_upload_enabled' => ['nullable'],
        ]);

        ClipperSetting::set('default_privacy', $validated['default_privacy']);
        ClipperSetting::set('default_upload_type', $validated['default_upload_type'] ?? 'short');
        ClipperSetting::set('daily_limit', (int) $validated['daily_limit']);
        ClipperSetting::set('auto_upload_enabled', $request->boolean('auto_upload_enabled'));

        return back()->with('status', 'YouTube upload settings updated successfully.');
    }

    /**
     * Disconnect/revoke YouTube channel access.
     */
    public function disconnect(): RedirectResponse
    {
        ClipperYoutubeAccount::query()->update(['is_active' => false]);

        return back()->with('status', 'YouTube channel disconnected.');
    }
}
