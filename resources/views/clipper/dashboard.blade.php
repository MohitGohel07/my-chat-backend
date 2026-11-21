@extends('clipper.layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Hero Header / Mobile Welcome -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-800 pb-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <span>Dashboard Summary</span>
            </h1>
            <p class="text-xs sm:text-sm text-zinc-400 mt-0.5">
                Real-time GTA VI highlight capture & publication status
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('clipper.streams.create') }}" class="gta-btn-primary text-xs sm:text-sm">
                <span>➕</span>
                <span>Add Livestream</span>
            </a>
        </div>
    </div>

    <!-- Worker Heartbeat Banner -->
    <div class="gta-card flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-l-4 {{ $metrics['worker_heartbeat']['status'] === 'online' ? 'border-l-emerald-500' : 'border-l-amber-500' }}">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex items-center justify-center {{ $metrics['worker_heartbeat']['status'] === 'online' ? 'bg-emerald-950/80 text-emerald-400' : 'bg-amber-950/80 text-amber-400' }}">
                <span class="text-lg">⚡</span>
            </div>
            <div>
                <div class="text-xs text-zinc-400 uppercase tracking-wider font-semibold">Video Pipeline Worker</div>
                <div class="text-sm font-bold text-white flex items-center gap-2">
                    <span class="capitalize">{{ $metrics['worker_heartbeat']['status'] }}</span>
                    <span class="text-xs font-normal text-zinc-400 font-mono">({{ $metrics['worker_heartbeat']['endpoint'] }})</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('clipper.health.index') }}" class="text-xs text-pink-400 hover:text-pink-300 font-semibold underline underline-offset-4">
                View Diagnostics &rarr;
            </a>
        </div>
    </div>

    <!-- Dashboard Metrics Grid -->
    <div>
        <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400 mb-3">Live Pipeline Metrics</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">

            <!-- Active Monitoring -->
            <div class="gta-card">
                <div class="stat-label">Active Monitoring</div>
                <div class="flex items-baseline justify-between">
                    <div class="stat-value text-emerald-400">{{ $metrics['active_monitoring'] }}</div>
                    <span class="badge badge-green">Live</span>
                </div>
            </div>

            <!-- Queued -->
            <div class="gta-card">
                <div class="stat-label">Streams Queued</div>
                <div class="flex items-baseline justify-between">
                    <div class="stat-value">{{ $metrics['queued'] }}</div>
                    <span class="badge badge-gray">Pending</span>
                </div>
            </div>

            <!-- Capturing -->
            <div class="gta-card">
                <div class="stat-label">Capturing Footage</div>
                <div class="flex items-baseline justify-between">
                    <div class="stat-value text-pink-400">{{ $metrics['capturing'] }}</div>
                    <span class="badge badge-rose">Rolling Buffer</span>
                </div>
            </div>

            <!-- Processing / Transcoding -->
            <div class="gta-card">
                <div class="stat-label">Processing / FFmpeg</div>
                <div class="flex items-baseline justify-between">
                    <div class="stat-value text-purple-400">{{ $metrics['processing'] }}</div>
                    <span class="badge badge-purple">9:16 Render</span>
                </div>
            </div>

            <!-- Clips Generated Today -->
            <div class="gta-card">
                <div class="stat-label">Clips Today</div>
                <div class="flex items-baseline justify-between">
                    <div class="stat-value text-white">{{ $metrics['clips_today'] }}</div>
                    <span class="text-xs text-zinc-400">Target: 5/day</span>
                </div>
            </div>

            <!-- Uploads Pending -->
            <div class="gta-card">
                <div class="stat-label">Uploads Pending</div>
                <div class="flex items-baseline justify-between">
                    <div class="stat-value text-amber-400">{{ $metrics['uploads_pending'] }}</div>
                    <span class="badge badge-amber">Awaiting Approval</span>
                </div>
            </div>

            <!-- Uploads Completed -->
            <div class="gta-card">
                <div class="stat-label">Uploads Completed</div>
                <div class="flex items-baseline justify-between">
                    <div class="stat-value text-emerald-400">{{ $metrics['uploads_completed'] }}</div>
                    <span class="badge badge-green">Published</span>
                </div>
            </div>

            <!-- Failed Tasks -->
            <div class="gta-card">
                <div class="stat-label">Failed Tasks</div>
                <div class="flex items-baseline justify-between">
                    <div class="stat-value {{ $metrics['failed_tasks'] > 0 ? 'text-rose-400' : 'text-zinc-500' }}">
                        {{ $metrics['failed_tasks'] }}
                    </div>
                    @if ($metrics['failed_tasks'] > 0)
                        <span class="badge badge-rose">Action Needed</span>
                    @else
                        <span class="badge badge-gray">Clean</span>
                    @endif
                </div>
            </div>

        </div>
    </div>

    <!-- Quick Navigation / Feature Shortcuts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Streams Panel -->
        <div class="gta-card space-y-3">
            <div class="flex items-center justify-between">
                <div class="font-bold text-white flex items-center gap-2">
                    <span>📡 Monitored Streams</span>
                </div>
                <a href="{{ route('clipper.streams.index') }}" class="text-xs text-pink-400 hover:text-pink-300 font-medium">View all &rarr;</a>
            </div>

            @if (empty($recentStreams))
                <div class="text-center py-8 border border-dashed border-zinc-800 rounded-lg">
                    <div class="text-3xl mb-2">🎮</div>
                    <div class="text-sm font-semibold text-zinc-300">No active streams monitored</div>
                    <p class="text-xs text-zinc-500 max-w-xs mx-auto mt-1 mb-3">
                        Paste an authorized YouTube or Twitch livestream URL to begin capturing highlights.
                    </p>
                    <a href="{{ route('clipper.streams.create') }}" class="gta-btn-primary text-xs">
                        Configure First Stream
                    </a>
                </div>
            @endif
        </div>

        <!-- Recent Clips Panel -->
        <div class="gta-card space-y-3">
            <div class="flex items-center justify-between">
                <div class="font-bold text-white flex items-center gap-2">
                    <span>🎬 Recent Highlight Clips</span>
                </div>
                <a href="{{ route('clipper.clips.index') }}" class="text-xs text-pink-400 hover:text-pink-300 font-medium">View library &rarr;</a>
            </div>

            @if (empty($recentClips))
                <div class="text-center py-8 border border-dashed border-zinc-800 rounded-lg">
                    <div class="text-3xl mb-2">🎞️</div>
                    <div class="text-sm font-semibold text-zinc-300">Clip library is empty</div>
                    <p class="text-xs text-zinc-500 max-w-xs mx-auto mt-1">
                        When the detector identifies action moments, vertical 9:16 clips will appear here for review.
                    </p>
                </div>
            @endif
        </div>
    </div>

</div>
@endsection

