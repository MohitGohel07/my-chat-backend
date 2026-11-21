@extends('clipper.layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-800 pb-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Livestreams</h1>
            <p class="text-xs sm:text-sm text-zinc-400 mt-0.5">
                Manage live source feeds, clip settings, and monitoring state
            </p>
        </div>
        <div>
            <a href="{{ route('clipper.streams.create') }}" class="gta-btn-primary text-xs sm:text-sm w-full sm:w-auto">
                <span>➕</span>
                <span>Add Stream URL</span>
            </a>
        </div>
    </div>

    @if (empty($streams))
        <div class="gta-card text-center py-12">
            <div class="text-4xl mb-3">📡</div>
            <h2 class="text-base font-bold text-white mb-1">No Livestreams Configured</h2>
            <p class="text-xs sm:text-sm text-zinc-400 max-w-md mx-auto mb-6">
                Connect an authorized GTA VI livestream from YouTube or Twitch to start automatic highlight detection, 9:16 cropping, and captioning.
            </p>
            <a href="{{ route('clipper.streams.create') }}" class="gta-btn-primary text-sm">
                Add Your First Stream
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($streams as $stream)
                <div class="gta-card flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-white text-base">{{ $stream['name'] }}</span>
                            <span class="badge {{ $stream['status'] === 'monitoring' ? 'badge-green' : 'badge-gray' }}">
                                {{ ucfirst($stream['status']) }}
                            </span>
                        </div>
                        <div class="text-xs text-zinc-400 font-mono truncate max-w-md">{{ $stream['source_url'] }}</div>
                        <div class="flex flex-wrap gap-2 pt-1 text-[11px] text-zinc-400">
                            <span>⏱️ {{ $stream['clip_min_seconds'] }}s–{{ $stream['clip_max_seconds'] }}s</span>
                            <span>•</span>
                            <span>🎯 Sensitivity: {{ ucfirst($stream['highlight_sensitivity']) }}</span>
                            <span>•</span>
                            <span>📱 Vertical: {{ $stream['vertical_enabled'] ? 'Yes' : 'No' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('clipper.streams.show', $stream['id']) }}" class="px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold transition border border-zinc-700">
                            Controls
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection

