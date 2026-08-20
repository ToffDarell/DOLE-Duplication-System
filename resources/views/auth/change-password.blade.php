@extends('layouts.app')
@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
<div class="mx-auto max-w-md pt-8">
    <div class="rounded-2xl border border-gray-200/60 bg-white p-8 shadow-sm">
        <h2 class="mb-6 text-lg font-semibold text-gray-800">Update Your Password</h2>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <div>
                <label for="current_password" class="mb-1.5 block text-sm font-medium text-gray-700">Current Password</label>
                <input type="password" name="current_password" id="current_password" required
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm transition focus:border-dole-blue focus:outline-none focus:ring-2 focus:ring-dole-blue/20">
                @error('current_password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">New Password</label>
                <input type="password" name="password" id="password" required
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm transition focus:border-dole-blue focus:outline-none focus:ring-2 focus:ring-dole-blue/20">
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm shadow-sm transition focus:border-dole-blue focus:outline-none focus:ring-2 focus:ring-dole-blue/20">
            </div>
            <button type="submit" class="w-full rounded-lg bg-dole-blue px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-dole-blue-dark focus:outline-none focus:ring-2 focus:ring-dole-blue/50 focus:ring-offset-2">
                Update Password
            </button>
        </form>
    </div>
</div>
@endsection
