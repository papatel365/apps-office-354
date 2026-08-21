{{-- resources/views/components/sidebar-dropdown.blade.php --}}
{{--
    Sidebar Dropdown Component
    A collapsible menu item with submenu
    Uses Alpine's x-data from parent component

    Props:
    - icon: Font Awesome icon class for the parent
    - text: Parent label text
    - open: Whether the dropdown should be open initially
    - items: Array of submenu items with 'href', 'icon', and 'text' keys
--}}

@props([
    'icon' => 'fa-solid fa-folder',
    'text' => '',
    'open' => false,
    'items' => [],
])

<li
    class="sidebar-dropdown"
    x-data="{ isOpen: {{ $open ? 'true' : 'false' }}"
    :class="{ 'open': isOpen }"
>
    <a
        href="#"
        class="sidebar-dropdown-toggle"
        @click.prevent="isOpen = !isOpen"
        :aria-expanded="isOpen.toString()"
        aria-haspopup="true"
    >
        <span class="sidebar-icon flex-shrink-0">
            <i class="{{ $icon }}"></i>
        </span>
        <span class="sidebar-text truncate flex-1 min-w-0">{{ $text }}</span>
        <span class="sidebar-chevron flex-shrink-0" :class="{ 'rotate-90': isOpen }">
            <i class="fa-solid fa-chevron-right"></i>
        </span>

        {{-- Tooltip for collapsed state --}}
        <span class="sidebar-tooltip">{{ $text }}</span>
    </a>

    <ul class="sidebar-submenu" role="menu">
        @foreach($items as $item)
            <li class="sidebar-submenu-item">
                @php
                    // Support both 'href' and 'route' keys
                    $itemHref = $item['href'] ?? ($item['route'] ?? '#');
                    if (is_string($itemHref) && !str_starts_with($itemHref, '/') && !str_starts_with($itemHref, 'http')) {
                        try {
                            $itemHref = route($itemHref);
                        } catch (\Exception $e) {
                            // Keep as is
                        }
                    }
                @endphp
                <a
                    href="{{ $itemHref }}"
                    class="sidebar-submenu-link @if(isset($item['active']) && $item['active']) active @endif"
                    role="menuitem"
                    @click="$parent.closeMobile()"
                >
                    <span class="sidebar-icon flex-shrink-0">
                        <i class="{{ $item['icon'] ?? 'fa-solid fa-chevron-right' }}"></i>
                    </span>
                    <span class="truncate">{{ $item['text'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</li>
