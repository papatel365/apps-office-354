{{-- resources/views/components/sidebar-item.blade.php
    Sidebar Item Component
    A simple link item for the sidebar menu
    Automatically closes mobile sidebar when clicked

    Props:
    - href: URL of the link (or route name)
    - icon: Font Awesome icon class (e.g., 'fa-solid fa-home')
    - text: Label text
    - badge: Optional badge count
    - active: Whether this item is currently active
--}}

@props([
    'href' => '#',
    'icon' => 'fa-solid fa-circle',
    'text' => '',
    'badge' => null,
    'active' => false,
])

@php
    // Support both 'href' (URL) and 'route' (route name)
    $finalHref = $href;
    if ($href === '#' && isset($route)) {
        try {
            $finalHref = route($route);
        } catch (\Exception $e) {
            $finalHref = $href;
        }
    } elseif (is_string($href) && !str_starts_with($href, '/') && !str_starts_with($href, 'http') && $href !== '#') {
        // If href looks like a route name, try to generate URL
        try {
            $finalHref = route($href);
        } catch (\Exception $e) {
            $finalHref = $href;
        }
    }
@endphp

<li class="sidebar-menu-item">
    <a
        href="{{ $finalHref }}"
        @class([
            'sidebar-link',
            'active' => $active,
        ])
        @if($active) aria-current="page" @endif
        @click="$parent.closeMobile()"
    >
        <span class="sidebar-icon flex-shrink-0">
            <i class="{{ $icon }}"></i>
        </span>
        <span class="sidebar-text truncate">{{ $text }}</span>

        @if($badge)
            <span class="sidebar-badge flex-shrink-0">{{ $badge }}</span>
        @endif

        {{-- Tooltip for collapsed state --}}
        <span class="sidebar-tooltip">{{ $text }}</span>
    </a>
</li>
