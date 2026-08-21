{{-- resources/views/crm/components/breadcrumb.blade.php --}}

<nav class="flex" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-2">
        @foreach($breadcrumbs as $breadcrumb)
            <li class="flex items-center">

                {{-- Separator --}}
                @if(!$loop->first)
                    <svg class="w-4 h-4 text-gray-400 mx-2"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"/>
                    </svg>
                @endif

                @if($breadcrumb['url'] ?? null)
                    <a href="{{ $breadcrumb['url'] }}"
                       class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-gray-700">

                        @if(!empty($breadcrumb['icon']))
                            <i class="{{ $breadcrumb['icon'] }}"></i>
                        @endif

                        <span>{{ $breadcrumb['label'] }}</span>
                    </a>
                @else
                    <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">

                        @if(!empty($breadcrumb['icon']))
                            <i class="{{ $breadcrumb['icon'] }}"></i>
                        @endif

                        <span>{{ $breadcrumb['label'] }}</span>
                    </span>
                @endif

            </li>
        @endforeach
    </ol>
</nav>