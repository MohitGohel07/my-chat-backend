@extends('clipper.layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-800 pb-4">
        <div>
            <a href="{{ route('clipper.streams.index') }}" class="text-xs text-zinc-400 hover:text-white inline-flex items-center gap-1 mb-2">
                <span>&larr;</span> Back to Streams
            </a>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                <span>{{ $stream->name }}</span>
                <span class="badge {{ $stream->status === 'monitoring' ? 'badge-green' : 'badge-gray' }}">
                    {{ ucfirst($stream->status) }}
                </span>
                <span class="badge badge-purple uppercase text-[10px]">{{ $stream->source_platform }}</span>
            </h1>
            <p class="text-xs text-zinc-400 font-mono mt-0.5 truncate max-w-xl">
                {{ $stream->source_url }}
            </p>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            <form action="{{ route('clipper.streams.captureHighlight', $stream->id) }}" method="POST">
                @csrf
                <button type="submit" class="gta-btn-primary text-xs shadow-lg shadow-pink-600/30">
                    <span>⚡</span>
                    <span>Capture Highlight Now</span>
                </button>
            </form>

            <form action="{{ route('clipper.streams.toggle', $stream->id) }}" method="POST">
                @csrf
                @if ($stream->status === 'monitoring')
                    <input type="hidden" name="action" value="stop">
                    <button type="submit" class="px-3.5 py-2 rounded-lg bg-amber-950 hover:bg-amber-900 text-amber-300 font-semibold text-xs transition border border-amber-800">
                        ⏹️ Pause Monitoring
                    </button>
                @else
                    <input type="hidden" name="action" value="start">
                    <button type="submit" class="px-3.5 py-2 rounded-lg bg-emerald-950 hover:bg-emerald-900 text-emerald-300 font-semibold text-xs transition border border-emerald-800">
                        ▶️ Start Monitoring
                    </button>
                @endif
            </form>

            <a href="{{ route('clipper.streams.logs', $stream->id) }}" class="px-3 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold border border-zinc-700 transition">
                📜 Stream Logs
            </a>

            <form action="{{ route('clipper.streams.destroy', $stream->id) }}" method="POST" onsubmit="return confirm('Remove this stream configuration?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3 py-2 rounded-lg bg-rose-950/80 hover:bg-rose-900 text-rose-300 text-xs font-semibold border border-rose-800 transition">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Configuration Overview Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="gta-card space-y-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Clipping Parameters</h2>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Duration Limits:</span>
                    <span class="font-semibold text-white">{{ $stream->clip_min_seconds }}s – {{ $stream->clip_max_seconds }}s</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Pre / Post Padding:</span>
                    <span class="font-semibold text-white">+{{ $stream->pre_roll_seconds }}s / +{{ $stream->post_roll_seconds }}s</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Highlight Sensitivity:</span>
                    <span class="font-semibold text-white capitalize">{{ $stream->highlight_sensitivity }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Vertical 9:16 Reframe:</span>
                    <span class="font-semibold text-white">{{ $stream->vertical_enabled ? 'Enabled' : 'Disabled' }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-zinc-400">Whisper Captions:</span>
                    <span class="font-semibold text-white">{{ $stream->captions_enabled ? 'Enabled' : 'Disabled' }}</span>
                </div>
            </div>
        </div>

        <div class="gta-card space-y-3">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Publication Rules</h2>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Require Manual Approval:</span>
                    <span class="font-semibold text-white">{{ $stream->require_approval ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Auto-Upload to YouTube:</span>
                    <span class="font-semibold text-white">{{ $stream->auto_upload_enabled ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Upload Privacy:</span>
                    <span class="font-semibold text-white capitalize">{{ $stream->upload_privacy }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-zinc-800/60">
                    <span class="text-zinc-400">Max Daily Clips:</span>
                    <span class="font-semibold text-white">{{ $stream->max_clips_per_day }}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-zinc-400">Created:</span>
                    <span class="font-semibold text-white">{{ $stream->created_at?->toFormattedDateString() }}</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
