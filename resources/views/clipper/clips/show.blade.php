@extends('clipper.layouts.app')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <div class="border-b border-zinc-800 pb-4">
        <a href="{{ route('clipper.clips.index') }}" class="text-xs text-zinc-400 hover:text-white inline-flex items-center gap-1 mb-2">
            <span>&larr;</span> Back to Clips Library
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-xl sm:text-2xl font-bold text-white tracking-tight">{{ $clip->title }}</h1>
            <div class="flex items-center gap-2">
                <span class="badge {{ $clip->status === 'ready' ? 'badge-green' : 'badge-amber' }}">
                    {{ ucfirst($clip->status) }}
                </span>
                <span class="badge {{ $clip->approval_status === 'approved' ? 'badge-purple' : 'badge-gray' }}">
                    Approval: {{ ucfirst($clip->approval_status) }}
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">

        <!-- Left: 9:16 Video Player Card -->
        <div class="md:col-span-5 flex flex-col items-center">
            <div class="w-full max-w-[280px] aspect-[9/16] bg-zinc-950 rounded-xl border border-zinc-800 relative overflow-hidden flex flex-col items-center justify-center shadow-2xl">
                @php
                    $rawPath = $clip->video_path ?: 'clips/sample_gtavi_short.mp4';
                    $videoUrl = str_starts_with($rawPath, 'http') ? $rawPath : asset('/' . ltrim($rawPath, '/'));
                @endphp
                <video src="{{ $videoUrl }}"
                       controls
                       playsinline
                       autoplay
                       class="w-full h-full object-cover bg-black"></video>
                <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between text-[11px] bg-black/60 backdrop-blur-sm px-2.5 py-1.5 rounded-lg border border-zinc-700/50 pointer-events-none">
                    <span class="text-zinc-300">⏱️ {{ $clip->duration }}s</span>
                    <span class="text-pink-400 font-bold">Score: {{ $clip->score }}/100</span>
                </div>
            </div>
        </div>

        <!-- Right: Metadata & Action Controls -->
        <div class="md:col-span-7 space-y-4">

            <!-- Mobile Quick Actions -->
            <div class="gta-card space-y-3">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Moderation & Approval</h2>
                <div class="flex flex-wrap gap-2">
                    <form action="{{ route('clipper.clips.approval', $clip->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="upload_type" value="short">
                        <button type="submit" class="px-3.5 py-2 rounded-lg bg-pink-600 hover:bg-pink-500 text-white font-semibold text-xs transition flex items-center gap-1.5 shadow-lg shadow-pink-600/30">
                            <span>📱</span>
                            <span>Approve as YouTube Short</span>
                        </button>
                    </form>

                    <form action="{{ route('clipper.clips.approval', $clip->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="upload_type" value="standard">
                        <button type="submit" class="px-3.5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition flex items-center gap-1.5 shadow-lg shadow-indigo-600/30">
                            <span>🎬</span>
                            <span>Approve as Standard Video</span>
                        </button>
                    </form>

                    <form action="{{ route('clipper.clips.approval', $clip->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="px-3 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-300 font-semibold text-xs transition border border-zinc-700">
                            ❌ Reject / Archive
                        </button>
                    </form>

                    <form action="{{ route('clipper.clips.destroy', $clip->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this clip?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-3 py-2 rounded-lg bg-rose-950/60 hover:bg-rose-900 text-rose-300 font-semibold text-xs transition border border-rose-800">
                            🗑️ Delete
                        </button>
                    </form>
                </div>
            </div>

            <!-- Metadata Editor -->
            <form action="{{ route('clipper.clips.update', $clip->id) }}" method="POST" class="gta-card space-y-3">
                @csrf
                @method('PUT')
                <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Metadata (Title & Description)</h2>

                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1">YouTube Title</label>
                    <input type="text" name="title" value="{{ old('title', $clip->title) }}" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-pink-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1">Description & Hashtags</label>
                    <textarea name="description" rows="4" class="w-full bg-zinc-900 border border-zinc-800 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-pink-500">{{ old('description', $clip->description) }}</textarea>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-xs font-semibold border border-zinc-700">
                        Update Metadata
                    </button>
                </div>
            </form>

        </div>

    </div>

</div>
@endsection
