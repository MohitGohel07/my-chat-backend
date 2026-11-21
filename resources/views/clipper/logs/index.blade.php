@extends('clipper.layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-800 pb-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">System & Stream Logs</h1>
            <p class="text-xs sm:text-sm text-zinc-400 mt-0.5">
                Audit trail for capture sessions, highlight detectors, and upload events
            </p>
        </div>
        @if ($filterStreamId)
            <div>
                <a href="{{ route('clipper.logs.index') }}" class="text-xs text-zinc-400 hover:text-white underline">
                    Clear Stream Filter &times;
                </a>
            </div>
        @endif
    </div>

    @if ($logs->isEmpty())
        <div class="gta-card text-center py-12">
            <div class="text-4xl mb-3">📜</div>
            <h2 class="text-base font-bold text-white mb-1">No Log Entries Recorded</h2>
            <p class="text-xs sm:text-sm text-zinc-400 max-w-sm mx-auto">
                {{ $filterStreamId ? "No events found for stream ID [{$filterStreamId}]." : 'System and capture events will appear here once monitoring begins.' }}
            </p>
        </div>
    @else
        <div class="gta-card overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-zinc-800 text-zinc-400 uppercase font-semibold">
                        <th class="py-2.5 px-3">Timestamp</th>
                        <th class="py-2.5 px-3">Level</th>
                        <th class="py-2.5 px-3">Event / Message</th>
                        <th class="py-2.5 px-3">Stream</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/60 font-mono">
                    @foreach ($logs as $log)
                        <tr>
                            <td class="py-2.5 px-3 text-zinc-400 whitespace-nowrap text-[11px]">
                                {{ $log->created_at->format('M d, H:i:s') }}
                            </td>
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="badge {{ $log->level === 'error' ? 'badge-rose' : ($log->level === 'success' ? 'badge-green' : ($log->level === 'warning' ? 'badge-amber' : 'badge-purple')) }}">
                                    {{ strtoupper($log->level) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-3 text-zinc-200">{{ $log->message }}</td>
                            <td class="py-2.5 px-3 text-zinc-400 whitespace-nowrap">{{ $log->stream?->name ?? 'System' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
