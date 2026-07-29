<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Commerce AI — Feed Validator')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 500: '#6366f1', 600: '#4f46e5' }
                    }
                }
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
        .glass { background: rgba(255,255,255,0.04); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.08); }
        .glass-dark { background: rgba(10,10,20,0.75); backdrop-filter: blur(14px); border: 1px solid rgba(99,102,241,0.12); }
        ::-webkit-scrollbar { width: 4px; } ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #374151; border-radius: 2px; }
    </style>
</head>
<body class="h-full bg-gray-950 text-gray-100 antialiased">

    {{-- Background --}}
    <div class="fixed inset-0 -z-10 bg-[radial-gradient(ellipse_at_top_left,_rgba(99,102,241,0.08)_0%,_transparent_60%),radial-gradient(ellipse_at_bottom_right,_rgba(168,85,247,0.06)_0%,_transparent_60%)] bg-gray-950"></div>

    <div class="flex min-h-full">

        {{-- Sidebar --}}
        <aside class="hidden lg:flex flex-col w-60 glass-dark shrink-0">
            <div class="p-5 border-b border-white/5">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-sm font-bold shadow-lg shadow-indigo-500/20">⚡</div>
                    <div>
                        <p class="text-xs font-bold text-white leading-none">Commerce AI</p>
                        <p class="text-[10px] text-indigo-400 mt-0.5">Feed Validator</p>
                    </div>
                </div>
            </div>

            <nav class="flex-1 p-3 space-y-0.5">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-all
                          {{ request()->routeIs('dashboard') ? 'bg-indigo-600/80 text-white font-medium shadow-sm' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    My Feeds
                </a>
            </nav>

            @auth
            <div class="p-3 border-t border-white/5">
                <div class="flex items-center gap-2.5 p-2 rounded-lg hover:bg-white/5 transition-all">
                    @if(auth()->user()->avatar)
                        <img src="{{ auth()->user()->avatar }}" class="w-7 h-7 rounded-full ring-1 ring-indigo-500/40" alt="">
                    @else
                        <div class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold">{{ substr(auth()->user()->name, 0, 1) }}</div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-white truncate">{{ auth()->user()->name }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Sign out" class="text-gray-600 hover:text-red-400 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
            @endauth
        </aside>

        {{-- Main --}}
        <div class="flex-1 flex flex-col min-h-screen">

            <header class="sticky top-0 z-20 glass-dark border-b border-white/5 px-6 py-3.5 flex items-center justify-between gap-4">
                <h1 class="text-sm font-semibold text-white">@yield('heading', 'Dashboard')</h1>
                <div class="flex items-center gap-2">@yield('header-actions')</div>
            </header>

            @if(session('success'))
            <div class="mx-6 mt-4 flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-sm">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="mx-6 mt-4 flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-500/10 border border-red-500/20 text-red-300 text-sm">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                {{ session('error') }}
            </div>
            @endif

            <main class="flex-1 px-6 py-6">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
