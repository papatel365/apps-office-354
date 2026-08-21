{{-- Stat Card Component --}}
@props([
    'title' => '',
    'value' => '0',
    'icon' => 'fa-chart-line',
    'iconBg' => 'bg-indigo-500',
    'trend' => null,
    'trendType' => 'up',
    'subtitle' => '',
])

<div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg hover:border-gray-300 transition-all duration-200">
    <div class="flex items-start justify-between">
        <div class="flex-1">
            <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $value }}</p>
            @if($subtitle)
                <p class="mt-1 text-xs text-gray-400">{{ $subtitle }}</p>
            @endif
        </div>
        <div class="w-12 h-12 rounded-xl {{ $iconBg }} flex items-center justify-center shadow-lg">
            <i class="fa-solid {{ $icon }} text-white"></i>
        </div>
    </div>

    @if($trend)
        <div class="mt-3 flex items-center gap-1.5">
            @if($trendType === 'up')
                <span class="inline-flex items-center text-xs font-medium text-emerald-600">
                    <i class="fa-solid fa-arrow-up mr-1"></i>
                    {{ $trend }}
                </span>
            @elseif($trendType === 'down')
                <span class="inline-flex items-center text-xs font-medium text-red-600">
                    <i class="fa-solid fa-arrow-down mr-1"></i>
                    {{ $trend }}
                </span>
            @else
                <span class="inline-flex items-center text-xs font-medium text-gray-500">
                    <i class="fa-solid fa-minus mr-1"></i>
                    {{ $trend }}
                </span>
            @endif
            <span class="text-xs text-gray-400">vs last month</span>
        </div>
    @endif
</div>
