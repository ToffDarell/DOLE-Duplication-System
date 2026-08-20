@extends('layouts.app')
@section('title', 'User Management')
@section('page-title', 'System User Management')
@section('page-subtitle', 'Manage staff accounts, assign Spatie roles, activate/deactivate, and trigger password resets')

@section('content')
<div class="mb-6 flex items-center justify-between" x-data="{ createModal: false }">
    <button @click="createModal = true" id="btn-create-user" class="rounded-lg bg-dole-blue px-4 py-2 text-sm font-semibold text-white hover:bg-dole-blue-dark">
        + Add New Staff / User
    </button>

    {{-- Create User Modal --}}
    <div x-show="createModal" x-transition class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm" style="display: none;">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl" @click.away="createModal = false">
            <h3 class="mb-4 text-lg font-bold text-gray-900">Create Staff User</h3>
            <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">Full Name *</label>
                    <input type="text" name="name" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">Email Address *</label>
                    <input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">Employee ID</label>
                    <input type="text" name="employee_id" placeholder="EMP-123" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">Role *</label>
                    <select name="role" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                        @foreach($roles as $r)
                            <option value="{{ $r->name }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-700">Initial Password *</label>
                    <input type="password" name="password" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-dole-blue focus:outline-none">
                </div>
                <div class="flex justify-end gap-2 border-t pt-4">
                    <button type="button" @click="createModal = false" class="rounded-lg border px-4 py-2 text-xs font-medium">Cancel</button>
                    <button type="submit" class="rounded-lg bg-dole-blue px-5 py-2 text-xs font-semibold text-white">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="rounded-xl border border-gray-200/60 bg-white shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-100 bg-gray-50/70 text-xs uppercase text-gray-500">
            <tr>
                <th class="px-6 py-3.5 font-semibold">User</th>
                <th class="px-6 py-3.5 font-semibold">Employee ID</th>
                <th class="px-6 py-3.5 font-semibold">Role</th>
                <th class="px-6 py-3.5 font-semibold">Status</th>
                <th class="px-6 py-3.5 text-right font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($users as $u)
                <tr class="transition hover:bg-gray-50/50">
                    <td class="px-6 py-4">
                        <p class="font-bold text-gray-900">{{ $u->name }}</p>
                        <p class="text-xs text-gray-500">{{ $u->email }}</p>
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $u->employee_id ?? '—' }}</td>
                    <td class="px-6 py-4">
                        <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-dole-blue">
                            {{ $u->roles->first()?->name ?? 'None' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold uppercase
                            {{ $u->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                            {{ $u->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <form action="{{ route('users.reset-password', $u) }}" method="POST" data-confirm="Are you sure you want to reset password for {{ addslashes($u->name) }}?" data-confirm-title="Reset Password" data-confirm-btn="Reset Password" data-confirm-variant="warning">
                                @csrf
                                <button type="submit" class="rounded-xl border border-amber-300 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800 hover:bg-amber-100 transition cursor-pointer">
                                    Reset Password
                                </button>
                            </form>
                            @if($u->id !== auth()->id())
                                <form action="{{ route('users.toggle-status', $u) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="rounded border px-2.5 py-1 text-xs font-semibold
                                        {{ $u->is_active ? 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100' : 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                        {{ $u->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-6 py-4 border-t">{{ $users->links() }}</div>
</div>
@endsection
