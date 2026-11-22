<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'GTA VI Auto Clipper' }} — Antigravity Cloud</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --bg-base: #09090b;
            --bg-card: #141417;
            --bg-card-hover: #1c1c21;
            --border-color: #27272a;
            --accent-pink: #ec4899;
            --accent-purple: #8b5cf6;
            --text-main: #f4f4f5;
            --text-muted: #a1a1aa;
        }
        body {
            font-family: 'Instrument Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }
        .gta-gradient-text {
            background: linear-gradient(135deg, #f43f5e 0%, #ec4899 50%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .gta-btn-primary {
            background: linear-gradient(135deg, #e11d48 0%, #d946ef 100%);
            color: #ffffff;
            font-weight: 600;
            border-radius: 0.5rem;
            padding: 0.625rem 1.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: opacity 0.2s;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .gta-btn-primary:hover {
            opacity: 0.9;
        }
        .gta-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.25rem;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-green { background-color: #064e3b; color: #34d399; }
        .badge-amber { background-color: #78350f; color: #fbbf24; }
        .badge-rose { background-color: #881337; color: #fb7185; }
        .badge-purple { background-color: #4c1d95; color: #c084fc; }
        .badge-gray { background-color: #27272a; color: #a1a1aa; }
        .stat-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #ffffff;
        }
    </style>
</head>
<body class="min-h-screen pb-36 md:pb-12 flex flex-col">

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-40 w-full border-b border-zinc-800 bg-zinc-950/90 backdrop-blur-md px-4 py-3">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('clipper.dashboard') }}" class="flex items-center gap-2 text-decoration-none">
                    <span class="text-xl">🌴</span>
                    <div>
                        <div class="font-bold tracking-tight text-white flex items-center gap-1.5 text-base sm:text-lg">
                            <span class="gta-gradient-text">GTA VI</span> Auto Clipper
                        </div>
                        <div class="text-[10px] text-zinc-400 font-mono tracking-wider uppercase">Cloud Orchestrator</div>
                    </div>
                </a>
            </div>

            <!-- Header Quick Info & User -->
            <div class="flex items-center gap-3">
                <a href="{{ route('clipper.health.index') }}" class="hidden sm:flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-medium border border-zinc-800 bg-zinc-900/80 text-zinc-300 hover:border-zinc-700 transition">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>System Online</span>
                </a>

                @auth
                    <div class="flex items-center gap-2">
                        <span class="hidden md:inline-block text-xs text-zinc-400 font-medium">{{ Auth::user()->name }}</span>
                        <form action="{{ route('clipper.logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-2.5 py-1 rounded-md text-xs font-semibold text-zinc-300 hover:text-white bg-zinc-800 hover:bg-zinc-700 transition border border-zinc-700">
                                Sign Out
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <div class="max-w-7xl mx-auto w-full px-4 pt-4">
        @if (session('status'))
            <div class="p-3 mb-4 rounded-lg bg-emerald-950/80 border border-emerald-800 text-emerald-200 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span>✅</span>
                    <span>{{ session('status') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white text-xs">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="p-3 mb-4 rounded-lg bg-rose-950/80 border border-rose-800 text-rose-200 text-sm flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-white text-xs">&times;</button>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-3 mb-4 rounded-lg bg-rose-950/80 border border-rose-800 text-rose-200 text-sm">
                <div class="font-semibold mb-1">Please correct the following issues:</div>
                <ul class="list-disc pl-5 space-y-0.5 text-xs">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Main Content Container with Desktop Sidebar / Mobile Views -->
    <div class="max-w-7xl mx-auto w-full px-4 py-2 flex-1 flex flex-col md:flex-row gap-6">
        <!-- Desktop Sidebar Navigation -->
        <aside class="hidden md:block w-56 shrink-0">
            <nav class="sticky top-20 space-y-1 text-sm font-medium">
                <a href="{{ route('clipper.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition {{ request()->routeIs('clipper.dashboard') ? 'bg-zinc-800 text-white font-semibold shadow-inner' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900' }}">
                    <span class="text-base">📊</span> Dashboard
                </a>
                <a href="{{ route('clipper.streams.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition {{ request()->routeIs('clipper.streams.*') ? 'bg-zinc-800 text-white font-semibold shadow-inner' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900' }}">
                    <span class="text-base">📡</span> Streams
                </a>
                <a href="{{ route('clipper.clips.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition {{ request()->routeIs('clipper.clips.*') ? 'bg-zinc-800 text-white font-semibold shadow-inner' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900' }}">
                    <span class="text-base">🎬</span> Clips Library
                </a>
                <a href="{{ route('clipper.uploads.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition {{ request()->routeIs('clipper.uploads.*') ? 'bg-zinc-800 text-white font-semibold shadow-inner' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900' }}">
                    <span class="text-base">🚀</span> Upload Queue
                </a>
                <a href="{{ route('clipper.youtube.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition {{ request()->routeIs('clipper.youtube.*') ? 'bg-zinc-800 text-white font-semibold shadow-inner' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900' }}">
                    <span class="text-base">🔴</span> YouTube Connect
                </a>
                <a href="{{ route('clipper.health.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition {{ request()->routeIs('clipper.health.*') ? 'bg-zinc-800 text-white font-semibold shadow-inner' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900' }}">
                    <span class="text-base">🩺</span> System Health
                </a>
                <a href="{{ route('clipper.logs.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg transition {{ request()->routeIs('clipper.logs.*') ? 'bg-zinc-800 text-white font-semibold shadow-inner' : 'text-zinc-400 hover:text-zinc-200 hover:bg-zinc-900' }}">
                    <span class="text-base">📜</span> System Logs
                </a>
            </nav>
        </aside>

        <!-- Page Body -->
        <main class="flex-1 w-full min-w-0 pb-28 md:pb-8">
            @yield('content')
        </main>
    </div>

    <!-- Mobile Bottom Navigation Bar (Fixed for touch control on mobile devices) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-zinc-950/95 border-t border-zinc-800 backdrop-blur-md px-2 py-1.5 flex items-center justify-around shadow-2xl" style="padding-bottom: max(0.375rem, env(safe-area-inset-bottom));">
        <a href="{{ route('clipper.dashboard') }}" class="flex flex-col items-center justify-center py-1 px-2 rounded-lg text-center {{ request()->routeIs('clipper.dashboard') ? 'text-pink-400 font-bold' : 'text-zinc-400' }}">
            <span class="text-lg">📊</span>
            <span class="text-[10px] mt-0.5">Overview</span>
        </a>
        <a href="{{ route('clipper.streams.index') }}" class="flex flex-col items-center justify-center py-1 px-2 rounded-lg text-center {{ request()->routeIs('clipper.streams.*') ? 'text-pink-400 font-bold' : 'text-zinc-400' }}">
            <span class="text-lg">📡</span>
            <span class="text-[10px] mt-0.5">Streams</span>
        </a>
        <a href="{{ route('clipper.clips.index') }}" class="flex flex-col items-center justify-center py-1 px-2 rounded-lg text-center {{ request()->routeIs('clipper.clips.*') ? 'text-pink-400 font-bold' : 'text-zinc-400' }}">
            <span class="text-lg">🎬</span>
            <span class="text-[10px] mt-0.5">Clips</span>
        </a>
        <a href="{{ route('clipper.uploads.index') }}" class="flex flex-col items-center justify-center py-1 px-2 rounded-lg text-center {{ request()->routeIs('clipper.uploads.*') ? 'text-pink-400 font-bold' : 'text-zinc-400' }}">
            <span class="text-lg">🚀</span>
            <span class="text-[10px] mt-0.5">Queue</span>
        </a>
        <a href="{{ route('clipper.health.index') }}" class="flex flex-col items-center justify-center py-1 px-2 rounded-lg text-center {{ request()->routeIs('clipper.health.*', 'clipper.youtube.*', 'clipper.logs.*') ? 'text-pink-400 font-bold' : 'text-zinc-400' }}">
            <span class="text-lg">⚙️</span>
            <span class="text-[10px] mt-0.5">Settings</span>
        </a>
    </nav>

    @stack('scripts')
</body>
</html>

