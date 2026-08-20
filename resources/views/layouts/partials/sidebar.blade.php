{{-- Sidebar Navigation --}}
<aside class="fixed inset-y-0 left-0 z-30 flex flex-col border-r border-slate-200 bg-white transition-all duration-200 shadow-sm"
       :class="sidebarOpen ? 'w-64' : 'w-20'">

    {{-- Header / Logo --}}
    <div class="flex h-16 items-center border-b border-slate-200 px-4">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 overflow-hidden">
            <div class="rounded-lg bg-white p-1 border border-slate-200 shadow-2xs shrink-0 flex items-center justify-center">
                <img src="{{ asset('dole-logo.png') }}" alt="DOLE Logo" class="h-8 w-8 object-contain bg-white">
            </div>
            <div x-show="sidebarOpen" class="transition-opacity">
                <h1 class="text-sm font-extrabold leading-tight text-slate-900 tracking-tight">DOLE Bukidnon</h1>
                <p class="text-[10px] font-extrabold text-blue-700 uppercase tracking-wide">Duplicate Detection</p>
            </div>
        </a>
    </div>

    {{-- Nav Links --}}
    <nav class="flex-1 overflow-y-auto p-3 space-y-6">
        <div>
            <p x-show="sidebarOpen" class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Main Menu</p>
            <ul class="space-y-1 font-semibold text-xs">
                <li>
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ request()->routeIs('dashboard') ? 'bg-blue-800 text-white font-bold shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                        <span x-show="sidebarOpen" class="truncate">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('beneficiaries.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ request()->routeIs('beneficiaries.*') ? 'bg-blue-800 text-white font-bold shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        <span x-show="sidebarOpen" class="truncate">Beneficiaries</span>
                    </a>
                </li>
                @hasanyrole('Admin|Validator')
                <li>
                    <a href="{{ route('duplicates.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ request()->routeIs('duplicates.*') ? 'bg-blue-800 text-white font-bold shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                        <span x-show="sidebarOpen" class="flex-1 truncate">Duplicate Flags</span>
                        @php $pCount = \App\Models\DuplicateFlag::where('status', 'pending')->count(); @endphp
                        @if($pCount > 0)
                            <span x-show="sidebarOpen" class="rounded-full bg-red-600 px-2 py-0.5 text-[10px] font-bold text-white shadow-2xs">{{ $pCount }}</span>
                        @endif
                    </a>
                </li>
                @endhasanyrole
            </ul>
        </div>

        <div>
            <p x-show="sidebarOpen" class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Programs</p>
            <ul class="space-y-1 font-semibold text-xs">
                <li>
                    <a href="{{ route('dilp.groups.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ request()->routeIs('dilp.groups.*') ? 'bg-blue-800 text-white font-bold shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a5.97 5.97 0 00-.942 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                        <span x-show="sidebarOpen" class="truncate">DILP Groups</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('dilp.projects.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ request()->routeIs('dilp.projects.*') ? 'bg-blue-800 text-white font-bold shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12.75M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
                        <span x-show="sidebarOpen" class="truncate">DILP Projects</span>
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <p x-show="sidebarOpen" class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Data Management</p>
            <ul class="space-y-1 font-semibold text-xs">
                @hasanyrole('Admin|Encoder')
                <li>
                    <a href="{{ route('import.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ request()->routeIs('import.*') ? 'bg-blue-800 text-white font-bold shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        <span x-show="sidebarOpen" class="truncate">Import Excel / CSV</span>
                    </a>
                </li>
                @endhasanyrole
            </ul>
        </div>

        @hasrole('Admin')
        <div>
            <p x-show="sidebarOpen" class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">Administration</p>
            <ul class="space-y-1 font-semibold text-xs">
                <li>
                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ request()->routeIs('users.*') ? 'bg-blue-800 text-white font-bold shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                        <span x-show="sidebarOpen" class="truncate">User Accounts</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('audit.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ request()->routeIs('audit.*') ? 'bg-blue-800 text-white font-bold shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.25-2.142V9M12 18.75h.008v.008H12v-.008z"/></svg>
                        <span x-show="sidebarOpen" class="truncate">Audit Trail</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('settings.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition {{ request()->routeIs('settings.*') ? 'bg-blue-800 text-white font-bold shadow-xs' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.992l1.005.827c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span x-show="sidebarOpen" class="truncate">Settings</span>
                    </a>
                </li>
            </ul>
        </div>
        @endhasrole
    </nav>

    {{-- Collapse Toggle Footer --}}
    <div class="border-t border-slate-200 p-3">
        <button @click="sidebarOpen = !sidebarOpen"
                class="flex w-full items-center justify-center gap-2 rounded-lg p-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
            <svg class="h-4 w-4 transition-transform duration-200" :class="sidebarOpen ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.75 19.5l-7.5-7.5 7.5-7.5m-6 15L5.25 12l7.5-7.5"/></svg>
            <span x-show="sidebarOpen">Collapse Sidebar</span>
        </button>
    </div>
</aside>
