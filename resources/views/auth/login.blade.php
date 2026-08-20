<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — DOLE Bukidnon Duplicate Detection System</title>
    
    {{-- Official DOLE Favicon / Web Icon --}}
    <link rel="icon" type="image/png" href="{{ asset('dole-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('dole-logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col justify-center bg-gradient-to-br from-slate-100 via-blue-50/50 to-slate-200 font-sans text-slate-900 antialiased selection:bg-blue-700 selection:text-white relative overflow-hidden">
    {{-- Soft DOLE Ambient Glow Background Elements --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full bg-blue-600/10 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-amber-500/15 blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 h-[500px] w-[500px] rounded-full bg-blue-400/5 blur-3xl"></div>
    </div>

    <div class="relative z-10 sm:mx-auto sm:w-full sm:max-w-md px-4 py-8">
        {{-- Official DOLE Logo Header --}}
        <div class="text-center">
            <div class="inline-block rounded-2xl bg-white p-2.5 shadow-lg border border-slate-200/80 transition-transform duration-300 hover:scale-105">
                <img src="{{ asset('dole-logo.png') }}" alt="DOLE Official Logo" class="h-20 w-20 object-contain rounded-xl bg-white">
            </div>
            <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-900">DOLE Bukidnon</h2>
            <p class="mt-1 text-xs font-bold uppercase tracking-wider text-blue-700">Duplicate Detection System</p>
        </div>

        {{-- Login Card --}}
        <div class="mt-7 rounded-3xl border border-slate-200/80 bg-white/95 p-8 shadow-xl backdrop-blur-md">
            @if(session('error'))
                <div class="mb-5 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs font-semibold text-red-800 shadow-2xs">
                    <svg class="h-5 w-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if(session('status'))
                <div class="mb-5 flex items-center gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs font-semibold text-emerald-800 shadow-2xs">
                    <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="staff@dole-bukidnon.gov.ph"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-700/20">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Password</label>
                    <input type="password" name="password" required placeholder="••••••••"
                           class="w-full rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-700/20">
                </div>

                <div class="flex items-center justify-between text-xs pt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-semibold select-none">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 bg-slate-50 text-blue-700 focus:ring-blue-700/20">
                        Remember me
                    </label>
                </div>

                <button type="submit"
                        class="w-full rounded-xl bg-gradient-to-r from-blue-700 via-blue-800 to-blue-900 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-blue-700/25 transition-all duration-200 hover:shadow-xl hover:from-blue-800 hover:to-blue-950 hover:scale-[1.01] active:scale-[0.99]">
                    Sign In to Portal
                </button>
            </form>

            <div class="mt-6 border-t border-slate-100 pt-4 text-center text-[11px] font-semibold text-slate-500">
                Department of Labor and Employment — Bukidnon Field Office
            </div>
        </div>
    </div>
</body>
</html>
