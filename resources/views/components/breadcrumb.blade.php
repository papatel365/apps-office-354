{{-- resources/views/components/breadcrumb.blade.php --}}
{{--
    Global Breadcrumb Component
    Automatically generates breadcrumbs from the current route.
    Supports custom titles set via Breadcrumb::set() or @breadcrumb directive.

    Usage:
    <x-breadcrumb />

    With custom trail (overrides auto-generation):
    <x-breadcrumb :trail="$customTrail" />

    With custom title override:
    <x-breadcrumb title="PT ABC Indonesia" />

    Each breadcrumb item has these properties:
    - label: Display text (required)
    - icon: Font Awesome icon class (optional)
    - url: Link URL (optional - if null, renders as <span>)
    - current: Boolean (optional)
    - clickable: Boolean (optional)
      * false = always render as <span> even if url exists
      * true = render as <a> if url exists
      * null = auto-detect from url presence (backward compatible)
--}}

@php
    // Get breadcrumb trail
    $trail = $trail ?? \App\Services\BreadcrumbBuilder::build();

    // Optional custom title for the last crumb
    $customTitle = $title ?? null;
    if ($customTitle && count($trail) > 0) {
        $lastIndex = count($trail) - 1;
        $trail[$lastIndex]['label'] = $customTitle;
        $trail[$lastIndex]['current'] = true;
        $trail[$lastIndex]['url'] = null;
        $trail[$lastIndex]['clickable'] = false;
    }
@endphp

@if(count($trail) > 0)
{{-- Desktop Breadcrumb --}}
<nav aria-label="Breadcrumb" class="breadcrumb-nav hidden sm:block">
    <ol class="breadcrumb-list flex items-center gap-1" role="list">
        @foreach($trail as $index => $crumb)
            @if(!$loop->first)
                <li class="breadcrumb-item flex items-center shrink-0">
                    <span class="breadcrumb-separator" aria-hidden="true">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </span>
                </li>
            @endif

            <li class="breadcrumb-item shrink-0" role="listitem">
                @if(!($crumb['clickable'] ?? true))
                    <span class="breadcrumb-text flex items-center gap-1.5">
                        @if(!empty($crumb['icon'] ?? null))
                            <i class="fa-solid {{ $crumb['icon'] }} text-xs text-gray-400"></i>
                        @endif
                        {{ $crumb['label'] }}
                    </span>
                @else
                    <a href="{{ $crumb['url'] }}"
                       class="breadcrumb-link flex items-center gap-1.5"
                       @if($crumb['current'] ?? false) aria-current="page" @endif>
                        @if(!empty($crumb['icon'] ?? null))
                            <i class="fa-solid {{ $crumb['icon'] }} text-xs text-gray-400"></i>
                        @endif
                        {{ $crumb['label'] }}
                    </a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

{{-- Mobile Breadcrumb --}}
<div class="sm:hidden">
    <div class="flex items-center gap-1 overflow-x-auto scrollbar-hide pb-0.5">
        @foreach($trail as $index => $crumb)
            @if(!$loop->first)
                <span class="flex items-center shrink-0 text-gray-400">
                    <i class="fa-solid fa-chevron-right text-[8px]"></i>
                </span>
            @endif

            @if(!($crumb['clickable'] ?? true))
                <span class="flex items-center shrink-0 text-sm font-medium text-gray-700 whitespace-nowrap">
                    @if(!empty($crumb['icon'] ?? null))
                        <i class="fa-solid {{ $crumb['icon'] }} text-xs text-gray-400 mr-1"></i>
                    @endif
                    {{ $crumb['label'] }}
                </span>
            @else
                <a href="{{ $crumb['url'] }}"
                   class="flex items-center shrink-0 text-sm text-gray-500 whitespace-nowrap hover:text-indigo-600"
                   @if($crumb['current'] ?? false) aria-current="page" @endif>
                    @if(!empty($crumb['icon'] ?? null))
                        <i class="fa-solid {{ $crumb['icon'] }} text-xs text-gray-400 mr-1"></i>
                    @endif
                    {{ $crumb['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</div>
@endif

@push('styles')
<style>
.breadcrumb-nav {
    margin-bottom: 0.25rem;
}

.breadcrumb-list {
    flex-wrap: nowrap;
    overflow: hidden;
}

.breadcrumb-item {
    flex-shrink: 0;
}

.breadcrumb-separator {
    display: flex;
    align-items: center;
    color: #d1d5db;
}

.breadcrumb-link {
    color: #6b7280;
    font-size: 0.8125rem;
    font-weight: 400;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    transition: color 0.15s ease, background-color 0.15s ease;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.breadcrumb-link:hover {
    color: #4f46e5;
    background-color: #eef2ff;
}

/* Non-clickable text style */
.breadcrumb-text {
    color: #374151;
    font-size: 0.8125rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

/* Mobile scrollbar hide */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
@endpush
