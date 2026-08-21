<aside id="developer-sidebar" class="fixed inset-y-0 left-0 w-64 bg-gray-900 text-white flex flex-col z-40">
    {{-- Logo --}}
    <div class="h-16 flex items-center px-5 border-b border-gray-800 flex-shrink-0">
        <a href="{{ route('developer.dashboard') }}" class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                <i class="fa-solid fa-code text-white"></i>
            </div>
            <span class="text-base font-bold tracking-tight">Developer</span>
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        {{-- Menu Utama --}}
        <a href="{{ route('developer.dashboard') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('developer.dashboard') ? 'bg-indigo-500/15 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
            <i class="fa-solid fa-chart-pie w-5 text-center"></i>
            <span>Beranda</span>
        </a>
        <a href="{{ route('developer.company.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('developer.company.*') ? 'bg-indigo-500/15 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
            <i class="fa-solid fa-building w-5 text-center"></i>
            <span class="flex-1">Perusahaan Saya</span>
        </a>

        {{-- Manajemen --}}
        <div class="pt-4">
            <p class="px-3 pb-2 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Manajemen</p>
            <a href="{{ route('developer.karyawan.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('developer.staff.*') ? 'bg-indigo-500/15 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fa-solid fa-users w-5 text-center"></i>
                <span>Staff</span>
            </a>
        </div>

        {{-- Audit & Logs --}}
        <div class="pt-4">
            <p class="px-3 pb-2 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Audit & Log</p>
            <a href="{{ route('developer.audit-logs.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('developer.audit-logs.*') ? 'bg-indigo-500/15 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fa-solid fa-clipboard-list w-5 text-center"></i>
                <span>Log Audit</span>
            </a>
            <a href="{{ route('developer.api-keys.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('developer.api-keys.*') ? 'bg-indigo-500/15 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fa-solid fa-key w-5 text-center"></i>
                <span>Kunci API</span>
            </a>
        </div>

        {{-- Notifications --}}
        <div class="pt-4">
            <a href="{{ route('developer.notifications.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('developer.notifications.*') ? 'bg-indigo-500/15 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fa-solid fa-bell w-5 text-center"></i>
                <span class="flex-1">Notifikasi</span>
                @php $unreadCount = App\Models\DeveloperNotification::unread()->count(); @endphp
                @if($unreadCount > 0)
                <span class="px-2 py-0.5 bg-red-500/20 text-red-400 rounded-full text-xs font-semibold">{{ $unreadCount }}</span>
                @endif
            </a>
        </div>

        {{-- Pengaturan --}}
        <div class="pt-4">
            <p class="px-3 pb-2 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Pengaturan</p>
            <a href="{{ route('developer.settings.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('developer.settings.*') ? 'bg-indigo-500/15 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fa-solid fa-gear w-5 text-center"></i>
                <span>Pengaturan</span>
            </a>
            <a href="{{ route('developer.profile.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('developer.profile.*') ? 'bg-indigo-500/15 text-white' : 'text-gray-400 hover:bg-white/5 hover:text-white' }}">
                <i class="fa-solid fa-user-pen w-5 text-center"></i>
                <span>Profil Saya</span>
            </a>
        </div>
    </nav>

    {{-- Footer Stats --}}
    <div class="flex-shrink-0 border-t border-gray-800">
        <div class="p-3 border-b border-gray-800">
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-gray-800/50 rounded-lg p-2.5 text-center">
                    <p class="text-[10px] text-gray-500 uppercase">Perusahaan</p>
                    <p class="text-sm font-bold text-indigo-400">{{ App\Models\Company::count() }}</p>
                </div>
                <div class="bg-gray-800/50 rounded-lg p-2.5 text-center">
                    <p class="text-[10px] text-gray-500 uppercase">Staff</p>
                    <p class="text-sm font-bold text-emerald-400">{{ \App\Modules\System\Models\User::where('company_role', 'staff')->count() }}</p>
                </div>
            </div>
        </div>

        {{-- User --}}
        <div class="p-3">
            <div class="flex items-center gap-3">
                <img class="w-10 h-10 rounded-xl ring-2 ring-indigo-500/30" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'D') }}&background=4f46e5&color=fff&size=80" alt="">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                        <span class="text-xs text-gray-400">Developer</span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 text-gray-500 hover:text-red-400 hover:bg-red-500/10 rounded-lg" title="Keluar">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</aside>
