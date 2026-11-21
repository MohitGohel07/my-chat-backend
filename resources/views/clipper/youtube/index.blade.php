@extends('clipper.layouts.app')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">

    <div class="border-b border-zinc-800 pb-4">
        <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">YouTube Channel & OAuth</h1>
        <p class="text-xs sm:text-sm text-zinc-400 mt-0.5">
            Connect your authorized YouTube channel for automatic Shorts publishing
        </p>
    </div>

    <!-- Channel Connection Status Card -->
    <div class="gta-card space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $status['connected'] ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-red-950/80 text-red-400 border border-red-800' }}">
                    <span class="text-2xl">📺</span>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wider font-semibold text-zinc-400">Target Channel</div>
                    <div class="text-base font-bold text-white flex items-center gap-2">
                        <span>{{ $status['channel_name'] ?? 'No YouTube Channel Connected' }}</span>
                        @if ($status['connected'])
                            <span class="badge badge-green text-[10px]">Connected</span>
                        @endif
                    </div>
                    <div class="text-xs text-zinc-400">
                        @if ($status['connected'])
                            Channel ID: <span class="font-mono text-zinc-300">{{ $status['channel_id'] ?? '—' }}</span>
                        @else
                            Connect your account to enable direct uploads
                        @endif
                    </div>
                </div>
            </div>

            <div>
                @if ($status['connected'])
                    <form action="{{ route('clipper.youtube.disconnect') }}" method="POST">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-lg bg-rose-600/80 hover:bg-rose-500 text-white text-xs font-semibold transition border border-rose-600">
                            Disconnect Channel
                        </button>
                    </form>
                @else
                    @if ($status['client_id_configured'])
                        <a href="{{ route('clipper.youtube.connect') }}" class="gta-btn-primary text-xs">
                            🔗 Connect YouTube Account
                        </a>
                    @else
                        <button type="button" class="gta-btn-primary text-xs" disabled title="Configure OAuth credentials in environment first" style="opacity:0.6;cursor:not-allowed;">
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

    <!-- Publishing Defaults Form -->
    <form action="{{ route('clipper.youtube.update') }}" method="POST" class="space-y-6">
        @csrf

        <div class="gta-card space-y-4">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Default Upload Behavior</h2>

            <div>
                <label for="default_privacy" class="block text-xs font-medium text-zinc-300 mb-1">Default Privacy</label>
                <select id="default_privacy" name="default_privacy" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-pink-500">
                    <option value="private" {{ $status['default_privacy'] === 'private' ? 'selected' : '' }}>Private (Recommended — safe preview in Studio)</option>
                    <option value="unlisted" {{ $status['default_privacy'] === 'unlisted' ? 'selected' : '' }}>Unlisted (Visible only via URL)</option>
                    <option value="public" {{ $status['default_privacy'] === 'public' ? 'selected' : '' }}>Public (Published immediately to subscribers)</option>
                </select>
                <p class="text-[11px] text-zinc-500 mt-1">
                    Keep set to Private until you have reviewed your first rendered clips.
                </p>
            </div>

            <div>
                <label for="daily_limit" class="block text-xs font-medium text-zinc-300 mb-1">Daily Upload Ceiling</label>
                <input type="number" id="daily_limit" name="daily_limit" value="{{ $status['daily_limit'] }}" min="1" max="20" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-pink-500">
                <p class="text-[11px] text-zinc-500 mt-1">Prevents exceeding YouTube Data API v3 daily quota (10,000 units/day).</p>
            </div>

            <div class="pt-2">
                <label class="flex items-center gap-2 p-2.5 rounded-lg border border-zinc-800 bg-zinc-900/50 cursor-pointer">
                    <input type="checkbox" name="auto_upload_enabled" value="1" {{ $status['auto_upload_enabled'] ? 'checked' : '' }} class="accent-pink-500 rounded">
                    <div>
                        <div class="text-xs font-semibold text-white">Enable Automated Uploads</div>
                        <div class="text-[10px] text-zinc-400">If unchecked, clips remain in library until uploaded manually</div>
                    </div>
                </label>
            </div>

            <div class="flex justify-end pt-2">
                <button type="submit" class="gta-btn-primary text-xs">
                    Save Upload Preferences
                </button>
            </div>
        </div>
    </form>

    <!-- OAuth Callback Reference -->
    <div class="gta-card space-y-2 text-xs">
        <h3 class="font-bold text-white text-xs uppercase tracking-wider">OAuth 2.0 Redirect URI</h3>
        <p class="text-zinc-400">Add this authorized redirect URI into your Google Cloud Console project:</p>
        <div class="p-2.5 rounded bg-zinc-900 border border-zinc-800 font-mono text-[11px] text-pink-300 break-all select-all">
            {{ $status['redirect_uri'] }}
        </div>
    </div>

</div>
@endsection
