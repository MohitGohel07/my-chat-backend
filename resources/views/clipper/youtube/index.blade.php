@extends('clipper.layouts.app')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">

    <div class="border-b border-zinc-800 pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sticky top-14 sm:static z-30 bg-zinc-950/95 sm:bg-transparent backdrop-blur-md sm:backdrop-blur-none py-2 -mx-4 px-4 sm:mx-0 sm:px-0">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">YouTube Channel & OAuth</h1>
            <p class="text-xs sm:text-sm text-zinc-400 mt-0.5">
                Connect your authorized YouTube channel for automatic Shorts publishing
            </p>
        </div>
        <div class="shrink-0 w-full sm:w-auto">
            <button type="submit" form="youtubeSettingsForm" class="gta-btn-primary w-full sm:w-auto text-xs sm:text-sm py-2.5 px-5 font-bold shadow-lg shadow-pink-600/30 flex items-center justify-center gap-2">
                <span>💾</span>
                <span>Save Preferences</span>
            </button>
        </div>
    </div>

    <!-- Channel Connection Status Card -->
    <div class="gta-card space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                @if (! empty($status['channel_avatar']))
                    <img src="{{ $status['channel_avatar'] }}" alt="{{ $status['channel_name'] }}" class="w-14 h-14 rounded-full object-cover border-2 border-pink-500 shadow-lg shadow-pink-500/20 shrink-0">
                @else
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 {{ $status['connected'] ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-red-950/80 text-red-400 border border-red-800' }}">
                        <span class="text-2xl">📺</span>
                    </div>
                @endif

                <div class="space-y-0.5 min-w-0">
                    <div class="text-[11px] uppercase tracking-wider font-semibold text-zinc-400">Target Channel</div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-base sm:text-lg font-bold text-white truncate max-w-xs">
                            {{ $status['channel_name'] ?? 'No YouTube Channel Connected' }}
                        </span>
                        @if ($status['connected'])
                            <span class="badge badge-green text-[10px]">Connected</span>
                        @endif
                    </div>

                    @if ($status['connected'])
                        <div class="flex flex-wrap items-center gap-2 pt-0.5">
                            @if (! empty($status['channel_url']))
                                <a href="{{ $status['channel_url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 text-xs font-semibold text-pink-400 hover:text-pink-300 underline underline-offset-4">
                                    <span>Open YouTube Channel</span>
                                    <span>↗</span>
                                </a>
                                <span class="text-zinc-600">•</span>
                            @endif
                            <span class="text-[11px] text-zinc-400 font-mono">
                                ID: {{ $status['channel_id'] ?? '—' }}
                            </span>
                        </div>
                    @else
                        <div class="text-xs text-zinc-400">
                            Connect your account to enable direct uploads
                        </div>
                    @endif
                </div>
            </div>

            <div class="w-full sm:w-auto shrink-0">
                @if ($status['connected'])
                    <form action="{{ route('clipper.youtube.disconnect') }}" method="POST" class="w-full sm:w-auto" onsubmit="return confirm('Disconnect this YouTube channel?');">
                        @csrf
                        <button type="submit" class="w-full sm:w-auto px-4 py-2.5 rounded-lg bg-rose-950/80 hover:bg-rose-900 text-rose-300 text-xs font-semibold transition border border-rose-800 text-center">
                            Disconnect Channel
                        </button>
                    </form>
                @else
                    @if ($status['client_id_configured'])
                        <a href="{{ route('clipper.youtube.connect') }}" class="gta-btn-primary w-full sm:w-auto text-xs py-2.5 px-4 text-center">
                            🔗 Connect YouTube Account
                        </a>
                    @else
                        <button type="button" class="gta-btn-primary w-full sm:w-auto text-xs py-2.5 px-4" disabled title="Configure OAuth credentials in environment first" style="opacity:0.6;cursor:not-allowed;">
                            🔗 Connect YouTube Account
                        </button>
                    @endif
                @endif
            </div>
        </div>

        @if (! $status['client_id_configured'])
            <div class="p-3 rounded-lg bg-amber-950/40 border border-amber-800/80 text-amber-200 text-xs">
                <div class="font-bold mb-1">⚠️ Google OAuth credentials required</div>
                Configure <code class="text-pink-300">YOUTUBE_CLIENT_ID</code> and <code class="text-pink-300">YOUTUBE_CLIENT_SECRET</code> in your environment variables to enable the connection button.
            </div>
        @endif
    </div>

    <!-- Publishing Defaults Form (Optimized for Mobile Touch) -->
    <form id="youtubeSettingsForm" action="{{ route('clipper.youtube.update') }}" method="POST" class="space-y-6">
        @csrf

        <div class="gta-card space-y-4">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Default Upload Behavior</h2>

            <div>
                <label for="default_privacy" class="block text-xs font-medium text-zinc-300 mb-1">Default Privacy</label>
                <select id="default_privacy" name="default_privacy" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-pink-500">
                    <option value="private" {{ $status['default_privacy'] === 'private' ? 'selected' : '' }}>Private (Recommended — safe preview in Studio)</option>
                    <option value="unlisted" {{ $status['default_privacy'] === 'unlisted' ? 'selected' : '' }}>Unlisted (Visible only via direct link)</option>
                    <option value="public" {{ $status['default_privacy'] === 'public' ? 'selected' : '' }}>Public (Published immediately to subscribers)</option>
                </select>
                <p class="text-[11px] text-zinc-500 mt-1">
                    When set to Public, approved videos publish directly to your channel's public feed.
                </p>
            </div>

            <div>
                <label for="default_upload_type" class="block text-xs font-medium text-zinc-300 mb-1">Default Publishing Format</label>
                <select id="default_upload_type" name="default_upload_type" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-pink-500">
                    <option value="short" {{ ($status['default_upload_type'] ?? 'short') === 'short' ? 'selected' : '' }}>📱 YouTube Short (9:16 Vertical, includes #Shorts tags)</option>
                    <option value="standard" {{ ($status['default_upload_type'] ?? 'short') === 'standard' ? 'selected' : '' }}>🎬 Standard Video (Regular YouTube video)</option>
                </select>
                <p class="text-[11px] text-zinc-500 mt-1">
                    Choose whether automatically enqueued clips should publish to the YouTube Shorts feed or standard video uploads.
                </p>
            </div>

            <div>
                <label for="daily_limit" class="block text-xs font-medium text-zinc-300 mb-1">Daily Upload Ceiling</label>
                <input type="number" id="daily_limit" name="daily_limit" value="{{ $status['daily_limit'] }}" min="1" max="20" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2.5 text-sm text-white focus:outline-none focus:border-pink-500">
                <p class="text-[11px] text-zinc-500 mt-1">Prevents exceeding YouTube Data API v3 daily quota (10,000 units/day).</p>
            </div>

            <div class="pt-1">
                <label class="flex items-center gap-3 p-3 rounded-lg border border-zinc-800 bg-zinc-900/50 cursor-pointer">
                    <input type="checkbox" name="auto_upload_enabled" value="1" {{ $status['auto_upload_enabled'] ? 'checked' : '' }} class="accent-pink-500 w-4 h-4 rounded">
                    <div>
                        <div class="text-xs font-semibold text-white">Enable Automated Uploads</div>
                        <div class="text-[10px] text-zinc-400">If unchecked, clips remain in library until uploaded manually</div>
                    </div>
                </label>
            </div>

            <!-- Save Button: Full width on mobile, well-padded and never blocked -->
            <div class="pt-4 mt-2 border-t border-zinc-800 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="text-[11px] text-zinc-500 hidden sm:block">Settings take effect immediately.</div>
                <button type="submit" class="gta-btn-primary w-full sm:w-auto text-sm py-3 px-6 font-bold shadow-lg shadow-pink-600/30">
                    💾 Save Upload Preferences
                </button>
            </div>
        </div>
    </form>

    <!-- OAuth Callback Reference -->
    <div class="gta-card space-y-2 text-xs">
        <h3 class="font-bold text-white text-xs uppercase tracking-wider">OAuth 2.0 Redirect URI</h3>
        <p class="text-zinc-400">Authorized redirect URI registered in Google Cloud Console:</p>
        <div class="p-2.5 rounded bg-zinc-900 border border-zinc-800 font-mono text-[11px] text-pink-300 break-all select-all">
            {{ $status['redirect_uri'] }}
        </div>
    </div>

</div>
@endsection
