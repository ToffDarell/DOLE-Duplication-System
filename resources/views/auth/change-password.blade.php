@extends('layouts.app')
@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
<div class="mx-auto max-w-md pt-8">
    <div class="rounded-2xl border border-gray-200/60 bg-white p-8 shadow-sm">
        <h2 class="mb-6 text-lg font-semibold text-gray-800">Update Your Password</h2>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
            @csrf
            <div x-data="{ showCurrent: false }">
                <label for="current_password" class="mb-1.5 block text-sm font-medium text-gray-700">Current Password</label>
                <div class="relative">
                    <input :type="showCurrent ? 'text' : 'password'" name="current_password" id="current_password" required
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-11 text-sm shadow-sm transition focus:border-dole-blue focus:outline-none focus:ring-2 focus:ring-dole-blue/20">
                    <button type="button" @click="showCurrent = !showCurrent" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-dole-blue focus:outline-none transition cursor-pointer" tabindex="-1" aria-label="Toggle current password visibility">
                        <svg x-show="!showCurrent" class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="showCurrent" x-cloak class="h-4.5 w-4.5 text-dole-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                @error('current_password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ showNew: false }">
                <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">New Password</label>
                <div class="relative">
                    <input :type="showNew ? 'text' : 'password'" name="password" id="password" required
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-11 text-sm shadow-sm transition focus:border-dole-blue focus:outline-none focus:ring-2 focus:ring-dole-blue/20">
                    <button type="button" @click="showNew = !showNew" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-dole-blue focus:outline-none transition cursor-pointer" tabindex="-1" aria-label="Toggle new password visibility">
                        <svg x-show="!showNew" class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="showNew" x-cloak class="h-4.5 w-4.5 text-dole-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="{ showConfirm: false }">
                <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">Confirm New Password</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'" name="password_confirmation" id="password_confirmation" required
                           class="w-full rounded-lg border border-gray-300 px-4 py-2.5 pr-11 text-sm shadow-sm transition focus:border-dole-blue focus:outline-none focus:ring-2 focus:ring-dole-blue/20">
                    <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-dole-blue focus:outline-none transition cursor-pointer" tabindex="-1" aria-label="Toggle confirm password visibility">
                        <svg x-show="!showConfirm" class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <svg x-show="showConfirm" x-cloak class="h-4.5 w-4.5 text-dole-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full rounded-lg bg-dole-blue px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-dole-blue-dark focus:outline-none focus:ring-2 focus:ring-dole-blue/50 focus:ring-offset-2 cursor-pointer">
                Update Password
            </button>
        </form>
    </div>
</div>
@endsection
