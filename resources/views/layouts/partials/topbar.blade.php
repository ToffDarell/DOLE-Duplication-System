{{-- Top Navigation Bar --}}
<header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-6 shadow-xs">
    <div class="flex items-center gap-3">
        <button @click="sidebarOpen = !sidebarOpen" class="rounded-lg p-1.5 text-slate-600 hover:bg-slate-100 lg:hidden">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div>
            <h2 class="text-base font-extrabold text-slate-900 tracking-tight">@yield('page-title', 'Dashboard')</h2>
            <p class="text-xs text-slate-600 font-medium">@yield('page-subtitle', '')</p>
        </div>
    </div>

    <div class="flex items-center gap-4" x-data="{ profileOpen: false }">
        {{-- Pending duplicates beacon button --}}
        @hasanyrole('Admin|Validator')
        @php $pendingCount = \App\Models\DuplicateFlag::where('status', 'pending')->count(); @endphp
        @if($pendingCount > 0)
            <a href="{{ route('duplicates.index') }}"
               class="relative flex items-center gap-2 rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-900 shadow-2xs hover:bg-amber-100 transition">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-600"></span>
                </span>
                <span>{{ $pendingCount }} Flagged Duplicate{{ $pendingCount > 1 ? 's' : '' }}</span>
            </a>
        @endif
        @endhasanyrole

        {{-- User Profile Dropdown --}}
        <div class="relative">
            <button @click="profileOpen = !profileOpen"
                    class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-semibold transition hover:bg-slate-100">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-800 text-xs font-bold text-white shadow-2xs">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-xs font-bold leading-tight text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] font-extrabold text-blue-800 uppercase tracking-wide">{{ auth()->user()->roles->first()?->name ?? 'User' }}</p>
                </div>
                <svg class="h-4 w-4 text-slate-500 transition-transform duration-200" :class="profileOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="profileOpen" @click.away="profileOpen = false"
                 x-transition:enter="transition ease-out duration-150 transform"
                 x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100 transform"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                 class="absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg z-50"
                 style="display: none;">
                <div class="px-3 py-2 border-b border-slate-100 mb-1">
                    <p class="text-xs font-bold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-slate-500 truncate">{{ auth()->user()->email }}</p>
                </div>

                <a href="{{ route('password.change') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                    Change Password
                </a>

                <hr class="my-1 border-slate-100">

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                        <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
