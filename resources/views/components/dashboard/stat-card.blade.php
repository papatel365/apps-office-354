{{-- resources/views/components/dashboard/stat-card.blade.php --}}
{{--
    Stat Card Widget
    Usage: <x-dashboard.stat-card title="Total Proyek" :value="$totalProjects" icon="folder" color="purple" :trend="10" />
--}}
@props([
    'title' => '',
    'value' => 0,
    'icon' => 'chart',
    'color' => 'blue',
    'trend' => null,
    'subtitle' => null,
    'progress' => null,
])

@php
    $colorClasses = [
        'purple' => 'bg-purple-100 text-purple-600',
        'blue' => 'bg-blue-100 text-blue-600',
        'amber' => 'bg-amber-100 text-amber-600',
        'cyan' => 'bg-cyan-100 text-cyan-600',
        'green' => 'bg-green-100 text-green-600',
        'indigo' => 'bg-indigo-100 text-indigo-600',
        'emerald' => 'bg-emerald-100 text-emerald-600',
        'red' => 'bg-red-100 text-red-600',
    ];

    $colorClass = $colorClasses[$color] ?? $colorClasses['blue'];
    $iconMap = [
        'folder' => 'fa-folder-open',
        'folder-plus' => 'fa-folder-plus',
        'chart' => 'fa-chart-simple',
        'check' => 'fa-check',
        'clock' => 'fa-clock',
        'play' => 'fa-play',
        'list' => 'fa-list-check',
        'laptop' => 'fa-laptop',
        'users' => 'fa-users',
        'user' => 'fa-user',
        'calendar' => 'fa-calendar',
        'arrow-up' => 'fa-arrow-up',
        'arrow-down' => 'fa-arrow-down',
    ];
    $iconClass = $iconMap[$icon] ?? $iconMap['chart'];
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow animate-fadeIn">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="rounded-lg {{ $colorClass }} p-3">
                    <i class="fa-solid {{ $iconClass }} text-xl"></i>
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
                <p class="text-2xl font-bold text-gray-900">{{ number_format($value) }}</p>
            </div>
        </div>
    </div>
    <div class="mt-4 flex items-center text-sm">
        @if($trend !== null)
            @if($trend >= 0)
                <span class="text-green-600 flex items-center">
                    <i class="fa-solid fa-arrow-up mr-1"></i>
                    {{ abs($trend) }}%
                </span>
            @else
                <span class="text-red-600 flex items-center">
                    <i class="fa-solid fa-arrow-down mr-1"></i>
                    {{ abs($trend) }}%
                </span>
            @endif
            <span class="text-gray-400 ml-2">dari bulan lalu</span>
        @elseif($subtitle)
            <span class="text-gray-600 flex items-center">
                {!! $subtitle !!}
            </span>
        @endif
    </div>
    @if($progress !== null)
        <div class="mt-3">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                <span>Progress</span>
                <span>{{ $progress }}%</span>
            </div>
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-{{ $color }}-500 rounded-full" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    @endif
</div>
