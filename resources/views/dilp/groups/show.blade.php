@extends('layouts.app')
@section('title', $group->group_name . ' - DILP Group Details')
@section('page-title', $group->group_name)
@section('page-subtitle', 'Co-Partner details, project affiliations, and member contact roster')

@section('content')
<div class="space-y-6">
    {{-- Header Action Bar --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('dilp.groups.index') }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                Back to Groups
            </a>
            <span class="text-xs font-semibold text-slate-500">ID: #{{ $group->id }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('dilp.groups.edit', $group) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-blue-700 px-4 py-2 text-xs font-extrabold text-white shadow-md transition hover:bg-blue-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                Edit Group
            </a>
        </div>
    </div>

    {{-- Overview Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="gov-card p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Group Name</p>
            <p class="mt-1 text-base font-extrabold text-slate-900 truncate">{{ $group->group_name }}</p>
        </div>
        <div class="gov-card p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Co-Partner Organization</p>
            <p class="mt-1 text-base font-extrabold text-slate-900 truncate">{{ $group->co_partner_name ?? 'Not Assigned' }}</p>
        </div>
        <div class="gov-card p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Co-Partner Contact</p>
            <p class="mt-1 text-base font-extrabold text-slate-900 truncate">{{ $group->co_partner_contact ?? 'N/A' }}</p>
        </div>
        <div class="gov-card p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Roster Members</p>
            <p class="mt-1 text-2xl font-black text-amber-600">{{ $group->members_count ?? $group->members()->count() }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Batch Upload Form --}}
        <div class="gov-card p-6">
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-500 text-white font-black shadow-xs">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-900">Batch Upload Member Roster</h3>
                        <p class="text-[11px] font-medium text-slate-500">Upload CSV contact list for this group</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('dilp.groups.import-members', $group) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-800">Select CSV File *</label>
                    <input type="file" name="file" accept=".csv,.txt" required
                           class="w-full rounded-xl border border-slate-300 p-2 text-xs text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-amber-600 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-white hover:file:bg-amber-700">
                    @error('file')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50/80 p-3.5 text-xs text-amber-900 shadow-2xs space-y-1.5">
                    <p class="font-bold flex items-center gap-1.5 text-amber-950">
                        <svg class="h-4 w-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                        CSV Format Specification:
                    </p>
                    <p class="text-[11px] text-amber-900">Your CSV file should include columns for:</p>
                    <div class="rounded-lg bg-white/90 p-2 font-mono text-[10px] text-slate-800 border border-amber-200">
                        member_name, contact_no, designation<br>
                        Juan Dela Cruz, 09171234567, President<br>
                        Maria Santos, 09189998888, Secretary
                    </div>
                </div>

                <button type="submit"
                        class="w-full rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-extrabold px-4 py-2.5 text-xs shadow-md transition cursor-pointer flex items-center justify-center gap-2">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                    Upload Member Roster
                </button>
            </form>
        </div>

        {{-- Member Roster Table --}}
        <div class="gov-card p-6 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Registered Organization Members</h3>
                    <p class="text-xs font-medium text-slate-500">Contact roster for {{ $group->group_name }}</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 border border-slate-200">
                    {{ $members->total() }} Member(s)
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-extrabold uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Member Name</th>
                            <th class="px-4 py-3">Contact Number</th>
                            <th class="px-4 py-3">Designation / Role</th>
                            <th class="px-4 py-3">Date Added</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($members as $index => $member)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-4 py-3 font-medium text-slate-400">{{ $members->firstItem() + $index }}</td>
                                <td class="px-4 py-3 font-extrabold text-slate-900">{{ $member->member_name }}</td>
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $member->contact_no ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($member->designation)
                                        <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-bold text-blue-800 border border-blue-200">
                                            {{ $member->designation }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">Member</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $member->created_at ? $member->created_at->format('M d, Y') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center text-slate-400 font-medium">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-10 w-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
                                        <p class="text-sm font-semibold text-slate-600">No member contacts uploaded yet</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Use the upload box on the left to batch import organization members.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($members->hasPages())
                <div class="mt-4 border-t border-slate-200 pt-3">
                    {{ $members->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
