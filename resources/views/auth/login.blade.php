<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In — DOLE Bukidnon Duplicate Detection System</title>
    
    {{-- Official DOLE Favicon / Web Icon --}}
    <link rel="icon" type="image/png" href="{{ asset('logo dole.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo dole.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col justify-center bg-slate-50 font-sans text-slate-900 antialiased selection:bg-blue-800 selection:text-white">
    <div class="relative z-10 sm:mx-auto sm:w-full sm:max-w-md px-4 py-8">
        {{-- Official DOLE Logo Header --}}
        <div class="text-center">
            <div class="inline-block rounded-2xl bg-white p-2.5 shadow-xs border border-slate-200/80">
                <img src="{{ asset('logo dole.png') }}" alt="DOLE Official Logo" class="h-16 w-16 object-contain rounded-xl bg-white">
            </div>
            <h2 class="mt-4 text-2xl font-black tracking-tight text-slate-900">DOLE Bukidnon</h2>
            <p class="mt-1 text-xs font-bold text-blue-800">Duplicate Detection System</p>
        </div>

        {{-- Login Card --}}
        <div class="mt-6 rounded-2xl border border-slate-200/80 bg-white p-8 shadow-sm">
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

            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           placeholder="staff@dole-bukidnon.gov.ph"
                           class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-800/20">
                </div>

                <div x-data="{ showPass: false }">
                    <label class="mb-1.5 block text-xs font-bold text-slate-700">Password</label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" name="password" required placeholder="••••••••"
                               class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 pr-11 text-sm text-slate-900 placeholder-slate-400 transition focus:border-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-800/20">
                        <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-blue-800 focus:outline-none transition cursor-pointer" tabindex="-1" aria-label="Toggle password visibility">
                            {{-- Eye icon (Hidden state) --}}
                            <svg x-show="!showPass" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{-- Eye slash icon (Visible state) --}}
                            <svg x-show="showPass" x-cloak class="h-5 w-5 text-blue-800" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs pt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-semibold select-none">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 bg-slate-50 text-blue-800 focus:ring-blue-800/20">
                        Remember me
                    </label>
                </div>

                <button type="submit"
                        class="w-full rounded-xl bg-blue-800 hover:bg-blue-900 px-4 py-2.5 text-sm font-bold text-white shadow-xs transition cursor-pointer">
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
