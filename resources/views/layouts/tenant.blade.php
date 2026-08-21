<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') - {{ config('app.name', 'Mutiaraku CRM') }}</title>

    {{-- Dynamic Favicon from Company Settings --}}
    @if($appFavicon)
        <link rel="icon" type="image/png" href="{{ $appFavicon }}">
    @else
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @endif

    {{-- Google Fonts: Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Font Awesome 6 --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lbYI/Cxo=" crossorigin="anonymous"></script>

    {{-- Select2 CSS --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />

    {{-- Select2 JS --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    {{-- Vite CSS & JS --}}
    @vite(['resources/css/app.css', 'resources/css/sidebar.css', 'resources/css/responsive.css', 'resources/js/app.js', 'resources/js/sidebar.js'])

    {{-- Page Styles --}}
    @stack('styles')
    @yield('styles')
</head>
<body class="min-h-dvh bg-gray-100 overflow-x-hidden">
    {{-- Root Application Container with Global Mobile Sidebar State --}}
    <div
        x-data="{
            // Store is now global from sidebar.js - no need to init here
            get mobileOpen() {
                return Alpine.store('sidebar')?.mobileOpen ?? false;
            },

            toggleMobile() {
                Alpine.store('sidebar')?.toggleMobile();
            },

            closeMobile() {
                Alpine.store('sidebar')?.closeMobile();
            }
        }"
        @toggle-mobile-menu.window="toggleMobile()"
        @keydown.escape.window="closeMobile()"
        class="min-h-dvh flex"
    >
        {{-- Mobile Overlay (Backdrop) --}}
        <div
            x-show="Alpine.store('sidebar')?.mobileOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeMobile()"
            class="fixed inset-0 bg-black/50 z-40 lg:hidden"
            style="display: none;"
        ></div>

        {{-- Dynamic Sidebar --}}
        <x-dynamic-sidebar />

        {{-- Main Content Area --}}
        <div
            class="flex-1 flex flex-col min-w-0 transition-all duration-300"
            :class="{
                'lg:ml-[250px]': Alpine.store('sidebar')?.visible,
                'lg:ml-0': !Alpine.store('sidebar')?.visible
            }"
        >
            {{-- Top Bar --}}
            <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border-b border-gray-200 shadow-sm shrink-0">
                <div class="flex items-center justify-between h-14 sm:h-16 px-3 sm:px-4 lg:px-6">
                    {{-- Left Side: Mobile Menu Toggle & Page Title --}}
                    <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                        {{-- Mobile Menu Toggle --}}
                        <button
                            type="button"
                            class="lg:hidden flex-shrink-0 w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                            @click="toggleMobile()"
                            aria-label="Toggle menu"
                        >
                            <i class="fa-solid fa-bars text-lg sm:text-xl"></i>
                        </button>

                        {{-- Desktop Sidebar Toggle --}}
                        <button
                            type="button"
                            class="hidden lg:flex items-center justify-center w-8 h-8 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors"
                            @click="Alpine.store('sidebar')?.toggle()"
                            :title="Alpine.store('sidebar')?.visible ? 'Tutup Sidebar' : 'Buka Sidebar'"
                            aria-label="Toggle sidebar"
                        >
                            <i class="fa-solid text-sm" :class="Alpine.store('sidebar')?.visible ? 'fa-chevron-left' : 'fa-bars'"></i>
                        </button>

                        {{-- Page Title --}}
                        <h1 class="text-base sm:text-lg font-semibold text-gray-800 truncate">
                            @yield('page-title', 'Beranda')
                        </h1>
                    </div>

                    {{-- Right Side: Actions & User Menu --}}
                    <div class="flex items-center gap-1 sm:gap-3 flex-shrink-0">
                        {{-- Page Actions --}}
                        <div class="hidden sm:flex items-center gap-2">
                            @stack('page-actions')
                        </div>

                        {{-- User Dropdown --}}
                        <div class="relative" x-data="{ open: false }">
                            <button
                                type="button"
                                class="flex items-center gap-2 p-1.5 hover:bg-gray-100 rounded-lg transition-colors"
                                @click="open = !open"
                                @click.outside="open = false"
                            >
                                @php
                                    $headerPhotoUrl = null;
                                    if (auth()->user()->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists(auth()->user()->profile_photo))
                                        $headerPhotoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo);
                                @endphp
                                <img
                                    class="h-8 w-8 rounded-full"
                                    src="{{ $headerPhotoUrl ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name ?? 'U') . '&background=667eea&color=fff' }}"
                                    alt="{{ auth()->user()->name }}"
                                >
                                <span class="hidden sm:block text-sm font-medium text-gray-700 max-w-[120px] truncate">
                                    {{ auth()->user()->name ?? 'User' }}
                                </span>
                                <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                            </button>

                            <div
                                x-show="open"
                                x-transition
                                class="absolute right-0 mt-2 w-48 sm:w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
                                style="display: none;"
                            >
                                <a href="{{ route('profile.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    <i class="fa-solid fa-user w-4"></i>
                                    Profil Saya
                                </a>
                                <hr class="my-1 border-gray-200 dark:border-gray-700">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-3 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <i class="fa-solid fa-right-from-bracket w-4"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Mobile Page Actions --}}
                <div class="sm:hidden px-3 pb-3 flex flex-wrap gap-2">
                    @stack('page-actions')
                </div>
            </header>

            {{-- Breadcrumb Navigation --}}
            <div class="px-3 sm:px-4 lg:px-6 pt-3" id="breadcrumb-container">
                <x-breadcrumb />
            </div>

            {{-- Main Content --}}
            <main class="flex-1 p-3 sm:p-4 lg:p-6">
                @yield('content')
            </main>

            {{-- Global Footer --}}
            @if(isset($appFooterText))
                <footer class="shrink-0 border-t border-gray-200 bg-white/80 backdrop-blur-sm">
                    <div class="px-4 sm:px-6 lg:px-8 py-4">
                        <p class="text-sm text-gray-500 text-center sm:text-left">
                            {{ $appFooterText }}
                        </p>
                    </div>
                </footer>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-auto z-50 bg-emerald-500 text-white px-4 sm:px-6 py-3 rounded-lg shadow-lg flex items-center gap-3"
        >
            <i class="fa-solid fa-check-circle text-lg flex-shrink-0"></i>
            <span class="text-sm sm:text-base">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-auto z-50 bg-red-500 text-white px-4 sm:px-6 py-3 rounded-lg shadow-lg flex items-center gap-3"
        >
            <i class="fa-solid fa-exclamation-circle text-lg flex-shrink-0"></i>
            <span class="text-sm sm:text-base">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Scripts --}}
    @yield('scripts')
    @stack('scripts')
</body>
</html>
