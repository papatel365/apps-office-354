<!DOCTYPE html>
@php
    use App\Services\Permission\UserPermissionService;

    $user = auth()->user();
    $permissionService = UserPermissionService::forUser($user);

    $canAccess = function($permission) use ($user, $permissionService) {
        if (!$user) return false;
        $module = str_replace('sidebar.', '', $permission);
        return $permissionService->can($module);
    };
@endphp
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

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    {{-- jQuery --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    {{-- Select2 CSS --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />

    {{-- Select2 JS --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    {{-- Toastr CSS & JS --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    {{-- Vite CSS & JS --}}
    @vite(['resources/css/app.css', 'resources/css/sidebar.css', 'resources/css/responsive.css', 'resources/js/app.js', 'resources/js/sidebar.js'])

    {{-- Page Styles --}}
    @stack('styles')
    @yield('styles')

    <style>
        /* Ensure minimum touch target sizes */
        button, a, [role="button"] {
            min-height: 44px;
            min-width: 44px;
        }
        /* Prevent text selection on interactive elements */
        .no-select {
            -webkit-user-select: none;
            user-select: none;
        }
    </style>
</head>
<body class="min-h-dvh bg-gray-100 overflow-x-hidden">
    {{-- Page Loader Overlay - Prevents FOUC --}}
    <div id="page-loader">
        <div class="page-loader-content">
            <div class="page-loader-spinner"></div>
            <p class="page-loader-text">Memuat...</p>
        </div>
    </div>

    {{-- Root Application Container with Global Sidebar State --}}
    {{-- Store is initialized in resources/js/sidebar.js --}}
    <div
        x-data="{
            // Store is now global from sidebar.js - no need to init here
            // Just proxy the store methods for convenience

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
        {{-- Mobile Overlay (Backdrop) - z-50 to cover sidebar --}}
        <div
            x-show="Alpine.store('sidebar')?.mobileOpen"
            x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeMobile()"
            class="fixed inset-0 bg-black/50 z-50 lg:hidden"
        ></div>

        {{-- Dynamic Sidebar - renders menu based on module subscriptions --}}
        <x-dynamic-sidebar />

        {{-- Main Content Area --}}
        <div
            class="flex-1 flex flex-col min-w-0 transition-all duration-300"
            :class="{
                'lg:ml-[250px]': Alpine.store('sidebar')?.visible,
                'lg:ml-[72px]': !Alpine.store('sidebar')?.visible
            }"
        >
            {{-- Top Bar --}}
            <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-sm border-b border-gray-200 shadow-sm shrink-0">
                <div class="flex items-center justify-between h-14 sm:h-16 px-3 sm:px-4 lg:px-6">
                    {{-- Left Side: Mobile Menu Toggle & Page Title --}}
                    <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                        {{-- Mobile Menu Toggle (visible only on mobile) --}}
                        <button
                            type="button"
                            class="lg:hidden flex-shrink-0 w-10 h-10 flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                            @click="toggleMobile()"
                            aria-label="Toggle menu"
                        >
                            <i class="fa-solid fa-bars text-lg sm:text-xl"></i>
                        </button>

                        {{-- Desktop Sidebar Toggle (visible only on desktop) --}}
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

                        {{-- Notification Bell --}}
                        <div class="relative" x-data="notificationBell()">
                            <button
                                type="button"
                                class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors w-10 h-10 flex items-center justify-center"
                                @click="toggleDropdown()"
                                @click.outside="closeDropdown()"
                                title="Notifikasi"
                            >
                                <i class="fa-solid fa-bell text-lg"></i>
                                <span
                                    x-show="unreadCount > 0"
                                    x-text="unreadCount > 99 ? '99+' : unreadCount"
                                    class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-red-500 rounded-full"
                                ></span>
                            </button>

                            {{-- Notification Dropdown --}}
                            <div
                                x-show="isOpen"
                                x-transition
                                class="absolute right-0 mt-2 w-[calc(100vw-24px)] sm:w-96 max-w-[360px] bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden z-50"
                            >
                                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                                    <h3 class="font-semibold text-gray-800">Notifikasi</h3>
                                    <button
                                        x-show="unreadCount > 0"
                                        @click="markAllAsRead()"
                                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                                    >
                                        Tandai semua dibaca
                                    </button>
                                </div>
                                <div class="max-h-[400px] overflow-y-auto">
                                    <div x-show="loading" class="p-4 text-center text-gray-500">
                                        <i class="fa-solid fa-spinner fa-spin"></i>
                                        <span class="ml-2">Memuat...</span>
                                    </div>
                                    <div x-show="!loading && notifications.length === 0" class="p-8 text-center">
                                        <i class="fa-solid fa-bell-slash text-4xl text-gray-300"></i>
                                        <p class="mt-4 text-gray-500 text-sm">Tidak ada notifikasi</p>
                                    </div>
                                    <template x-for="notification in notifications" :key="notification.id">
                                        <div
                                            :class="notification.is_read ? 'bg-white' : 'bg-blue-50'"
                                            class="px-4 py-3 border-b border-gray-100 last:border-b-0 hover:bg-gray-50 transition-colors cursor-pointer"
                                            @click="handleNotificationClick(notification)"
                                        >
                                            <div class="flex gap-3">
                                                <div :class="notification.severity_bg" class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <i :class="notification.action_icon" class="text-sm"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="notification.title"></p>
                                                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-2" x-text="notification.message"></p>
                                                    <div class="flex items-center gap-2 mt-1.5">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600" x-text="notification.module"></span>
                                                        <span class="text-[10px] text-gray-400" x-text="notification.time_ago"></span>
                                                    </div>
                                                </div>
                                                <div class="flex-shrink-0" x-show="!notification.is_read">
                                                    <span class="w-2 h-2 bg-indigo-600 rounded-full block"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <div class="px-4 py-2 border-t border-gray-100 bg-gray-50 text-center">
                                    <a href="{{ route('notifications.all') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                                        Lihat semua notifikasi
                                    </a>
                                </div>
                            </div>
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
                                    $appLayoutPhotoUrl = null;
                                    if (auth()->user()->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists(auth()->user()->profile_photo))
                                        $appLayoutPhotoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo);
                                @endphp
                                <img
                                    class="h-8 w-8 rounded-full object-cover"
                                    src="{{ $appLayoutPhotoUrl ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name ?? 'U') . '&background=667eea&color=fff' }}"
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
                                        Keluar
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

            {{-- Modal Section (for permission modals, etc) --}}
            @yield('modal')
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
            class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-4 sm:w-auto z-50 bg-green-500 text-white px-4 sm:px-6 py-3 rounded-lg shadow-lg flex items-center gap-3"
        >
            <i class="fa-solid fa-check-circle flex-shrink-0"></i>
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
            <i class="fa-solid fa-exclamation-circle flex-shrink-0"></i>
            <span class="text-sm sm:text-base">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Notification Bell Script --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('notificationBell', () => ({
                isOpen: false,
                loading: false,
                notifications: [],
                unreadCount: 0,
                isLoggedIn: {{ auth()->check() ? 'true' : 'false' }},
                pollInterval: null,
                hasInitialized: false,

                init() {
                    // Only poll if user is logged in
                    if (this.isLoggedIn) {
                        // Delay initial fetch to prevent race conditions during login
                        // This ensures session is fully established before polling
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.fetchUnreadCount();
                                this.startPolling();
                            }, 1000);
                        });
                    }
                },

                /**
                 * Start the polling interval.
                 * Uses a flag to prevent multiple intervals.
                 */
                startPolling() {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                    }
                    this.pollInterval = setInterval(() => {
                        if (this.isLoggedIn) {
                            this.fetchUnreadCount();
                        }
                    }, 30000);
                },

                /**
                 * Stop polling (useful for cleanup or logout scenarios).
                 */
                stopPolling() {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
                    }
                },

                /**
                 * Fetch unread notification count.
                 * IMPORTANT: Uses Accept: application/json header to indicate AJAX request.
                 * This prevents Laravel from storing this URL as intended redirect.
                 */
                async fetchUnreadCount() {
                    try {
                        const response = await fetch('{{ route('notifications.unread-count') }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.unreadCount = data.data.count;
                        }
                    } catch (error) {
                        // Silently handle errors - polling should not interrupt user experience
                        // Only log in development
                        if (window.APP_DEBUG) {
                            console.error('Failed to fetch unread count:', error);
                        }
                    }
                },

                async toggleDropdown() {
                    this.isOpen = !this.isOpen;
                    if (this.isOpen && this.notifications.length === 0) {
                        await this.fetchNotifications();
                    }
                },

                closeDropdown() {
                    this.isOpen = false;
                },

                /**
                 * Fetch notifications for dropdown.
                 * IMPORTANT: Uses Accept: application/json header.
                 */
                async fetchNotifications() {
                    this.loading = true;
                    try {
                        const response = await fetch('{{ route('notifications.dropdown') }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.notifications = data.data;
                        }
                    } catch (error) {
                        if (window.APP_DEBUG) {
                            console.error('Failed to fetch notifications:', error);
                        }
                    } finally {
                        this.loading = false;
                    }
                },

                async handleNotificationClick(notification) {
                    if (!notification.is_read) {
                        await this.markAsRead(notification.id);
                    }
                    if (notification.action_url) {
                        window.location.href = notification.action_url;
                    }
                },

                async markAsRead(id) {
                    try {
                        await fetch(`/notifications/${id}/read`, {
                            method: 'PUT',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const notification = this.notifications.find(n => n.id === id);
                        if (notification) notification.is_read = true;
                        if (this.unreadCount > 0) this.unreadCount--;
                    } catch (error) {
                        if (window.APP_DEBUG) {
                            console.error('Failed to mark as read:', error);
                        }
                    }
                },

                async markAllAsRead() {
                    try {
                        await fetch('{{ route('notifications.read-all') }}', {
                            method: 'PUT',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        this.notifications.forEach(n => n.is_read = true);
                        this.unreadCount = 0;
                    } catch (error) {
                        if (window.APP_DEBUG) {
                            console.error('Failed to mark all as read:', error);
                        }
                    }
                }
            }));
        });

        // Debug flag for development
        window.APP_DEBUG = {{ config('app.debug') ? 'true' : 'false' }};

        // =====================================================
        // PAGE LOADER - Prevents FOUC and initial render flash
        // =====================================================
        (function() {
            // Create global page loader manager
            window.PageLoader = {
                ready: false,
                hidden: false,
                callbacks: [],

                // Register a callback to be called when page is ready
                onReady: function(callback) {
                    if (this.ready) {
                        callback();
                    } else {
                        this.callbacks.push(callback);
                    }
                },

                // Hide the page loader
                hide: function() {
                    if (this.hidden) return;
                    this.hidden = true;

                    var loader = document.getElementById('page-loader');
                    if (loader) {
                        loader.classList.add('page-loader-hidden');
                        setTimeout(function() {
                            if (loader.parentNode) {
                                loader.style.display = 'none';
                            }
                        }, 300);
                    }
                },

                // Mark page as ready and execute all callbacks
                done: function() {
                    this.ready = true;
                    this.callbacks.forEach(function(cb) { cb(); });
                    this.callbacks = [];
                    this.hide();
                }
            };

            // Auto-hide loader after a maximum timeout (safety net)
            // Pages that call PageLoader.done() explicitly will hide it earlier
            setTimeout(function() {
                if (!window.PageLoader.hidden) {
                    window.PageLoader.hide();
                }
            }, 3000);

            // Signal page loader ready after DOM is ready
            // Individual pages can call PageLoader.done() to hide earlier
            document.addEventListener('DOMContentLoaded', function() {
                // Small delay to ensure Alpine.js is initialized
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        // Don't auto-hide here - let individual pages control this
                        // This allows pages with heavy components (like charts) to
                        // control when the loader disappears
                    });
                });
            });
        })();
    </script>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
