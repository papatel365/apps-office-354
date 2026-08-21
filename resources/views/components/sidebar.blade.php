{{-- resources/views/components/sidebar.blade.php --}}
{{--
    CRM Sidebar Component

    MENU YANG DITAMPILKAN:
    1. Beranda
    2. Karyawan (Dropdown: Data Karyawan, Absensi, Laporan)
    3. Pengaturan (Dropdown: Backup, Hak Akses, Umum)
--}}

@php
    $user = auth()->user();
    $menuConfig = \App\Services\SidebarMenuConfig::getMenuConfig();
    $permissionService = \App\Services\Permission\UserPermissionService::forUser($user);

    $isSuperAdmin = $user && ($user->is_developer || $user->company_role === 'director' || $user->company_role === 'owner');

    $canAccess = function(string $module) use ($isSuperAdmin, $permissionService) {
        if ($isSuperAdmin) return true;
        return $permissionService->can($module);
    };

    $currentPath = '/' . request()->path();

    // Pre-calculate which dropdowns should be open based on current URL (server-side)
    function shouldDropdownBeOpenServerSide($menu, $currentPath) {
        $children = $menu['children'] ?? [];
        foreach ($children as $child) {
            if (!empty($child['route']) && \Illuminate\Support\Facades\Route::has($child['route'])) {
                $childPath = '/' . str_replace('.', '/', $child['route']);
                if ($currentPath === $childPath || str_starts_with($currentPath, $childPath . '/') || str_starts_with($currentPath, $childPath . '?')) {
                    return true;
                }
            }
        }
        return false;
    }

    $openDropdownsServerSide = [];
    foreach ($menuConfig as $menu) {
        if (($menu['type'] ?? '') === 'group' && shouldDropdownBeOpenServerSide($menu, $currentPath)) {
            $openDropdownsServerSide[$menu['key']] = true;
        }
    }

    // Get the redirect_to route for each group menu
    $groupRedirects = [];
    foreach ($menuConfig as $menu) {
        if (($menu['type'] ?? '') === 'group' && !empty($menu['redirect_to'])) {
            $groupRedirects[$menu['key']] = $menu['redirect_to'];
        }
    }
@endphp

<div x-data="sidebar()">
    {{-- Mobile Overlay --}}
    <div
        class="crm-sidebar-overlay"
        :class="{ 'active': mobileOpen }"
        @click="closeMobile()"
        x-show="mobileOpen"
    ></div>

    {{-- Sidebar --}}
    <aside class="crm-sidebar" :class="{ 'collapsed': collapsed, 'mobile-open': mobileOpen }">
        {{-- User Profile Card --}}
        <div class="sidebar-header">
            <div class="sidebar-user-card">
                <div class="sidebar-user-avatar">
                    @php
                        $sidebarPhotoUrl = null;
                        if ($user && $user->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo))
                            $sidebarPhotoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo);
                    @endphp
                    @if($sidebarPhotoUrl)
                        <img src="{{ $sidebarPhotoUrl }}" alt="{{ $user->name }}">
                    @else
                        {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                    @endif
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name">{{ $user->name ?? 'User' }}</div>
                    <div class="sidebar-user-email">{{ $user->email ?? '' }}</div>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="sidebar-nav" role="navigation">
            <ul class="sidebar-menu">
                @foreach($menuConfig as $menu)
                    @php
                        $menuType = $menu['type'] ?? 'item';
                    @endphp

                    @if($menuType === 'item')
                        {{-- STANDALONE MENU ITEM (Beranda/Dashboard) --}}
                        @if(
                            ($menu['key'] === 'dashboard' || $canAccess($menu['key']))
                            && !empty($menu['route'])
                            && Route::has($menu['route'])
                        )
                            <li class="sidebar-menu-item">
                                <a href="{{ route($menu['route']) }}"
                                   class="sidebar-link {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
                                    <i class="{{ $menu['icon_class'] ?? 'fa-solid ' . $menu['icon'] }} sidebar-icon"></i>
                                    <span class="sidebar-text">{{ $menu['label'] }}</span>
                                </a>
                            </li>
                        @endif

                    @elseif($menuType === 'group')
                        {{-- DROPDOWN GROUP --}}
                        @php
                            $hasAccessibleChild = false;
                            $accessibleChildren = [];
                            foreach ($menu['children'] ?? [] as $child) {
                                if ($canAccess($child['key'])) {
                                    $hasAccessibleChild = true;
                                    $accessibleChildren[] = $child;
                                }
                            }
                            // Check if this dropdown should be open (server-side calculated)
                            $isDropdownOpenServerSide = $openDropdownsServerSide[$menu['key']] ?? false;
                        @endphp

                        @if($hasAccessibleChild)
                            <li class="sidebar-dropdown {{ $isDropdownOpenServerSide ? 'open' : '' }}" :class="{ 'open': isDropdownOpen('{{ $menu['key'] }}') }">
                                <a href="{{ !empty($menu['redirect_to']) && Route::has($menu['redirect_to']) ? route($menu['redirect_to']) : '#' }}"
                                   class="sidebar-dropdown-toggle"
                                   @click.prevent="toggleDropdown('{{ $menu['key'] }}', '{{ !empty($menu['redirect_to']) && Route::has($menu['redirect_to']) ? route($menu['redirect_to']) : '' }}')">
                                    <i class="{{ $menu['icon_class'] ?? 'fa-solid ' . $menu['icon'] }} sidebar-icon"></i>
                                    <span class="sidebar-text">{{ $menu['label'] }}</span>
                                    <i class="fa-solid fa-chevron-right sidebar-chevron {{ $isDropdownOpenServerSide ? 'rotate-90' : '' }}"
                                       :class="{ 'rotate-90': isDropdownOpen('{{ $menu['key'] }}') }"></i>
                                </a>
                                <ul class="sidebar-submenu">
                                    @foreach($accessibleChildren as $child)
                                        @if(!empty($child['route']) && Route::has($child['route']))
                                            <li>
                                                <a href="{{ route($child['route']) }}"
                                                   class="sidebar-submenu-link {{ request()->routeIs($child['route']) ? 'active' : '' }}">
                                                    <i class="fa-solid {{ $child['icon_class'] ?? 'fa-solid ' . $child['icon'] }}"></i>
                                                    <span>{{ $child['label'] }}</span>
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </li>
                        @endif
                    @endif
                @endforeach
            </ul>

            {{-- TIDAK ADA SETTINGS SECTION --}}
            {{-- Menu Pengaturan tidak ditampilkan di sidebar --}}
            {{-- Route masih berfungsi, hanya tidak terlihat di menu ini --}}
        </nav>
    </aside>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('sidebar', () => ({
            mobileOpen: false,
            collapsed: false,

            init() {
                const savedCollapsed = localStorage.getItem('sidebar_collapsed_v2');
                if (savedCollapsed) {
                    this.collapsed = JSON.parse(savedCollapsed);
                }
            },

            toggleMobile() {
                this.mobileOpen = !this.mobileOpen;
            },

            closeMobile() {
                this.mobileOpen = false;
            },

            toggle() {
                this.collapsed = !this.collapsed;
                localStorage.setItem('sidebar_collapsed_v2', JSON.stringify(this.collapsed));
            }
        }));

        Alpine.data('sidebarMenu', () => ({
            // Initialize with server-side state merged with localStorage
            // Server-side already calculated openDropdowns based on current URL
            openDropdowns: {!! json_encode($openDropdownsServerSide) !!},
            menuConfig: @json($menuConfig),
            groupRedirects: @json($groupRedirects),

            init() {
                // Load saved state from localStorage (user's manual toggles)
                const saved = localStorage.getItem('sidebar_dropdowns_v3');
                if (saved) {
                    try {
                        const savedState = JSON.parse(saved);
                        // Merge saved state with server-side state
                        // Server-side takes precedence for initial URL-based state
                        for (const key in savedState) {
                            if (!this.openDropdowns.hasOwnProperty(key)) {
                                this.openDropdowns[key] = savedState[key];
                            }
                        }
                    } catch(e) {}
                }
                // Note: checkCurrentRoute() is NOT called here anymore
                // because server-side already handles initial dropdown state
            },

            toggleDropdown(name, redirectUrl = '') {
                // If URL is provided and dropdown is currently closed, redirect instead
                if (redirectUrl && !this.openDropdowns[name]) {
                    window.location.href = redirectUrl;
                    return;
                }
                this.openDropdowns[name] = !this.openDropdowns[name];
                localStorage.setItem('sidebar_dropdowns_v3', JSON.stringify(this.openDropdowns));
            },

            isDropdownOpen(name) {
                // First check localStorage state (user's manual toggles)
                if (this.openDropdowns.hasOwnProperty(name)) {
                    return this.openDropdowns[name];
                }
                // Fall back to server-side calculated state
                return false;
            }
        }));
    });
</script>
