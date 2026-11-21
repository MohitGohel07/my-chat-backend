@extends('clipper.layouts.app')

@section('content')
<div class="space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-zinc-800 pb-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Clips Library</h1>
            <p class="text-xs sm:text-sm text-zinc-400 mt-0.5">
                Review, approve, and manage rendered 9:16 vertical highlight shorts
            </p>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('clipper.clips.clearStatic') }}" method="POST" class="inline" onsubmit="return confirm('Remove all static sample placeholders? Only real stream cuts will be kept.');">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-xs font-semibold border border-zinc-700 transition">
                    🧹 Remove Static Sample Clips
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Tabs (Horizontal scroll on mobile) -->
    <div class="flex items-center gap-2 overflow-x-auto pb-2 border-b border-zinc-800 text-xs font-semibold no-scrollbar">
        <a href="{{ route('clipper.clips.index', ['status' => 'all']) }}"
           class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ $status === 'all' ? 'bg-zinc-800 text-white font-bold' : 'text-zinc-400 hover:text-white' }}">
            All Clips
        </a>
        <a href="{{ route('clipper.clips.index', ['status' => 'pending']) }}"
           class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ $status === 'pending' ? 'bg-amber-950 text-amber-300 font-bold border border-amber-800' : 'text-zinc-400 hover:text-white' }}">
            Pending Review
        </a>
        <a href="{{ route('clipper.clips.index', ['status' => 'ready']) }}"
           class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ $status === 'ready' ? 'bg-emerald-950 text-emerald-300 font-bold border border-emerald-800' : 'text-zinc-400 hover:text-white' }}">
            Ready to Upload
        </a>
        <a href="{{ route('clipper.clips.index', ['status' => 'uploaded']) }}"
           class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ $status === 'uploaded' ? 'bg-purple-950 text-purple-300 font-bold border border-purple-800' : 'text-zinc-400 hover:text-white' }}">
            Published on YouTube
        </a>
        <a href="{{ route('clipper.clips.index', ['status' => 'failed']) }}"
           class="px-3 py-1.5 rounded-lg whitespace-nowrap transition {{ $status === 'failed' ? 'bg-rose-950 text-rose-300 font-bold border border-rose-800' : 'text-zinc-400 hover:text-white' }}">
            Failed
        </a>
    </div>

    @if ($clips->isEmpty())
        <div class="gta-card text-center py-12">
            <div class="text-4xl mb-3">🎬</div>
            <h2 class="text-base font-bold text-white mb-1">No Clips in this View</h2>
            <p class="text-xs sm:text-sm text-zinc-400 max-w-sm mx-auto mb-4">
                No video highlights match the selected filter. As active streams detect action, clips will appear here automatically.
            </p>

            @php
                $activeStream = \App\Models\Clipper\ClipperStream::where('status', 'monitoring')->first() ?? \App\Models\Clipper\ClipperStream::first();
            @endphp
            @if ($activeStream)
                <form action="{{ route('clipper.streams.captureHighlight', $activeStream->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="gta-btn-primary text-xs shadow-lg shadow-pink-600/30">
                        ⚡ Trigger Highlight Capture Now ({{ $activeStream->name }})
                    </button>
                </form>
            @endif
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($clips as $clip)
                <div class="gta-card flex flex-col justify-between space-y-3">
                    <div class="aspect-[9/16] max-h-72 w-full bg-zinc-950 rounded-lg relative overflow-hidden border border-zinc-800">
                        @php
                            $rawPath = $clip->video_path ?: 'clips/sample_gtavi_short.mp4';
                            $videoUrl = str_starts_with($rawPath, 'http') ? $rawPath : asset('/' . ltrim($rawPath, '/'));
                        @endphp
                        <video src="{{ $videoUrl }}"
                               controls
                               playsinline
                               preload="metadata"
                               class="w-full h-full object-cover rounded-lg bg-black"></video>
                        <div class="absolute top-2 right-2 pointer-events-none">
                            <span class="badge badge-purple">{{ $clip->duration }}s</span>
                        </div>
                        <div class="absolute bottom-2 left-2 flex items-center gap-1.5 pointer-events-none">
                            <span class="badge badge-amber">Score: {{ $clip->score }}</span>
                            <span class="badge {{ $clip->approval_status === 'approved' ? 'badge-green' : 'badge-gray' }}">
                                {{ ucfirst($clip->approval_status) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-bold text-sm text-white line-clamp-1">{{ $clip->title }}</h3>
                        <p class="text-xs text-zinc-400 line-clamp-2 mt-1">{{ $clip->description }}</p>
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t border-zinc-800/80">
                        <a href="{{ route('clipper.clips.show', $clip->id) }}" class="text-xs text-pink-400 hover:text-pink-300 font-semibold">
                            Preview & Details &rarr;
                        </a>
                        @if ($clip->approval_status === 'pending')
                            <form action="{{ route('clipper.clips.approval', $clip->id) }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-semibold transition">
                                    Approve
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
