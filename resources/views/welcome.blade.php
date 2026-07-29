<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commerce AI — Feed Validator</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .gradient-text { background: linear-gradient(135deg, #818cf8, #c084fc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="min-h-screen bg-gray-950 text-gray-100 flex items-center justify-center p-4">

    <div class="fixed inset-0 -z-10 bg-[radial-gradient(ellipse_at_center,_rgba(99,102,241,0.08)_0%,_transparent_70%)]"></div>

    <div class="max-w-lg w-full text-center space-y-8">

        <div class="space-y-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-3xl mx-auto shadow-2xl shadow-indigo-500/20">
                ⚡
            </div>
            <h1 class="text-4xl font-bold tracking-tight">
                <span class="gradient-text">Commerce AI</span>
            </h1>
            <p class="text-lg text-gray-400 font-light">Product Feed Validator</p>
            <p class="text-sm text-gray-600 max-w-sm mx-auto leading-relaxed">
                Validate Google Merchant Center feeds, flag errors automatically,
                and fix them instantly with Gemini AI.
            </p>
        </div>

        <div class="flex flex-col gap-3 items-center">
            <a href="{{ route('auth.github') }}"
               class="flex items-center gap-3 px-6 py-3 bg-white text-gray-900 font-medium rounded-xl hover:bg-gray-100 transition-all shadow-lg w-full max-w-xs justify-center">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 0C5.374 0 0 5.373 0 12c0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23A11.509 11.509 0 0112 5.803c1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576C20.566 21.797 24 17.3 24 12c0-6.627-5.373-12-12-12z"/>
                </svg>
                Sign in with GitHub
            </a>
        </div>

        <div class="grid grid-cols-3 gap-4 pt-4">
            @foreach([
                ['icon' => '📋', 'label' => 'CSV & XML', 'desc' => 'Parse any feed format'],
                ['icon' => '🔍', 'label' => 'Validation', 'desc' => 'GMC schema rules'],
                ['icon' => '✨', 'label' => 'AI Fixes', 'desc' => 'Powered by Gemini'],
            ] as $f)
            <div class="text-center space-y-1 p-3 rounded-xl bg-white/[0.03] border border-white/5">
                <div class="text-xl">{{ $f['icon'] }}</div>
                <p class="text-xs font-semibold text-white">{{ $f['label'] }}</p>
                <p class="text-[10px] text-gray-600">{{ $f['desc'] }}</p>
            </div>
            @endforeach
        </div>

    </div>
</body>
</html>
