@extends('clipper.layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-800 pb-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Upload Queue</h1>
            <p class="text-xs sm:text-sm text-zinc-400 mt-0.5">
                Monitor YouTube Data API v3 upload jobs, rate limits, and publishing status
            </p>
        </div>
    </div>

    <!-- Daily Quota & Quota Guardrails Card -->
    <div class="gta-card flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="text-xs uppercase tracking-wider font-semibold text-zinc-400">Daily Channel Upload Quota</div>
            <div class="text-lg font-bold text-white">
                <span class="text-pink-400">{{ $dailyQuota['used'] }}</span> / {{ $dailyQuota['limit'] }} Shorts uploaded today
            </div>
            <p class="text-[11px] text-zinc-500">
                Resets daily at {{ $dailyQuota['reset_time'] }}. YouTube Data API v3 enforces 10,000 units/day (~6 video uploads).
            </p>
        </div>
        <div class="w-full sm:w-48">
            <div class="w-full bg-zinc-900 h-2.5 rounded-full overflow-hidden border border-zinc-800">
                <div class="bg-gradient-to-r from-pink-500 to-purple-500 h-full rounded-full" style="width: {{ ($dailyQuota['used'] / max(1, $dailyQuota['limit'])) * 100 }}%"></div>
            </div>
        </div>
    </div>

    @if (empty($queue))
        <div class="gta-card text-center py-12">
            <div class="text-4xl mb-3">🚀</div>
            <h2 class="text-base font-bold text-white mb-1">Upload Queue is Empty</h2>
            <p class="text-xs sm:text-sm text-zinc-400 max-w-sm mx-auto">
                No videos are currently waiting for upload. Approved clips with auto-upload enabled will be dispatched here according to the schedule.
            </p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($queue as $item)
                <div class="gta-card flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-white text-sm">{{ $item['title'] }}</span>
                            <span class="badge badge-purple">{{ ucfirst($item['status']) }}</span>
                        </div>
                        <div class="text-xs text-zinc-400">
                            Privacy: <span class="capitalize text-zinc-300">{{ $item['privacy'] }}</span> •
                            Attempts: {{ $item['attempts'] }}
                        </div>
                    </div>
                    <div>
                        <form action="{{ route('clipper.uploads.retry', $item['id']) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold border border-zinc-700">
                                Retry Upload
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection

