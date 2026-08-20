<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - DOLE Bukidnon Duplicate Detection System</title>

    {{-- Official DOLE Favicon --}}
    <link rel="icon" type="image/png" href="{{ asset('dole-logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('dole-logo.png') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="h-full font-sans antialiased text-slate-900 bg-slate-50" x-data="{ sidebarOpen: true }">
    <div class="min-h-screen bg-slate-50">
        {{-- Sidebar --}}
        @include('layouts.partials.sidebar')

        {{-- Main Layout Container --}}
        <div class="transition-all duration-200" :class="sidebarOpen ? 'lg:pl-64' : 'lg:pl-20'">
            {{-- Top Navigation Bar --}}
            @include('layouts.partials.topbar')

            {{-- Main Content View --}}
            <main class="p-6">
                {{-- Flash Notifications --}}
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" class="mb-5 flex items-center justify-between rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-xs font-bold text-emerald-900 shadow-2xs">
                        <div class="flex items-center gap-2.5">
                            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-emerald-700 hover:text-emerald-900 font-extrabold text-sm">&times;</button>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" class="mb-5 flex items-center justify-between rounded-xl border border-rose-300 bg-rose-50 p-4 text-xs font-bold text-rose-900 shadow-2xs">
                        <div class="flex items-center gap-2.5">
                            <svg class="h-5 w-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                        <button @click="show = false" class="text-rose-700 hover:text-rose-900 font-extrabold text-sm">&times;</button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    {{-- Native HTML5 Confirm Modal Dialog --}}
    <dialog id="html5-confirm-dialog" class="backdrop:bg-slate-900/60 backdrop:backdrop-blur-xs rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl max-w-md w-full m-auto transition-all">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div id="html5-confirm-icon-bg" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600 shadow-2xs">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div class="space-y-1">
                    <h3 id="html5-confirm-title" class="text-base font-extrabold text-slate-900">Confirm Action</h3>
                    <p id="html5-confirm-message" class="text-xs font-semibold text-slate-600 leading-relaxed">Are you sure you want to proceed?</p>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-slate-100 pt-4">
                <button type="button" id="html5-confirm-cancel-btn" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-extrabold text-slate-700 hover:bg-slate-100 transition cursor-pointer">
                    Cancel
                </button>
                <button type="button" id="html5-confirm-submit-btn" class="rounded-xl bg-red-600 px-4.5 py-2 text-xs font-extrabold text-white shadow-sm hover:bg-red-700 transition cursor-pointer">
                    Confirm
                </button>
            </div>
        </div>
    </dialog>

    <script>
        window.confirmAction = function(options) {
            const dialog = document.getElementById('html5-confirm-dialog');
            if (!dialog) return Promise.resolve(confirm(options.message || 'Are you sure?'));

            const titleEl = document.getElementById('html5-confirm-title');
            const msgEl = document.getElementById('html5-confirm-message');
            const submitBtn = document.getElementById('html5-confirm-submit-btn');
            const cancelBtn = document.getElementById('html5-confirm-cancel-btn');
            const iconBg = document.getElementById('html5-confirm-icon-bg');

            titleEl.textContent = options.title || 'Confirm Action';
            msgEl.textContent = options.message || 'Are you sure you want to proceed?';
            submitBtn.textContent = options.confirmText || 'Confirm';

            if (options.variant === 'warning') {
                submitBtn.className = 'rounded-xl bg-amber-600 hover:bg-amber-700 px-4.5 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer';
                iconBg.className = 'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 shadow-2xs';
            } else if (options.variant === 'primary') {
                submitBtn.className = 'rounded-xl bg-blue-800 hover:bg-blue-900 px-4.5 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer';
                iconBg.className = 'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-800 shadow-2xs';
            } else {
                submitBtn.className = 'rounded-xl bg-red-600 hover:bg-red-700 px-4.5 py-2 text-xs font-extrabold text-white shadow-sm transition cursor-pointer';
                iconBg.className = 'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600 shadow-2xs';
            }

            dialog.showModal();

            return new Promise((resolve) => {
                const handleConfirm = () => {
                    cleanup();
                    dialog.close();
                    resolve(true);
                };

                const handleCancel = () => {
                    cleanup();
                    dialog.close();
                    resolve(false);
                };

                const cleanup = () => {
                    submitBtn.removeEventListener('click', handleConfirm);
                    cancelBtn.removeEventListener('click', handleCancel);
                    dialog.removeEventListener('cancel', handleCancel);
                };

                submitBtn.addEventListener('click', handleConfirm);
                cancelBtn.addEventListener('click', handleCancel);
                dialog.addEventListener('cancel', handleCancel);
            });
        };

        // Global Event Delegation for forms using data-confirm
        document.addEventListener('submit', function(e) {
            const form = e.target;
            const message = form.getAttribute('data-confirm');
            if (message && !form.dataset.confirmed) {
                e.preventDefault();
                window.confirmAction({
                    title: form.getAttribute('data-confirm-title') || 'Confirmation Required',
                    message: message,
                    confirmText: form.getAttribute('data-confirm-btn') || 'Yes, Delete',
                    variant: form.getAttribute('data-confirm-variant') || 'danger'
                }).then((confirmed) => {
                    if (confirmed) {
                        form.dataset.confirmed = "true";
                        form.submit();
                    }
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
