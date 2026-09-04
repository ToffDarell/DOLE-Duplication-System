@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'System User Management')
@section('page-subtitle', 'Manage staff accounts, assign Spatie roles, activate/deactivate, and trigger password resets')

@section('content')
<div x-data="{ createModal: false }">
    {{-- Header Action Bar --}}
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <button @click="createModal = true" id="btn-create-user" 
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-800 hover:bg-blue-900 px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition cursor-pointer">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                <span>Add New Staff / User</span>
            </button>
            <span class="rounded-full bg-slate-100 border border-slate-200 px-3 py-1 text-xs font-bold text-slate-700">
                Total Staff: <span class="font-extrabold text-slate-900">{{ $users->total() }}</span>
            </span>
        </div>
    </div>

    {{-- Create User Modal --}}
    <div x-show="createModal" x-transition.opacity.duration.200ms 
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4 backdrop-blur-xs" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200" @click.away="createModal = false">
            <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-100 text-blue-800 font-bold">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-extrabold text-slate-900">Create Staff Account</h3>
                </div>
                <button type="button" @click="createModal = false" class="rounded-lg p-1 text-slate-400 hover:text-slate-600 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Maria Santos"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-900 focus:border-blue-800 focus:outline-none shadow-2xs">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Email Address *</label>
                    <input type="email" name="email" required placeholder="name@dole.gov.ph"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-900 focus:border-blue-800 focus:outline-none shadow-2xs">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">Employee ID</label>
                    <input type="text" name="employee_id" placeholder="e.g. BUK-2026-042"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs font-semibold text-slate-900 focus:border-blue-800 focus:outline-none shadow-2xs">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-bold text-slate-700">System Role *</label>
                    <select name="role" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-800 focus:border-blue-800 focus:outline-none shadow-2xs cursor-pointer">
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-data="{ showPass: false }">
                    <label class="mb-1 block text-xs font-bold text-slate-700">Initial Password *</label>
                    <div class="relative">
                        <input :type="showPass ? 'text' : 'password'" name="password" required 
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2 pr-10 text-xs font-semibold text-slate-900 focus:border-blue-800 focus:outline-none shadow-2xs">
                        <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-blue-800 focus:outline-none transition cursor-pointer" tabindex="-1" aria-label="Toggle password visibility">
                            <svg x-show="!showPass" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg x-show="showPass" x-cloak class="h-4 w-4 text-blue-800" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" @click="createModal = false" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-xl bg-blue-800 hover:bg-blue-900 px-5 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[700px]">
                <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 min-w-[220px]">Staff Member</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[120px]">Employee ID</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[130px]">Assigned Role</th>
                        <th class="px-5 py-3.5 whitespace-nowrap min-w-[110px]">Status</th>
                        <th class="px-5 py-3.5 text-right whitespace-nowrap min-w-[180px]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium bg-white">
                    @foreach($users as $u)
                        @php
                            $initials = strtoupper(substr($u->name, 0, 1) . (str_contains($u->name, ' ') ? substr(explode(' ', $u->name)[1], 0, 1) : ''));
                            $roleName = $u->roles->first()?->name ?? 'None';
                            
                            if ($roleName === 'Admin') {
                                $roleBadge = 'bg-purple-50 text-purple-900 border-purple-200';
                            } elseif ($roleName === 'Validator') {
                                $roleBadge = 'bg-blue-50 text-blue-900 border-blue-200';
                            } elseif ($roleName === 'Encoder') {
                                $roleBadge = 'bg-emerald-50 text-emerald-900 border-emerald-200';
                            } else {
                                $roleBadge = 'bg-slate-100 text-slate-700 border-slate-200';
                            }
                        @endphp
                        <tr class="transition hover:bg-slate-50/80">
                            {{-- Staff Name & Avatar --}}
                            <td class="px-5 py-3.5 min-w-[220px]">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-extrabold text-xs shadow-2xs border border-slate-200">
                                        {{ $initials }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-extrabold text-slate-900 text-xs sm:text-sm tracking-tight truncate">{{ $u->name }}</p>
                                        <p class="text-[11px] text-slate-400 font-normal truncate">{{ $u->email }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- Employee ID --}}
                            <td class="px-5 py-3.5 whitespace-nowrap text-slate-700 font-mono text-xs">
                                {{ $u->employee_id ?? '—' }}
                            </td>

                            {{-- Distinct Role Badge --}}
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-md border {{ $roleBadge }} px-2.5 py-0.5 text-[10px] font-extrabold shadow-2xs">
                                    {{ $roleName }}
                                </span>
                            </td>

                            {{-- Active/Inactive Status with Live Dot --}}
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if($u->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-[10px] font-extrabold text-emerald-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 border border-rose-200 px-2.5 py-0.5 text-[10px] font-extrabold text-rose-800">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    {{-- Reset Password --}}
                                    <form action="{{ route('users.reset-password', $u) }}" method="POST" 
                                          data-confirm="Are you sure you want to reset password for {{ addslashes($u->name) }}?" 
                                          data-confirm-title="Reset Password" data-confirm-btn="Reset Password" data-confirm-variant="warning" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-xl border border-amber-300 bg-amber-50 px-2.5 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100 transition shadow-2xs cursor-pointer" title="Reset user password">
                                            <svg class="h-3.5 w-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                                            </svg>
                                            <span>Reset PW</span>
                                        </button>
                                    </form>

                                    {{-- Toggle Status --}}
                                    @if($u->id !== auth()->id())
                                        <form action="{{ route('users.toggle-status', $u) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-xl border px-2.5 py-1.5 text-xs font-bold transition shadow-2xs cursor-pointer
                                                {{ $u->is_active ? 'border-rose-300 bg-rose-50 text-rose-800 hover:bg-rose-100' : 'border-emerald-300 bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }}">
                                                <span>{{ $u->is_active ? 'Deactivate' : 'Activate' }}</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">{{ $users->links() }}</div>
    </div>
</div>
@endsection
