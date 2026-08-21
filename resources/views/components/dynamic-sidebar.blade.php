{{-- resources/views/components/dynamic-sidebar.blade.php --}}

@php
use App\Services\SidebarMenuConfig;
use App\Services\Permission\UserPermissionService;

$user = auth()->user();
$menuConfig = SidebarMenuConfig::getVisibleMenuConfig();
$permService = $user ? UserPermissionService::forUser($user) : null;
$accessibleModules = $permService ? $permService->getAccessibleModules() : [];
$isSuperAdmin = $permService && $permService->isSuperAdmin();

$sidebarPerms = [];
foreach ($accessibleModules as $module) {
    $sidebarPerms[] = 'sidebar.' . $module;
}

// Pre-calculate which dropdowns should be open based on current URL (server-side)
$currentPath = '/' . request()->path();

function shouldDropdownBeOpenServerSide($menu, $currentPath) {
    $children = $menu['visible_children'] ?? $menu['children'] ?? [];
    foreach ($children as $child) {
        if (!empty($child['route']) && Route::has($child['route'])) {
            $childPath = '/' . str_replace('.', '/', $child['route']);
            // Match exact path or path with trailing segments
            if ($currentPath === $childPath || str_starts_with($currentPath, $childPath . '/') || str_starts_with($currentPath, $childPath . '?')) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Helper: Check if a child menu has sidebar permission
 */
function childHasPermission($child, $isSuperAdmin, $sidebarPerms) {
    $childPermKey = $child['permission_key'] ?? null;
    $childRoute = $child['route'] ?? null;

    if (!$childRoute || !Route::has($childRoute)) {
        return false;
    }

    if ($isSuperAdmin) {
        return true;
    }

    if ($childPermKey) {
        $normalizedKey = str_starts_with($childPermKey, 'sidebar.') ? $childPermKey : 'sidebar.' . $childPermKey;
        return in_array($normalizedKey, $sidebarPerms);
    }

    return false;
}

/**
 * Pre-calculate visible children for each group
 * This ensures parent modules only show if they have at least one accessible child
 */
$processedMenuConfig = [];
$openDropdownsServerSide = []; // Track which dropdowns should be open

foreach ($menuConfig as $menu) {
    $menuType = $menu['type'] ?? 'item';

    if ($menuType === 'item') {
        // Standalone items (like Beranda) are always shown
        $processedMenuConfig[] = $menu;
    } elseif ($menuType === 'group') {
        // Filter children to only include those with permission
        $visibleChildren = [];
        foreach ($menu['children'] ?? [] as $child) {
            if (childHasPermission($child, $isSuperAdmin, $sidebarPerms)) {
                $visibleChildren[] = $child;
            }
        }

        // Only add the group if it has at least one visible child
        if (count($visibleChildren) > 0) {
            $menu['visible_children'] = $visibleChildren;
            $processedMenuConfig[] = $menu;

            // Check if this dropdown should be open based on current URL
            if (shouldDropdownBeOpenServerSide($menu, $currentPath)) {
                $openDropdownsServerSide[$menu['key']] = true;
            }
        }
    }
}
@endphp

<div x-data="sidebarMenu()">
    {{-- Sidebar with collapse state classes --}}
    <aside
        class="crm-sidebar"
        :class="{
            'sidebar-collapsed': !Alpine.store('sidebar')?.visible,
            'mobile-open': Alpine.store('sidebar')?.mobileOpen
        }"
    >
        {{-- User Profile Card --}}
        <div class="sidebar-header">
            <div class="flex items-center gap-3 w-full">
                {{-- Avatar circle (always visible) --}}
                <div class="sidebar-avatar-circle">
                    @if($user && $user->profile_photo && Storage::disk('public')->exists($user->profile_photo))
                        <img src="{{ Storage::disk('public')->url($user->profile_photo) }}" alt="{{ $user->name }}">
                    @else
                        {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                    @endif
                </div>
                {{-- User info (hidden when collapsed) --}}
                <div class="sidebar-user-info" x-show="Alpine.store('sidebar')?.visible" x-transition>
                    <div class="sidebar-user-name">{{ $user->name ?? 'User' }}</div>
                    <div class="sidebar-user-email">{{ $user->email ?? '' }}</div>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav" role="navigation">
            <ul class="sidebar-menu">
                @foreach($processedMenuConfig as $menu)
                    @php
                    $menuType = $menu['type'] ?? 'item';
                    $menuKey = $menu['key'] ?? '';
                    $menuLabel = $menu['label'] ?? '';
                    $menuIcon = $menu['icon_class'] ?? 'fa-solid ' . ($menu['icon'] ?? 'fa-circle');
                    $menuRoute = $menu['route'] ?? null;
                    @endphp

                    {{-- STANDALONE ITEM (Beranda) --}}
                    @if($menuType === 'item')
                        <li class="sidebar-menu-item">
                            <a href="{{ route($menuRoute) }}"
                               class="sidebar-link {{ request()->routeIs($menuRoute) ? 'active' : '' }}"
                               @click="handleItemClick('{{ $menuRoute }}')"
                               :title="!Alpine.store('sidebar')?.visible ? '{{ $menuLabel }}' : ''">
                                <i class="{{ $menuIcon }} sidebar-icon"></i>
                                <span class="sidebar-text" x-show="Alpine.store('sidebar')?.visible">{{ $menuLabel }}</span>
                            </a>
                        </li>
                    @endif

                    {{-- DROPDOWN GROUP (only rendered if has visible children) --}}
                    @if($menuType === 'group')
                        @php
                        $redirectUrl = '';
                        if (!empty($menu['redirect_to']) && Route::has($menu['redirect_to'])) {
                            $redirectUrl = route($menu['redirect_to']);
                        }
                        // Check if this dropdown should be open (server-side calculated)
                        $isDropdownOpenServerSide = $openDropdownsServerSide[$menuKey] ?? false;
                        @endphp
                        <li class="sidebar-dropdown"
                            :class="{ 'open': isDropdownOpen('{{ $menuKey }}') }"
                            :data-tooltip="!Alpine.store('sidebar')?.visible ? '{{ $menuLabel }}' : ''">
                            {{-- Dropdown Toggle Button --}}
                            <button type="button"
                                    class="sidebar-dropdown-toggle w-full text-left"
                                    @click="handleDropdownClick('{{ $menuKey }}', '{{ $redirectUrl }}')"
                                    :title="!Alpine.store('sidebar')?.visible ? '{{ $menuLabel }}' : ''">
                                <i class="{{ $menuIcon }} sidebar-icon"></i>
                                <span class="sidebar-text" x-show="Alpine.store('sidebar')?.visible" x-collapse>{{ $menuLabel }}</span>
                                <i class="fa-solid fa-chevron-right sidebar-chevron"
                                   :class="{ 'rotate-90': isDropdownOpen('{{ $menuKey }}') }"></i>
                            </button>

                            {{-- Submenu --}}
                            <ul class="sidebar-submenu" x-show="Alpine.store('sidebar')?.visible" x-collapse>
                                @foreach($menu['visible_children'] ?? [] as $child)
                                    @php
                                    $childKey = $child['key'] ?? '';
                                    $childLabel = $child['label'] ?? '';
                                    $childIcon = $child['icon_class'] ?? 'fa-solid ' . ($child['icon'] ?? 'fa-circle');
                                    $childRoute = $child['route'] ?? null;
                                    @endphp
                                    <li>
                                        <a href="{{ route($childRoute) }}"
                                           class="sidebar-submenu-link {{ request()->routeIs($childRoute) ? 'active' : '' }}">
                                            <i class="fa-solid {{ $childIcon }}"></i>
                                            <span>{{ $childLabel }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>
    </aside>
</div>

{{-- Pass server-side state to JavaScript component --}}
<script>
    // Set the server-side rendered state for the sidebarMenu component
    // This is read by sidebar-menu.js when the component initializes
    if (typeof window.setSidebarMenuState === 'function') {
        window.setSidebarMenuState({!! json_encode($openDropdownsServerSide) !!});
    }
</script>
