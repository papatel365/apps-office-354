<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Superadmin Panel') - {{ config('app.name') }}</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/css/sidebar.css', 'resources/css/responsive.css', 'resources/js/app.js'])

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    {{-- Page Styles --}}
    @stack('styles')
    @yield('styles')

    <style>
        /* Custom Scrollbar for Sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Active Menu Animation */
        .nav-item-active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.15) 0%, transparent 100%);
            border-left: 3px solid #6366f1;
        }
    </style>
</head>
<body class="h-full bg-gray-50 font-sans antialiased overflow-x-hidden">
    {{-- Full Height Flex Container --}}
    {{-- Store is initialized in resources/js/sidebar.js --}}
    <div
        class="flex h-screen overflow-hidden flex-col lg:flex-row"
        @keydown.escape.window="Alpine.store('sidebar')?.closeMobile()"
    >
        {{-- Mobile Header (visible only on mobile) --}}
        <div class="lg:hidden fixed top-0 left-0 right-0 z-50 bg-gray-900 text-white h-14 px-4 flex items-center justify-between shadow-lg">
            <a href="{{ route('developer.dashboard') }}" class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                    <i class="fa-solid fa-shield-halved text-white text-xs"></i>
                </div>
                <span class="text-sm font-bold">Superadmin</span>
            </a>
            <button type="button" @click="Alpine.store('sidebar')?.toggleMobile()" class="p-2 text-white hover:bg-gray-800 rounded-lg">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        {{-- Mobile Sidebar Overlay --}}
        <div
            x-show="Alpine.store('sidebar')?.mobileOpen"
            x-transition
            class="lg:hidden fixed inset-0 bg-black/50 z-40"
            @click="Alpine.store('sidebar')?.toggleMobile()"
            style="display: none;"
        ></div>

        {{-- Sidebar - Fixed Width, Full Height --}}
        <aside id="developer-sidebar"
              class="w-64 lg:w-64 flex flex-col bg-gray-900 text-white shrink-0 fixed lg:relative inset-y-0 left-0 z-50 transform transition-transform duration-300"
              :class="{
                  '-translate-x-full': !Alpine.store('sidebar')?.visible && !Alpine.store('sidebar')?.mobileOpen,
                  'translate-x-0': Alpine.store('sidebar')?.visible || Alpine.store('sidebar')?.mobileOpen
              }"
              @toggle-mobile-menu.window="Alpine.store('sidebar')?.toggleMobile()">
            {{-- Logo Header --}}
            <div class="h-16 flex items-center px-4 border-b border-gray-800 flex-shrink-0">
                <a href="{{ route('developer.dashboard') }}" class="flex items-center gap-3 group">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <i class="fa-solid fa-shield-halved text-white text-sm"></i>
                    </div>
                    <div>
                        <span class="text-sm font-bold text-white">Superadmin</span>
                        <span class="block text-[10px] text-gray-400 uppercase tracking-wider">Control Panel</span>
                    </div>
                </a>
                {{-- Mobile Close Button (X) --}}
                <button
                    type="button"
                    class="lg:hidden ml-auto w-8 h-8 flex items-center justify-center text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                    @click="Alpine.store('sidebar')?.closeMobile()"
                    aria-label="Tutup sidebar"
                >
                    <i class="fa-solid fa-times text-sm"></i>
                </button>
            </div>

            {{-- User Profile & Controls --}}
            <div class="px-4 py-3 border-b border-gray-800/50">
                {{-- User Info --}}
                <div class="flex items-center gap-3">
                    <img class="h-9 w-9 rounded-lg ring-2 ring-indigo-500/30 shrink-0" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'D') }}&background=4f46e5&color=fff&size=64" alt="">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Navigation - Scrollable --}}
            <nav class="flex-1 overflow-y-auto sidebar-scroll py-4 px-3 space-y-6">
                {{-- Main Menu - SUPERADMIN Section --}}
                <div>
                    <div class="px-3 py-2 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Superadmin</div>

                    {{-- Beranda --}}
                    <a href="{{ route('developer.dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 mb-2
                              {{ request()->routeIs('developer.dashboard') ? 'nav-item-active text-white' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        <i class="fa-solid fa-th-large w-5 text-center text-sm {{ request()->routeIs('developer.dashboard') ? 'text-indigo-400' : 'text-gray-500' }}"></i>
                        Beranda
                    </a>
                </div>

                {{-- PENGATURAN Section --}}
                <div>
                    <div class="px-3 py-2 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Pengaturan</div>

                    {{-- Perusahaan Saya --}}
                    <a href="{{ route('developer.company.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-all duration-200 mb-1
                              {{ request()->routeIs('developer.company.my.*', 'developer.company.staff.*') ? 'nav-item-active text-white' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        <i class="fa-solid fa-building w-5 text-center text-sm {{ request()->routeIs('developer.company.my.*', 'developer.company.staff.*') ? 'text-indigo-400' : 'text-gray-500' }}"></i>
                        <span class="flex-1">Perusahaan Saya</span>
                    </a>
                </div>
            </nav>

            {{-- Footer - Always at Bottom --}}
            <div class="mt-auto border-t border-gray-800 flex-shrink-0">
                {{-- Logout Button --}}
                <div class="px-4 py-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2 text-sm text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg transition-colors">
                            <i class="fa-solid fa-right-from-bracket text-sm"></i>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main Content - Flexible Width --}}
        <div
            class="flex-1 flex flex-col overflow-hidden pt-14 lg:pt-0"
            :class="{
                'lg:ml-64': Alpine.store('sidebar')?.visible,
                'lg:ml-0': !Alpine.store('sidebar')?.visible
            }"
        >
            {{-- Top Header - Responsive --}}
            <header class="h-14 lg:h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
                <div class="flex items-center gap-2 lg:gap-3 min-w-0">
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

                    <div class="min-w-0">
                        <h1 class="text-sm lg:text-base font-semibold text-gray-900 truncate">@yield('page-title', 'Beranda')</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2 lg:gap-3 flex-shrink-0">
                    {{-- Online Status --}}
                    <span class="hidden sm:flex items-center gap-1.5 px-2 py-1 bg-emerald-50 text-emerald-700 text-xs font-medium rounded-full">
                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                        Online
                    </span>

                    {{-- Notifications Bell --}}
                    <a href="{{ route('developer.notifications.index') }}" class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                        <i class="fa-solid fa-bell text-lg"></i>
                        @php $notifCount = \App\Models\DeveloperNotification::where('is_read', false)->count(); @endphp
                        @if($notifCount > 0)
                            <span class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">{{ $notifCount > 9 ? '9+' : $notifCount }}</span>
                        @endif
                    </a>

                    {{-- User Dropdown --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button" class="flex items-center gap-2 p-1.5 hover:bg-gray-100 rounded-lg transition-colors" @click="open = !open" @click.outside="open = false">
                            <img class="h-8 w-8 rounded-lg ring-2 ring-indigo-200" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'D') }}&background=4f46e5&color=fff&size=64" alt="">
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400 hidden sm:block"></i>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50" style="display: none;">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('developer.profile.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fa-solid fa-user w-4 text-gray-400"></i>
                                Profile
                            </a>
                            <a href="{{ route('developer.settings.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fa-solid fa-gear w-4 text-gray-400"></i>
                                Settings
                            </a>
                            <div class="border-t border-gray-100 mt-1 pt-1">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-3 px-4 py-2 w-full text-sm text-red-600 hover:bg-red-50">
                                        <i class="fa-solid fa-right-from-bracket w-4"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content - Scrollable - Responsive --}}
            <main class="flex-1 overflow-auto p-4 sm:p-6 bg-gray-50">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg flex items-center gap-2" x-data="{ show: true }" x-show="show">
                        <i class="fa-solid fa-check-circle flex-shrink-0"></i>
                        <span class="text-sm">{{ session('success') }}</span>
                        <button type="button" class="ml-auto flex-shrink-0" @click="show = false">
                            <i class="fa-solid fa-times text-sm"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-center gap-2" x-data="{ show: true }" x-show="show">
                        <i class="fa-solid fa-exclamation-circle flex-shrink-0"></i>
                        <span class="text-sm">{{ session('error') }}</span>
                        <button type="button" class="ml-auto flex-shrink-0" @click="show = false">
                            <i class="fa-solid fa-times text-sm"></i>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg flex items-center gap-2" x-data="{ show: true }" x-show="show">
                        <i class="fa-solid fa-exclamation-triangle flex-shrink-0"></i>
                        <span class="text-sm">{{ session('warning') }}</span>
                        <button type="button" class="ml-auto flex-shrink-0" @click="show = false">
                            <i class="fa-solid fa-times text-sm"></i>
                        </button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    {{-- Scripts --}}
    @stack('scripts')
    @yield('scripts')
</body>
</html>
