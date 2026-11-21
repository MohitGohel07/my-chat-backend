@extends('clipper.layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-800 pb-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">System Health & Telemetry</h1>
            <p class="text-xs sm:text-sm text-zinc-400 mt-0.5">
                Diagnostics for Laravel Cloud scheduler, queue backlog, and the external video worker
            </p>
        </div>
        <div>
            <a href="{{ route('clipper.health.index') }}" class="px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold border border-zinc-700 transition">
                🔄 Refresh Telemetry
            </a>
        </div>
    </div>

    <!-- Health Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- External Worker Health -->
        <div class="gta-card space-y-3 border-l-4 border-l-pink-500">
            <div class="flex items-center justify-between">
                <div class="font-bold text-white text-sm flex items-center gap-2">
                    <span>⚡ Dedicated Video Worker</span>
                </div>
                <span class="badge {{ $health['worker']['status'] === 'online' ? 'badge-green' : 'badge-amber' }}">
                    {{ ucfirst(str_replace('_', ' ', $health['worker']['status'])) }}
                </span>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Endpoint:</span>
                    <span class="font-mono text-zinc-300">{{ $health['worker']['endpoint'] }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Last Heartbeat:</span>
                    <span class="text-zinc-300">{{ $health['worker']['last_heartbeat'] ?? 'No heartbeat received yet' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Worker CPU:</span>
                    <span class="text-zinc-300">{{ $health['worker']['cpu_usage'] }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Worker Memory:</span>
                    <span class="text-zinc-300">{{ $health['worker']['memory_usage'] }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-zinc-400">Available Temp Disk:</span>
                    <span class="text-zinc-300">{{ $health['worker']['disk_free'] }}</span>
                </div>
            </div>
        </div>

        <!-- Laravel Cloud Queue & Scheduler Health -->
        <div class="gta-card space-y-3 border-l-4 border-l-purple-500">
            <div class="flex items-center justify-between">
                <div class="font-bold text-white text-sm flex items-center gap-2">
                    <span>☁️ Laravel Cloud Orchestration</span>
                </div>
                <span class="badge badge-green">Healthy</span>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Queue Driver:</span>
                    <span class="font-mono text-zinc-300">{{ $health['queue']['driver'] }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Pending Jobs in Queue:</span>
                    <span class="font-bold text-white">{{ $health['queue']['pending_jobs'] }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Failed Jobs:</span>
                    <span class="{{ $health['queue']['failed_jobs'] > 0 ? 'text-rose-400 font-bold' : 'text-zinc-300' }}">{{ $health['queue']['failed_jobs'] }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Laravel Scheduler:</span>
                    <span class="text-zinc-300">{{ $health['scheduler']['status'] }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-zinc-400">Persistent Media Disk:</span>
                    <span class="font-mono text-zinc-300">{{ $health['storage']['disk'] }} ({{ $health['storage']['status'] }})</span>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

