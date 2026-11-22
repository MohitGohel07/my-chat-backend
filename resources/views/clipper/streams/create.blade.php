@extends('clipper.layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    <!-- Top Header with Immediate Action Button (Sticky on Mobile) -->
    <div class="border-b border-zinc-800 pb-4 sticky top-14 sm:static z-30 bg-zinc-950/95 sm:bg-transparent backdrop-blur-md sm:backdrop-blur-none py-2 -mx-4 px-4 sm:mx-0 sm:px-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-lg sm:shadow-none">
        <div>
            <a href="{{ route('clipper.streams.index') }}" class="text-xs text-zinc-400 hover:text-white inline-flex items-center gap-1 mb-1">
                <span>&larr;</span> Back to Streams
            </a>
            <h1 class="text-lg sm:text-2xl font-bold text-white tracking-tight">Configure New Livestream / Channel</h1>
            <p class="text-[11px] sm:text-sm text-zinc-400 mt-0.5">
                Paste any YouTube Live stream, Channel (@handle), or video URL
            </p>
        </div>
        <div class="shrink-0 w-full sm:w-auto">
            <button type="submit" form="createStreamForm" class="gta-btn-primary w-full sm:w-auto text-xs sm:text-sm py-2.5 px-5 shadow-lg shadow-pink-600/30 font-bold flex items-center justify-center gap-2">
                <span>💾</span>
                <span>Save & Start Monitoring</span>
            </button>
        </div>
    </div>

    <form id="createStreamForm" action="{{ route('clipper.streams.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Stream Source Info -->
        <div class="gta-card space-y-4">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">1. Source Stream / Channel</h2>

            <div>
                <label for="name" class="block text-xs font-medium text-zinc-300 mb-1">Stream Name / Channel Title</label>
                <input type="text" id="name" name="name" value="{{ old('name', 'GTA VI Live Gameplay') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-pink-500"
                       placeholder="e.g. GTA VI Live Gameplay or Channel Name">
            </div>

            <div>
                <label for="source_url" class="block text-xs font-medium text-zinc-300 mb-1">Livestream, Video, or Channel URL</label>
                <input type="text" id="source_url" name="source_url" value="{{ old('source_url') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2 text-sm text-white font-mono focus:outline-none focus:border-pink-500"
                       placeholder="https://www.youtube.com/@ChannelName or https://www.youtube.com/live/... or @ChannelHandle">
                <p class="text-[11px] text-zinc-400 mt-1 flex flex-col gap-0.5">
                    <span class="text-pink-400 font-semibold">💡 Supports all formats:</span>
                    <span>• <strong>Channel Handle / URL:</strong> <code class="text-zinc-300">@MikkGamer</code> or <code class="text-zinc-300">https://www.youtube.com/@ChannelName</code> (automatically starts clipping the second the channel goes live!)</span>
                    <span>• <strong>Live Feed / Video:</strong> <code class="text-zinc-300">https://www.youtube.com/live/FYxga5CEaFw</code> or any regular YouTube video/VOD.</span>
                </p>
            </div>
        </div>

        <!-- Highlight & Clipping Rules -->
        <div class="gta-card space-y-4">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">2. Clipping & Highlight Rules</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="clip_min_seconds" class="block text-xs font-medium text-zinc-300 mb-1">Min Duration (seconds)</label>
                    <input type="number" id="clip_min_seconds" name="clip_min_seconds" value="{{ old('clip_min_seconds', $defaultConfig['clip_min_seconds']) }}" min="5" max="300" required
                           class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-pink-500">
                </div>

                <div>
                    <label for="clip_max_seconds" class="block text-xs font-medium text-zinc-300 mb-1">Max Duration (seconds)</label>
                    <input type="number" id="clip_max_seconds" name="clip_max_seconds" value="{{ old('clip_max_seconds', $defaultConfig['clip_max_seconds']) }}" min="10" max="600" required
                           class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-pink-500">
                    <p class="text-[10px] text-zinc-500 mt-0.5">YouTube Shorts maximum is 60 seconds.</p>
                </div>
            </div>

            <div>
                <label for="highlight_sensitivity" class="block text-xs font-medium text-zinc-300 mb-1">Highlight Sensitivity</label>
                <select id="highlight_sensitivity" name="highlight_sensitivity" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-pink-500">
                    <option value="low" {{ old('highlight_sensitivity', $defaultConfig['highlight_sensitivity']) === 'low' ? 'selected' : '' }}>Low (Only major high-energy peaks)</option>
                    <option value="medium" {{ old('highlight_sensitivity', $defaultConfig['highlight_sensitivity']) === 'medium' ? 'selected' : '' }}>Medium (Balanced gaming moments - Recommended)</option>
                    <option value="high" {{ old('highlight_sensitivity', $defaultConfig['highlight_sensitivity']) === 'high' ? 'selected' : '' }}>High (Captures frequent action & speech)</option>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                <label class="flex items-center gap-2 p-2.5 rounded-lg border border-zinc-800 bg-zinc-900/50 cursor-pointer">
                    <input type="checkbox" name="vertical_enabled" value="1" {{ old('vertical_enabled', $defaultConfig['vertical_enabled']) ? 'checked' : '' }} class="accent-pink-500 rounded">
                    <div>
                        <div class="text-xs font-semibold text-white">9:16 Vertical Render</div>
                        <div class="text-[10px] text-zinc-400">Cropped 1080x1920 for YouTube Shorts</div>
                    </div>
                </label>

                <label class="flex items-center gap-2 p-2.5 rounded-lg border border-zinc-800 bg-zinc-900/50 cursor-pointer">
                    <input type="checkbox" name="captions_enabled" value="1" {{ old('captions_enabled', $defaultConfig['captions_enabled']) ? 'checked' : '' }} class="accent-pink-500 rounded">
                    <div>
                        <div class="text-xs font-semibold text-white">Speech Captions (Whisper)</div>
                        <div class="text-[10px] text-zinc-400">Auto-transcription with subtitle burn</div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Publication & YouTube Behavior -->
        <div class="gta-card space-y-4">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">3. Publication Controls</h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-center gap-2 p-2.5 rounded-lg border border-zinc-800 bg-zinc-900/50 cursor-pointer">
                    <input type="checkbox" name="require_approval" value="1" {{ old('require_approval', $defaultConfig['require_approval']) ? 'checked' : '' }} class="accent-pink-500 rounded">
                    <div>
                        <div class="text-xs font-semibold text-white">Require Manual Approval</div>
                        <div class="text-[10px] text-zinc-400">Review video on phone before upload</div>
                    </div>
                </label>

                <label class="flex items-center gap-2 p-2.5 rounded-lg border border-zinc-800 bg-zinc-900/50 cursor-pointer">
                    <input type="checkbox" name="auto_upload_enabled" value="1" {{ old('auto_upload_enabled', $defaultConfig['auto_upload_enabled']) ? 'checked' : '' }} class="accent-pink-500 rounded">
                    <div>
                        <div class="text-xs font-semibold text-white">Auto-Publish to YouTube</div>
                        <div class="text-[10px] text-zinc-400">Queue automatically when ready</div>
                    </div>
                </label>
            </div>

            <div>
                <label for="upload_privacy" class="block text-xs font-medium text-zinc-300 mb-1">Default Upload Privacy</label>
                <select id="upload_privacy" name="upload_privacy" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-pink-500">
                    <option value="private" {{ old('upload_privacy', $defaultConfig['upload_privacy']) === 'private' ? 'selected' : '' }}>Private (Recommended for initial tests)</option>
                    <option value="unlisted" {{ old('upload_privacy', $defaultConfig['upload_privacy']) === 'unlisted' ? 'selected' : '' }}>Unlisted (Accessible only via direct link)</option>
                    <option value="public" {{ old('upload_privacy', $defaultConfig['upload_privacy']) === 'public' ? 'selected' : '' }}>Public (Published directly to channel)</option>
                </select>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-4 pb-32 sm:pb-8">
            <a href="{{ route('clipper.streams.index') }}" class="px-4 py-2.5 rounded-lg text-sm text-center text-zinc-400 hover:text-white transition">Cancel</a>
            <button type="submit" class="gta-btn-primary text-sm py-3 px-6 font-bold shadow-lg shadow-pink-600/30 flex items-center justify-center gap-2">
                <span>💾</span>
                <span>Save & Start Monitoring</span>
            </button>
        </div>

    </form>

</div>
@endsection

